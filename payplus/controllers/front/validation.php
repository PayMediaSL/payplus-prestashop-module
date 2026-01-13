<?php
/**
 * PayPlus Validation Controller
 * Handles payment confirmation callbacks from PayPlus gateway
 */

class PayPlusValidationModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    public function postProcess()
    {
        // Get the raw POST data
        $input = Tools::file_get_contents('php://input');
        $data = json_decode($input, true);

        // Log callback
        PrestaShopLogger::addLog('PayPlus Callback: ' . json_encode($data), 1, null, 'PayPlus');

        // Validate callback signature
        if (!$this->validateCallback($data)) {
            PrestaShopLogger::addLog('PayPlus Callback Signature Validation Failed', 3, null, 'PayPlus');
            http_response_code(401);
            die('Unauthorized');
        }

        // Process payment status
        $this->processPaymentStatus($data);

        http_response_code(200);
        die('OK');
    }

    /**
     * Validate callback signature
     */
    private function validateCallback($data)
    {
        if (!isset($data['signature']) || !isset($data['payload'])) {
            return false;
        }

        $merchantSecret = Configuration::get('PAYPLUS_MERCHANT_SECRET');
        $payload = $data['payload'];

        // Encode payload
        $encodedPayload = base64_encode(json_encode($payload));

        // Generate expected HMAC
        $expectedHmac = hash_hmac('sha256', $encodedPayload, $merchantSecret);

        // Compare signatures
        return hash_equals($expectedHmac, $data['signature']);
    }

    /**
     * Process payment status update
     */
    private function processPaymentStatus($data)
    {
        $payload = $data['payload'];
        $orderId = $payload['orderId'] ?? null;
        $status = $payload['status'] ?? null;
        $sessionId = $payload['sessionId'] ?? null;

        if (!$orderId || !$status) {
            PrestaShopLogger::addLog('PayPlus Callback Missing Required Fields', 3, null, 'PayPlus');
            return;
        }

        // Find order by reference
        $order = new Order();
        $orders = $order->getByReference($orderId);

        if (empty($orders)) {
            PrestaShopLogger::addLog('PayPlus Callback Order Not Found: ' . $orderId, 3, null, 'PayPlus');
            return;
        }

        $order = $orders[0];

        // Update transaction record
        $this->updateTransaction($orderId, $sessionId, $status, $payload);

        // Update order status based on payment status
        $this->updateOrderStatus($order, $status, $payload);
    }

    /**
     * Update transaction record in database
     */
    private function updateTransaction($orderReference, $sessionId, $status, $payload)
    {
        $statusMap = [
            'COMPLETED' => 'completed',
            'PENDING' => 'pending',
            'FAILED' => 'failed',
            'CANCELLED' => 'cancelled',
            'EXPIRED' => 'expired',
        ];

        $dbStatus = isset($statusMap[$status]) ? $statusMap[$status] : $status;

        $sql = 'UPDATE `' . _DB_PREFIX_ . 'payplus_transactions` 
                SET status = "' . pSQL($dbStatus) . '", 
                    session_id = "' . pSQL($sessionId) . '",
                    response_data = "' . pSQL(json_encode($payload)) . '",
                    updated_at = NOW()
                WHERE order_reference = "' . pSQL($orderReference) . '"';

        Db::getInstance()->execute($sql);
    }

    /**
     * Update order status based on payment status
     */
    private function updateOrderStatus($order, $paymentStatus, $payload)
    {
        $statusMap = [
            'COMPLETED' => Configuration::get('PS_OS_PAYMENT'),
            'PENDING' => Configuration::get('PS_OS_PENDING'),
            'FAILED' => Configuration::get('PS_OS_ERROR'),
            'CANCELLED' => Configuration::get('PS_OS_CANCELED'),
            'EXPIRED' => Configuration::get('PS_OS_ERROR'),
        ];

        $newOrderStatus = isset($statusMap[$paymentStatus]) ? $statusMap[$paymentStatus] : Configuration::get('PS_OS_ERROR');

        // Only update if status is different
        if ($order->current_state != $newOrderStatus) {
            $order->setCurrentState($newOrderStatus);

            // Add order history
            $orderHistory = new OrderHistory();
            $orderHistory->id_order = $order->id;
            $orderHistory->changeIdOrderState($newOrderStatus, $order->id);

            // Add order message
            $message = 'PayPlus Payment Status: ' . $paymentStatus;
            if (isset($payload['transactionId'])) {
                $message .= ' | Transaction ID: ' . $payload['transactionId'];
            }

            $order->addMessage($message);

            PrestaShopLogger::addLog('Order #' . $order->reference . ' Status Updated: ' . $paymentStatus, 1, null, 'PayPlus');
        }
    }
}
