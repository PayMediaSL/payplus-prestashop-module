<?php
/**
 * PayPlus IPG Payment Gateway Module for PrestaShop
 *
 * @author PayPlus Team
 * @version 1.0.0
 * @license Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class PayPlus extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'payplus';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'PayPlus';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->controllers = ['payment', 'validation'];
        $this->is_eu_compatible = 1;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        parent::__construct();

        $this->displayName = $this->l('PayPlus Payment Gateway');
        $this->description = $this->l('Accept payments via PayPlus IPG with HMAC authentication');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        if (!Configuration::get('PAYPLUS_MERCHANT_ID')) {
            $this->warning = $this->l('PayPlus Merchant ID is not configured');
        }
    }

    /**
     * Install the module
     */
    public function install()
    {
        if (extension_loaded('curl') === false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to use this module');
            return false;
        }

        return parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('paymentReturn')
            && $this->createTables()
            && $this->installConfiguration();
    }

    /**
     * Uninstall the module
     */
    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallConfiguration();
    }

    /**
     * Create database tables for payment tracking
     */
    private function createTables()
    {
        $sql = [];

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplus_transactions` (
            `id_transaction` INT(11) NOT NULL AUTO_INCREMENT,
            `id_order` INT(11) NOT NULL,
            `order_reference` VARCHAR(64) NOT NULL,
            `merchant_id` VARCHAR(255) NOT NULL,
            `session_id` VARCHAR(255),
            `amount` DECIMAL(20, 6) NOT NULL,
            `currency` VARCHAR(3) NOT NULL,
            `status` VARCHAR(50) NOT NULL DEFAULT "pending",
            `payment_type` VARCHAR(50),
            `response_data` LONGTEXT,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_transaction`),
            UNIQUE KEY `order_reference` (`order_reference`),
            INDEX `id_order` (`id_order`),
            INDEX `status` (`status`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Install configuration settings
     */
    private function installConfiguration()
    {
        Configuration::updateValue('PAYPLUS_MERCHANT_ID', '');
        Configuration::updateValue('PAYPLUS_MERCHANT_SECRET', '');
        Configuration::updateValue('PAYPLUS_APPLICATION_KEY', '');
        Configuration::updateValue('PAYPLUS_ENVIRONMENT', 'sandbox');
        Configuration::updateValue('PAYPLUS_SANDBOX_ENDPOINT', 'https://gateway-api-dev.payplus.lk/api/payment-session/create');
        Configuration::updateValue('PAYPLUS_LIVE_ENDPOINT', 'https://gateway-live-api-dev.payplus.lk/api/payment-session/create');

        return true;
    }

    /**
     * Uninstall configuration settings
     */
    private function uninstallConfiguration()
    {
        Configuration::deleteByName('PAYPLUS_MERCHANT_ID');
        Configuration::deleteByName('PAYPLUS_MERCHANT_SECRET');
        Configuration::deleteByName('PAYPLUS_APPLICATION_KEY');
        Configuration::deleteByName('PAYPLUS_ENVIRONMENT');
        Configuration::deleteByName('PAYPLUS_SANDBOX_ENDPOINT');
        Configuration::deleteByName('PAYPLUS_LIVE_ENDPOINT');

        return true;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitPayPlusModule')) {
            $output .= $this->postProcess();
        }

        return $output . $this->renderForm();
    }

    /**
     * Process form submission
     */
    protected function postProcess()
    {
        $form_values = [
            'PAYPLUS_MERCHANT_ID' => Tools::getValue('PAYPLUS_MERCHANT_ID'),
            'PAYPLUS_MERCHANT_SECRET' => Tools::getValue('PAYPLUS_MERCHANT_SECRET'),
            'PAYPLUS_APPLICATION_KEY' => Tools::getValue('PAYPLUS_APPLICATION_KEY'),
            'PAYPLUS_ENVIRONMENT' => Tools::getValue('PAYPLUS_ENVIRONMENT'),
            'PAYPLUS_SANDBOX_ENDPOINT' => Tools::getValue('PAYPLUS_SANDBOX_ENDPOINT'),
            'PAYPLUS_LIVE_ENDPOINT' => Tools::getValue('PAYPLUS_LIVE_ENDPOINT'),
        ];

        foreach ($form_values as $key => $value) {
            Configuration::updateValue($key, $value);
        }

        return $this->displayConfirmation($this->l('Settings updated successfully'));
    }

    /**
     * Render the configuration form
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_language = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANGUAGE');

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPayPlusModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Create the structure of your form
     */
    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('PayPlus Configuration'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'name' => 'PAYPLUS_MERCHANT_ID',
                        'label' => $this->l('Merchant ID'),
                        'placeholder' => $this->l('Enter your PayPlus Merchant ID'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'PAYPLUS_MERCHANT_SECRET',
                        'label' => $this->l('Merchant Secret'),
                        'placeholder' => $this->l('Enter your PayPlus Merchant Secret'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'PAYPLUS_APPLICATION_KEY',
                        'label' => $this->l('Application Key'),
                        'placeholder' => $this->l('Enter your PayPlus Application Key'),
                        'required' => true,
                    ],
                    [
                        'type' => 'select',
                        'name' => 'PAYPLUS_ENVIRONMENT',
                        'label' => $this->l('Environment'),
                        'options' => [
                            'query' => [
                                ['id' => 'sandbox', 'name' => $this->l('Sandbox (Development)')],
                                ['id' => 'live', 'name' => $this->l('Live (Production)')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'name' => 'PAYPLUS_SANDBOX_ENDPOINT',
                        'label' => $this->l('Sandbox API Endpoint'),
                        'placeholder' => $this->l('Sandbox endpoint URL'),
                    ],
                    [
                        'type' => 'text',
                        'name' => 'PAYPLUS_LIVE_ENDPOINT',
                        'label' => $this->l('Live API Endpoint'),
                        'placeholder' => $this->l('Live endpoint URL'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs
     */
    protected function getConfigFormValues()
    {
        return [
            'PAYPLUS_MERCHANT_ID' => Configuration::get('PAYPLUS_MERCHANT_ID'),
            'PAYPLUS_MERCHANT_SECRET' => Configuration::get('PAYPLUS_MERCHANT_SECRET'),
            'PAYPLUS_APPLICATION_KEY' => Configuration::get('PAYPLUS_APPLICATION_KEY'),
            'PAYPLUS_ENVIRONMENT' => Configuration::get('PAYPLUS_ENVIRONMENT'),
            'PAYPLUS_SANDBOX_ENDPOINT' => Configuration::get('PAYPLUS_SANDBOX_ENDPOINT'),
            'PAYPLUS_LIVE_ENDPOINT' => Configuration::get('PAYPLUS_LIVE_ENDPOINT'),
        ];
    }

    /**
     * Add the payment option to checkout
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return [];
        }

        if (!$this->checkCurrency($params['cart'])) {
            return [];
        }

        $paymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $paymentOption->setCallToActionText($this->l('Pay with PayPlus'))
            ->setAction($this->context->link->getModuleLink($this->name, 'payment', [], true))
            ->setAdditionalInformation($this->fetch('module:payplus/views/templates/front/payment_info.tpl'));

        return [$paymentOption];
    }

    /**
     * Check if currency is supported
     */
    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }
}
