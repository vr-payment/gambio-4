<?php declare(strict_types=1);

use GXModules\VRPaymentPayment\Library\{Core\Settings\Struct\Settings, Helper\VRPaymentHelper};

defined('GM_HTTP_SERVER') || define('GM_HTTP_SERVER', HTTP_SERVER);

if (file_exists(dirname(__DIR__) . '/../../../GXModules/vrpayment/VRPaymentPayment/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/../../../GXModules/vrpayment/VRPaymentPayment/vendor/autoload.php';
}

defined('GM_HTTP_SERVER') || define('GM_HTTP_SERVER', HTTP_SERVER);

/**
 * Class vrpayment_ORIGIN
 */
class vrpayment_ORIGIN
{

    /**
     * @var LanguageTextManager
     */
    protected $languageTextManager;

    public function __construct()
    {
        global $order;
        $this->code = 'vrpayment';
        $this->languageTextManager = MainFactory::create_object(LanguageTextManager::class, array(), true);
        $this->_initLanguageConstants();

        $this->title = 'VRPayment ' . $this->languageTextManager->get_text('payment', 'vrpayment');
        $this->description = 'VRPayment ' . $this->languageTextManager->get_text('description', 'vrpayment');

        $this->sort_order = defined('MODULE_PAYMENT_' . strtoupper($this->code)
            . '_SORT_ORDER') ? constant('MODULE_PAYMENT_' . strtoupper($this->code)
            . '_SORT_ORDER') : 0;
        $this->enabled = defined('MODULE_PAYMENT_' . strtoupper($this->code) . '_STATUS')
            && filter_var(constant('MODULE_PAYMENT_' . strtoupper($this->code) . '_STATUS'),
                FILTER_VALIDATE_BOOLEAN);
        $this->info = defined('MODULE_PAYMENT_' . strtoupper($this->code)
            . '_TEXT_INFO') ? constant('MODULE_PAYMENT_' . strtoupper($this->code)
            . '_TEXT_INFO') : '';
        if (defined('MODULE_PAYMENT_' . strtoupper($this->code) . '_ORDER_STATUS_ID')
            && constant('MODULE_PAYMENT_' . strtoupper($this->code) . '_ORDER_STATUS_ID') > 0) {
            $this->order_status = constant('MODULE_PAYMENT_' . strtoupper($this->code) . '_ORDER_STATUS_ID');
        }

        $this->tmpStatus = defined('MODULE_PAYMENT_' . strtoupper($this->code)
            . '_TMPORDER_STATUS_ID') ? (int)constant('MODULE_PAYMENT_' . strtoupper($this->code)
            . '_TMPORDER_STATUS_ID') : 0;
        if (is_object($order)) {
            $this->update_status();
        }
    }

    public function update_status()
    {

    }

    public function javascript_validation()
    {
        return false;
    }


    public function selection()
    {
        $selection = array(
            'id' => $this->code,
            'module' => $this->title,
            'description' => $this->description,
            'fields' => array(),
        );

        return $selection;
    }

    public function pre_confirmation_check()
    {
        return false;
    }

    public function confirmation()
    {
        $paymentName = $this->title;
        foreach ($_SESSION['possiblePaymentMethods'] as $paymentMethod) {
            $slug = 'vrpayment_' . trim(strtolower(VRPaymentHelper::slugify($paymentMethod->getName())));
            if ($slug === $_SESSION['choosen_payment_method']) {
                $paymentName = $paymentMethod->getName();
                $_SESSION['payment_methods_title'] = $paymentName;
            }
        }

        $confirmation = [
            'title' => $paymentName,
        ];

        return $confirmation;
    }

    public function refresh()
    {
    }

    public function process_button()
    {
        return '';
    }

    public function payment_action()
    {
        $redirectUrl = xtc_href_link('shop.php', 'do=VRPaymentPayment/PaymentPage?payment_error=' . $this->code, 'SSL');

        xtc_redirect($redirectUrl, '');
    }

    public function before_process()
    {
        return false;
    }

    public function after_process()
    {

    }

    public function get_error()
    {
        if (isset($_SESSION['vrpayment_error'])) {
            $error = array('error' => $_SESSION['vrpayment_error']);
            unset($_SESSION['vrpayment_error']);
            return $error;
        }
        return false;
    }

    public function check()
    {
        if (!isset ($this->_check)) {
            $check_query = xtc_db_query("select `value` from " . TABLE_CONFIGURATION . " where `key` = 'configuration/MODULE_PAYMENT_" . strtoupper($this->code) . "_STATUS'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    public function install()
    {
        $config = $this->_configuration();
        $sort_order = 0;
        foreach ($config as $key => $data) {
            $install_query = "INSERT IGNORE into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) "
                . "values ('configuration/MODULE_PAYMENT_" . strtoupper($this->code) . "_" . $key . "', '"
                . $data['value'] . "', '" . $sort_order . "', '" . addslashes($data['type'] ?? '')
                . "', now())";
            xtc_db_query($install_query);
            $sort_order++;
        }

        $defaultOrderStatus = [
            'TMPORDER_STATUS_ID' => [
                'names' => ['en' => 'vrpayment temporary', 'de' => 'vrpayment temporaer'],
                'color' => '2196F3',
            ],
            'ORDER_STATUS_ID' => [
                'names' => ['en' => 'Paid', 'de' => 'Bezahlt'],
                'color' => '45a845',
            ],
            'ERRORORDER_STATUS_ID' => [
                'names' => ['en' => 'Error', 'de' => 'Fehler'],
                'color' => 'e0412c',
            ],
            'CANCELORDER_STATUS_ID' => [
                'names' => ['en' => 'Canceled', 'de' => 'Abgesagt'],
                'color' => 'ffa701',
            ],
            'VOIDED_STATUS_ID' => [
                'names' => ['en' => 'Voided', 'de' => 'Entwertet'],
                'color' => 'ffa701',
            ],
            'AUTHORIZED_STATUS_ID' => [
                'names' => ['en' => 'Authorized', 'de' => 'Autorisiert'],
                'color' => '68BBE3',
            ],
            'PROCESSING_STATUS_ID' => [
                'names' => ['en' => 'Processing', 'de' => 'Verarbeitung'],
                'color' => '68BBE3',
            ],
            'FULLFILL_STATUS_ID' => [
                'names' => ['en' => 'Fulfill', 'de' => 'Erfüllen'],
                'color' => '45a845',
            ],
            'DERECOGNIZED_STATUS_ID' => [
                'names' => ['en' => 'Derecognized', 'de' => 'Ausgebucht'],
                'color' => 'ffa701',
            ],
            'REFUNDED_STATUS_ID' => [
                'names' => ['en' => 'Refunded', 'de' => 'Erstattet'],
                'color' => 'ffa701',
            ],
            'PARTIALLY_REFUNDED_STATUS_ID' => [
                'names' => ['en' => 'Partially refunded', 'de' => 'Teilweise erstattet'],
                'color' => 'ffa701',
            ],
        ];
        foreach ($defaultOrderStatus as $configKey => $orderStatusDefaults) {
            $this->updateConfiguration($configKey,
                $this->getOrdersStatus($orderStatusDefaults['names'],
                    $orderStatusDefaults['color']));
        }
    }

    public function _configuration()
    {
        /**
         * Creating checkbox for each payment method.
         */
        $config['STATUS'] = ['type' => 'switcher', 'value' => 'True'];

        foreach ($this->getPaymentMethods() as $method) {
            $title = $method['titles'][$_SESSION['language']];
            if (empty($title)) {
                $title = $method['titles']['english'];
            }

            define('MODULE_PAYMENT_VRPAYMENT_' . strtoupper($method['id']) . '_TITLE', $title);
            define('MODULE_PAYMENT_VRPAYMENT_' . strtoupper($method['id']) . '_DESC', $this->languageTextManager->get_text('would_you_like_to_enable_this_payment_method', 'vrpayment'));
            $config[strtoupper($method['id'])] = ['value' => 'True', 'type' => 'switcher'];
        }

        return $config;
    }

    protected function updateConfiguration($configurationKey, $configurationValue)
    {
        $db = StaticGXCoreLoader::getDatabaseQueryBuilder();
        $db->where('key', 'configuration/MODULE_PAYMENT_' . strtoupper($this->code) . '_' . $configurationKey);
        $db->update(TABLE_CONFIGURATION, ['value' => $configurationValue]);
    }

    protected function getOrdersStatus($names, $color)
    {
        $orderStatusId = null;
        $orderStatusService = StaticGXCoreLoader::getService('OrderStatus');
        /** @var \OrderStatusInterface $orderStatus */
        foreach ($orderStatusService->findAll() as $orderStatus) {
            foreach ($names as $languageCode => $statusName) {
                if ($orderStatus->getName(MainFactory::create('LanguageCode', new StringType($languageCode)))
                    === $statusName) {
                    $orderStatusId = $orderStatus->getId();
                    break 2;
                }
            }
        }
        if ($orderStatusId === null) {
            $newOrderStatus = MainFactory::create('OrderStatus');
            foreach ($names as $languageCode => $statusName) {
                $newOrderStatus->setName(MainFactory::create('LanguageCode', new StringType($languageCode)),
                    new StringType($statusName));
            }
            $newOrderStatus->setColor(new StringType($color));
            $orderStatusId = $orderStatusService->create($newOrderStatus);
        }

        return $orderStatusId;
    }

    public function remove()
    {
        xtc_db_query("delete from " . TABLE_CONFIGURATION . " where `key` in ('" . implode("', '", $this->keys()) . "')");
    }

    /**
     * Determines the module's configuration keys
     * @return array
     */
    public function keys()
    {
        $ckeys = array_keys($this->_configuration());
        $keys = array();
        foreach ($ckeys as $k) {
            $keys[] = 'configuration/MODULE_PAYMENT_' . strtoupper($this->code) . '_' . $k;
        }
        return $keys;
    }

    public function isInstalled()
    {
        foreach ($this->keys() as $key) {
            if (!defined($key)) {
                return false;
            }
        }
        return true;
    }

    protected function getPaymentMethods()
    {
        $configuration = \MainFactory::create('VRPaymentStorage');
        $paymentMethods = json_decode($configuration->get('payment_methods'), true);

        return $paymentMethods;
    }

    protected function _initLanguageConstants()
    {
        $prefix = 'MODULE_PAYMENT_%s';

        $constantNames = [
            sprintf($prefix . '_STATUS_TITLE', strtoupper($this->code)),
            sprintf($prefix . '_STATUS_DESC', strtoupper($this->code)),
            sprintf($prefix . '_SORT_ORDER_TITLE', strtoupper($this->code)),
            sprintf($prefix . '_SORT_ORDER_DESC', strtoupper($this->code)),
            sprintf($prefix . '_SORT_ORDER_ASC', strtoupper($this->code)),
        ];

        foreach ($constantNames as $constantName) {
            $translationKey = 'configuration' . strtolower(str_replace(sprintf($prefix, strtoupper($this->code)), '', $constantName));
            defined($constantName) or define($constantName, $this->languageTextManager->get_text($translationKey, 'vrpayment'));
        }
    }

}

MainFactory::load_origin_class('vrpayment');
