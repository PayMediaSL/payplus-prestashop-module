<?php
/**
 * PayPlus Helper Class
 * Provides utility functions for PayPlus payment processing
 */

class PayPlusHelper
{
    /**
     * Encode payload to Base64
     */
    public static function encodePayload($payload)
    {
        $jsonPayload = json_encode($payload);
        return base64_encode($jsonPayload);
    }

    /**
     * Generate HMAC SHA256 signature
     */
    public static function generateHmac($encodedPayload, $secret)
    {
        return hash_hmac('sha256', $encodedPayload, $secret);
    }

    /**
     * Validate HMAC signature
     */
    public static function validateHmac($encodedPayload, $signature, $secret)
    {
        $expectedHmac = self::generateHmac($encodedPayload, $secret);
        return hash_equals($expectedHmac, $signature);
    }

    /**
     * Get API endpoint based on environment
     */
    public static function getApiEndpoint($environment = null)
    {
        if ($environment === null) {
            $environment = Configuration::get('PAYPLUS_ENVIRONMENT');
        }

        return $environment === 'live'
            ? Configuration::get('PAYPLUS_LIVE_ENDPOINT')
            : Configuration::get('PAYPLUS_SANDBOX_ENDPOINT');
    }

    /**
     * Make API request to PayPlus
     */
    public static function makeApiRequest($endpoint, $payload, $hmac)
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
     * Get transaction by order reference
     */
    public static function getTransactionByOrderReference($orderReference)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'payplus_transactions` 
                WHERE order_reference = "' . pSQL($orderReference) . '"';

        return Db::getInstance()->getRow($sql);
    }

    /**
     * Get all transactions
     */
    public static function getAllTransactions($limit = 50, $offset = 0)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'payplus_transactions` 
                ORDER BY created_at DESC 
                LIMIT ' . (int) $offset . ', ' . (int) $limit;

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get transactions by status
     */
    public static function getTransactionsByStatus($status, $limit = 50, $offset = 0)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'payplus_transactions` 
                WHERE status = "' . pSQL($status) . '" 
                ORDER BY created_at DESC 
                LIMIT ' . (int) $offset . ', ' . (int) $limit;

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get transaction count
     */
    public static function getTransactionCount()
    {
        $sql = 'SELECT COUNT(*) as count FROM `' . _DB_PREFIX_ . 'payplus_transactions`';
        $result = Db::getInstance()->getRow($sql);

        return isset($result['count']) ? (int) $result['count'] : 0;
    }

    /**
     * Format currency amount
     */
    public static function formatAmount($amount, $decimals = 2)
    {
        return number_format((float) $amount, $decimals, '.', '');
    }

    /**
     * Validate merchant configuration
     */
    public static function validateConfiguration()
    {
        $merchantId = Configuration::get('PAYPLUS_MERCHANT_ID');
        $merchantSecret = Configuration::get('PAYPLUS_MERCHANT_SECRET');
        $applicationKey = Configuration::get('PAYPLUS_APPLICATION_KEY');

        return !empty($merchantId) && !empty($merchantSecret) && !empty($applicationKey);
    }

    /**
     * Get configuration array
     */
    public static function getConfiguration()
    {
        return [
            'merchantId' => Configuration::get('PAYPLUS_MERCHANT_ID'),
            'merchantSecret' => Configuration::get('PAYPLUS_MERCHANT_SECRET'),
            'applicationKey' => Configuration::get('PAYPLUS_APPLICATION_KEY'),
            'environment' => Configuration::get('PAYPLUS_ENVIRONMENT'),
            'sandboxEndpoint' => Configuration::get('PAYPLUS_SANDBOX_ENDPOINT'),
            'liveEndpoint' => Configuration::get('PAYPLUS_LIVE_ENDPOINT'),
        ];
    }

    /**
     * Log payment action
     */
    public static function logPaymentAction($message, $severity = 1)
    {
        PrestaShopLogger::addLog($message, $severity, null, 'PayPlus');
    }

    /**
     * Map PayPlus status to PrestaShop order status
     */
    public static function mapPaymentStatus($paymentStatus)
    {
        $statusMap = [
            'COMPLETED' => Configuration::get('PS_OS_PAYMENT'),
            'PENDING' => Configuration::get('PS_OS_PENDING'),
            'FAILED' => Configuration::get('PS_OS_ERROR'),
            'CANCELLED' => Configuration::get('PS_OS_CANCELED'),
            'EXPIRED' => Configuration::get('PS_OS_ERROR'),
        ];

        return isset($statusMap[$paymentStatus]) ? $statusMap[$paymentStatus] : Configuration::get('PS_OS_ERROR');
    }
}
