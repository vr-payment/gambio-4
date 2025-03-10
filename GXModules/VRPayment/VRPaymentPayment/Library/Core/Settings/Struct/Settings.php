<?php declare(strict_types=1);

namespace GXModules\VRPaymentPayment\Library\Core\Settings\Struct;

use VRPayment\Sdk\ApiClient;

/**
 * Class Settings
 *
 * @package VRPaymentPayment\Core\Settings\Struct
 */
class Settings {

	public const SHOP_SYSTEM = 'x-meta-shop-system';
	public const SHOP_SYSTEM_VERSION = 'x-meta-shop-system-version';
	public const SHOP_SYSTEM_AND_VERSION = 'x-meta-shop-system-and-version';

    /**
	 * @var \VRPayment\Sdk\ApiClient
	 */
	protected $apiClient;

	/**
	 * Enable module
	 *
	 * @var bool
	 */
	protected $active;

	/**
	 * Application Key
	 *
	 * @var string
	 */
	protected $applicationKey;

	/**
	 * Enable emails
	 *
	 * @var bool
	 */
	protected $confirmationEmailSendEnabled;

	/**
	 * Preferred integration
	 *
	 * @var string
	 */
	protected $integration;

	/**
	 * Enforce line item consistency
	 *
	 * @var bool
	 */
	protected $lineItemConsistencyEnabled;

	/**
	 * Enable storefront invoice download
	 *
	 * @var bool
	 */
	protected $storefrontInvoiceDownloadEnabled;

	/**
	 * Space Id
	 *
	 * @var int
	 */
	protected $spaceId;

	/**
	 * Space View Id
	 *
	 * @var ?int
	 */
	protected $spaceViewId;

	/**
	 * User id
	 *
	 * @var int
	 */
	protected $userId;

	/**
	 * @param $configuration
	 */
	public function __construct($configuration = null) {

		if ($configuration === null) {
			$configuration = \MainFactory::create('VRPaymentStorage');
		}

		$this->setActive((bool) $configuration->get('active'));
		$this->setUserId((int) $configuration->get('user_id'));
		$this->setSpaceId((int) $configuration->get('space_id'));
		$this->setSpaceViewId((int) $configuration->get('space_view_id'));
		$this->setApplicationKey($configuration->get('application_key'));
		$this->setIntegration($configuration->get('integration'));
		$this->setLineItemConsistencyEnabled((bool) $configuration->get('line_item_consistency'));
		$this->setConfirmationEmailSendEnabled((bool) $configuration->get('send_order_confirmation_email'));
		$this->setStorefrontInvoiceDownloadEnabled((bool) $configuration->get('invoice_download'));
	}

	/**
	 * @return bool
	 */
	public function isConfirmationEmailSendEnabled(): bool
	{
		return boolval($this->confirmationEmailSendEnabled);
	}

	/**
	 * @param bool $confirmationEmailSendEnabled
	 */
	protected function setConfirmationEmailSendEnabled(bool $confirmationEmailSendEnabled): void
	{
		$this->confirmationEmailSendEnabled = $confirmationEmailSendEnabled;
	}


	/**
	 * @return string
	 */
	public function getIntegration(): string
	{
		return strval($this->integration);
	}

	/**
	 * @param string $integration
	 */
	protected function setIntegration(string $integration): void
	{
		$this->integration = $integration;
	}

	/**
	 * @return bool
	 */
	public function isLineItemConsistencyEnabled(): bool
	{
		return boolval($this->lineItemConsistencyEnabled);
	}

	/**
	 * @param bool $lineItemConsistencyEnabled
	 */
	protected function setLineItemConsistencyEnabled(bool $lineItemConsistencyEnabled): void
	{
		$this->lineItemConsistencyEnabled = $lineItemConsistencyEnabled;
	}

	/**
	 * @return bool
	 */
	public function isStorefrontInvoiceDownloadEnabled(): bool
	{
		return boolval($this->storefrontInvoiceDownloadEnabled);
	}

	/**
	 * @param bool $storefrontInvoiceDownloadEnabled
	 */
	protected function setStorefrontInvoiceDownloadEnabled(bool $storefrontInvoiceDownloadEnabled): void
	{
		$this->storefrontInvoiceDownloadEnabled = $storefrontInvoiceDownloadEnabled;
	}

	/**
	 * @return bool
	 */
	public function isActive(): bool
	{
		return boolval($this->actice);
	}

	/**
	 * @param bool $active
	 */
	protected function setActive(bool $active): void
	{
		$this->active = $active;
	}

	/**
	 * @return int
	 */
	public function getSpaceId(): int
	{
		return intval($this->spaceId);
	}

	/**
	 * @param int $spaceId
	 */
	protected function setSpaceId(int $spaceId): void
	{
		$this->spaceId = $spaceId;
	}

	/**
	 * @return int|null
	 */
	public function getSpaceViewId(): ?int
	{
		if (!empty($this->spaceViewId) && is_numeric($this->spaceViewId)) {
			return intval($this->spaceViewId);
		}

		return null;
	}

	/**
	 * @param int $spaceViewId
	 */
	protected function setSpaceViewId(int $spaceViewId): void
	{
		$this->spaceViewId = $spaceViewId;
	}

	/**
	 * Get SDK ApiClient
	 *
	 * @return \VRPayment\Sdk\ApiClient
	 */
	public function getApiClient(): ApiClient
	{
		if (is_null($this->apiClient)) {
			$this->apiClient   = new ApiClient($this->getUserId(), $this->getApplicationKey());
			$apiClientBasePath = getenv('VRPAYMENT_API_BASE_PATH') ? getenv('VRPAYMENT_API_BASE_PATH') : $this->apiClient->getBasePath();
			$this->apiClient->setBasePath($apiClientBasePath);
			foreach (self::getDefaultHeaderData() as $key => $value) {
				$this->apiClient->addDefaultHeader($key, $value);
			}
		}
		return $this->apiClient;
	}

	/**
	 * @return int
	 */
	public function getUserId(): int
	{
		return intval($this->userId);
	}

	/**
	 * @param int $userId
	 */
	protected function setUserId(int $userId): void
	{
		$this->userId = $userId;
	}

	/**
	 * @return string
	 */
	public function getApplicationKey(): string
	{
		return strval($this->applicationKey);
	}

	/**
	 * @param string $applicationKey
	 */
	protected function setApplicationKey(string $applicationKey): void
	{
		$this->applicationKey = $applicationKey;
	}

	/**
	 * @return array
	 */
	protected static function getDefaultHeaderData()
	{
		$gx_version = include DIR_FS_CATALOG . 'release_info.php';
	
		$shop_version = str_replace('v', '', $gx_version);
		[$major_version, $minor_version, $_] = explode('.', $shop_version, 3);
		return [
			self::SHOP_SYSTEM             => 'gambio',
			self::SHOP_SYSTEM_VERSION     => $shop_version,
			self::SHOP_SYSTEM_AND_VERSION => 'gambio-' . $major_version . '.' . $minor_version,
		];
	}
}