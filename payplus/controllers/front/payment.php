<?php
/**
 * PayPlus Payment Controller
 * Handles payment initiation and redirection to PayPlus gateway
 */

class PayPlusPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    public function postProcess()
    {
        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Validate minimum amount
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'payplus') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Create order
        $this->module->validateOrder(
            $cart->id,
            Configuration::get('PS_OS_PAYMENT'),
            $cart->getOrderTotal(true, Cart::BOTH),
            $this->module->displayName,
            'PayPlus Payment Session Created',
            [],
            null,
            false,
            $customer->secure_key
        );

        $order = new Order($this->module->currentOrder);

        // Store transaction data
        $this->storeTransaction($order, $cart);

        // Redirect to payment page
        $this->redirectToPayment($order, $customer, $cart);
    }

    /**
     * Store transaction in database
     */
    private function storeTransaction($order, $cart)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'payplus_transactions` 
                (id_order, order_reference, merchant_id, amount, currency, status, created_at, updated_at)
                VALUES 
                (' . (int) $order->id . ', 
                 "' . pSQL($order->reference) . '", 
                 "' . pSQL(Configuration::get('PAYPLUS_MERCHANT_ID')) . '", 
                 ' . (float) $cart->getOrderTotal(true, Cart::BOTH) . ', 
                 "' . pSQL($this->context->currency->iso_code) . '", 
                 "pending", 
                 NOW(), 
                 NOW())';

        Db::getInstance()->execute($sql);
    }

    /**
     * Redirect to PayPlus payment gateway
     */
    private function redirectToPayment($order, $customer, $cart)
    {
        // Get configuration
        $merchantId = Configuration::get('PAYPLUS_MERCHANT_ID');
        $applicationKey = Configuration::get('PAYPLUS_APPLICATION_KEY');
        $merchantSecret = Configuration::get('PAYPLUS_MERCHANT_SECRET');
        $environment = Configuration::get('PAYPLUS_ENVIRONMENT');

        // Determine API endpoint
        $apiEndpoint = $environment === 'live' 
            ? Configuration::get('PAYPLUS_LIVE_ENDPOINT')
            : Configuration::get('PAYPLUS_SANDBOX_ENDPOINT');

        // Get domain
        $domain = Tools::getShopDomain();

        // Prepare URLs
        $notifyUrl = $this->context->link->getModuleLink('payplus', 'validation', [], true);
        $redirectUrl = $this->context->link->getPageLink('order-confirmation', true, null, [
            'id_order' => $order->id,
            'id_customer' => $customer->id,
            'key' => $customer->secure_key,
        ]);

        // Prepare payload
        $payload = [
            'merchantId' => $merchantId,
            'applicationKey' => $applicationKey,
            'domain' => $domain,
            'amount' => (float) $cart->getOrderTotal(true, Cart::BOTH),
            'paymentType' => 'ONE_TIME',
            'orderId' => $order->reference,
            'currency' => $this->context->currency->iso_code,
            'pluginVersion' => '1.0.0',
            'notifyUrl' => $notifyUrl,
            'redirectUrl' => $redirectUrl,
            'source' => 'PRESTASHOP',
            'description' => 'Order #' . $order->reference,
            'doInitialPayment' => false,
            'customerInfo' => [
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
                'email' => $customer->email,
                'phoneNumber' => $this->getCustomerPhone($customer),
                'dialCode' => '+94',
            ],
        ];

        // Encode payload
        $jsonPayload = json_encode($payload);
        $encodedPayload = base64_encode($jsonPayload);

        // Generate HMAC
        $hmac = hash_hmac('sha256', $encodedPayload, $merchantSecret);

        // Make API request
        $response = $this->makeApiRequest($apiEndpoint, $encodedPayload, $hmac);

        if ($response && isset($response['data']['link'])) {
            // Store session ID if available
            if (isset($response['data']['sessionId'])) {
                $this->updateTransactionSessionId($order->reference, $response['data']['sessionId']);
            }

            // Redirect to PayPlus payment page
            Tools::redirect($response['data']['link']);
        } else {
            // Handle error
            $this->context->controller->errors[] = $this->module->l('Failed to create payment session. Please try again.');
            Tools::redirect('index.php?controller=order&step=3');
        }
    }

    /**
     * Get customer phone number
     */
    private function getCustomerPhone($customer)
    {
        $address = new Address($customer->id_address_billing);
        return !empty($address->phone) ? $address->phone : (!empty($address->phone_mobile) ? $address->phone_mobile : '');
    }

    /**
     * Make API request to PayPlus
     */
    private function makeApiRequest($endpoint, $payload, $hmac)
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: hmac ' . $hmac,
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            PrestaShopLogger::addLog('PayPlus API Error: ' . $error, 3, null, 'PayPlus');
            return null;
        }

        if ($httpCode !== 200) {
            PrestaShopLogger::addLog('PayPlus API Response Code: ' . $httpCode . ' Response: ' . $response, 3, null, 'PayPlus');
            return null;
        }

        $decodedResponse = json_decode($response, true);

        return $decodedResponse;
    }

    /**
     * Update transaction with session ID
     */
    private function updateTransactionSessionId($orderReference, $sessionId)
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'payplus_transactions` 
                SET session_id = "' . pSQL($sessionId) . '" 
                WHERE order_reference = "' . pSQL($orderReference) . '"';

        Db::getInstance()->execute($sql);
    }
}
