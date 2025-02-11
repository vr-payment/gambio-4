<?php declare(strict_types=1);

namespace GXModules\VRPaymentPayment\Library\Core\Service;

use ExistingDirectory;
use Gambio\Core\Cache\CacheFactory;
use GXModules\VRPaymentPayment\Library\{Core\Settings\Struct\Settings, Helper\VRPaymentHelper};
use LanguageTextManager;
use LegacyDependencyContainer;
use MainFactory;
use RequiredDirectory;
use StaticGXCoreLoader;
use Swaggest\JsonDiff\Exception;
use ThemeDirectoryRoot;
use ThemeId;
use ThemeService;
use ThemeSettings;
use VRPayment\Sdk\{Model\CreationEntityState,
  Model\CriteriaOperator,
  Model\EntityQuery,
  Model\EntityQueryFilter,
  Model\EntityQueryFilterType,
  Model\PaymentMethodConfiguration
};
use VRPaymentStorage;

/**
 * Class WebHooksService
 *
 * @package VRPaymentPayment\Core\Api\WebHooks\Service
 */
class PaymentService
{
	/**
	 * @var string $rootDir
	 */
	protected $rootDir;
	
	/**
	 * @var Settings $settings
	 */
	public $settings;
	
	/**
	 * @var VRPaymentStorage $configuration
	 */
	public $configuration;
	
	/**
	 * @var LanguageTextManager $languageTextManager
	 */
	public $languageTextManager;
	
	/**
	 * @var array
	 */
	private $localeLanguageMapping = [
	  'de-DE' => 'german',
	  'fr-FR' => 'french',
	  'it-IT' => 'italian',
	  'en-US' => 'english',
	];
	
	/**
	 * PaymentService constructor.
	 * @param VRPaymentStorage|null $configuration
	 */
	public function __construct(?VRPaymentStorage $configuration = null)
	{
		$this->rootDir = __DIR__ . '/../../../../../../';
		$this->configuration = $configuration;
		$this->settings = new Settings($this->configuration);
		$this->languageTextManager = MainFactory::create_object(LanguageTextManager::class, array(), true);
	}
	
	public function syncPaymentMethods()
	{
		$paymentMethods = $this->getPaymentMethodConfigurations();
		
		$translations = [];
		
		$data = [];
		/**
		 * PaymentMethodConfiguration $paymentMethod
		 */
		foreach ($paymentMethods as $paymentMethod) {
			$name = 'VRPayment ' . $paymentMethod->getName();
			$slug = trim(strtolower(VRPaymentHelper::slugify($name)));
			
			$descriptions = [];
			$languageMapping = $this->localeLanguageMapping;
			foreach ($paymentMethod->getResolvedDescription() as $locale => $text) {
				$language = $languageMapping[$locale];
				$descriptions[$language] = $translations[$language][$slug . '_description'] = addslashes($text);
			}
			
			$titles = [];
			foreach ($paymentMethod->getResolvedTitle() as $locale => $text) {
				$language = $languageMapping[$locale];
				$titles[$language] = $translations[$language][$slug . '_title'] = addslashes(str_replace('-/', ' / ', $text));
			}
			
			$paymentMethodStateOnPortal = (string)$paymentMethod->getState();
			$data[] = [
			  'state' => $paymentMethodStateOnPortal,
			  'logo_url' => $paymentMethod->getResolvedImageUrl(),
			  'logo_alt' => $slug,
			  'id' => $slug,
			  'module' => $translations['english'][$slug . '_title'],
			  'description' => $translations['english'][$slug . '_description'],
			  'fields' => [],
			  'titles' => $titles,
			  'descriptions' => $descriptions
			];
			
			$key = 'MODULE_PAYMENT_VRPAYMENT_' . strtoupper($slug);
			$query = xtc_db_query("SELECT * FROM `gx_configurations` WHERE `key` = '" . xtc_db_input('configuration/' . $key) . "'");
			$result = xtc_db_fetch_array($query);
			
			if (empty($result)) {
				$install_query = "insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) "
				  . "values ('configuration/" . $key . "', 'false', '0', 'switcher', now())";
				xtc_db_query($install_query);
				
				define($key . '_TITLE', $name . ' ' . $titles['english']);
				define($key . '_DESC', $this->languageTextManager->get_text('would_you_like_to_enable_this_payment_method', 'vrpayment'));
			} else {
				if ($result['state'] === 'true' && strtolower($paymentMethodStateOnPortal) === 'active') {
					xtc_db_perform(
					  'gx_configurations',
					  ['value' => 'false'],
					  'update',
					  'key = ' . xtc_db_input('configuration/' . $key)
					);
				}
			}
		}
		
		$this->configuration->set('payment_methods', \json_encode($data));
	}
	
	/**
	 * @return mixed|VRPaymentStorage
	 */
	public function getConfiguration()
	{
		return $this->configuration ?? MainFactory::create('VRPaymentStorage');
	}
	
	/**
	 * Fetch active merchant payment methods from VRPayment API
	 *
	 * @return \VRPayment\Sdk\Model\PaymentMethodConfiguration[]
	 * @throws \VRPayment\Sdk\ApiException
	 * @throws \VRPayment\Sdk\Http\ConnectionException
	 * @throws \VRPayment\Sdk\VersioningException
	 */
	public function getPaymentMethodConfigurations(): array
	{
		$entityQueryFilter = (new EntityQueryFilter())
		  ->setOperator(CriteriaOperator::EQUALS)
		  ->setFieldName('state')
		  ->setType(EntityQueryFilterType::LEAF)
		  ->setValue(CreationEntityState::ACTIVE);
		
		$entityQuery = (new EntityQuery())->setFilter($entityQueryFilter);
		
		$settings = new Settings($this->configuration);
		$apiClient = $settings->getApiClient();
		$spaceId = $settings->getSpaceId();
		
		if (empty($spaceId)) {
			$GLOBALS['messageStack']->add_session($this->languageTextManager->get_text('no_payment_methods_were_imported_please_check_space_id_setting', 'vrpayment'), 'error');
			return [];
		}
		
		$paymentMethodConfigurations = $apiClient->getPaymentMethodConfigurationService()->search($spaceId, $entityQuery);
		
		usort($paymentMethodConfigurations, function (PaymentMethodConfiguration $item1, PaymentMethodConfiguration $item2) {
			return $item1->getSortOrder() <=> $item2->getSortOrder();
		});
		
		return $paymentMethodConfigurations;
	}
}
