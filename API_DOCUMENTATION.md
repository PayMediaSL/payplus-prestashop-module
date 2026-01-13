# PayPlus Plugin API Documentation

## Overview

This document provides technical details for developers integrating with the PayPlus plugin for PrestaShop.

## Payment Request Payload

### Structure

```json
{
  "merchantId": "string",
  "applicationKey": "string",
  "domain": "string",
  "amount": "number",
  "paymentType": "string",
  "orderId": "string",
  "currency": "string",
  "pluginVersion": "string",
  "notifyUrl": "string",
  "redirectUrl": "string",
  "source": "string",
  "description": "string",
  "doInitialPayment": "boolean",
  "customerInfo": {
    "firstname": "string",
    "lastname": "string",
    "email": "string",
    "phoneNumber": "string",
    "dialCode": "string"
  }
}
```

### Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| merchantId | String | Yes | PayPlus merchant identifier |
| applicationKey | String | Yes | PayPlus application key |
| domain | String | Yes | Merchant domain name |
| amount | Number | Yes | Payment amount (e.g., 1000.00) |
| paymentType | String | Yes | Always "ONE_TIME" for single payments |
| orderId | String | Yes | Unique order reference |
| currency | String | Yes | ISO 4217 currency code (e.g., LKR, USD) |
| pluginVersion | String | Yes | Plugin version (e.g., "1.0.0") |
| notifyUrl | String | Yes | Callback URL for payment status |
| redirectUrl | String | Yes | URL to redirect after payment |
| source | String | Yes | Always "PRESTASHOP" for this plugin |
| description | String | Yes | Order description |
| doInitialPayment | Boolean | Yes | Always false for one-time payments |
| customerInfo | Object | Yes | Customer information object |
| customerInfo.firstname | String | Yes | Customer first name |
| customerInfo.lastname | String | Yes | Customer last name |
| customerInfo.email | String | Yes | Customer email address |
| customerInfo.phoneNumber | String | Yes | Customer phone number |
| customerInfo.dialCode | String | Yes | Country dial code (e.g., "+94") |

### Example Payload

```json
{
  "merchantId": "MERCHANT123",
  "applicationKey": "APP_KEY_123",
  "domain": "mystore.com",
  "amount": 5000.00,
  "paymentType": "ONE_TIME",
  "orderId": "ORD-2026-001",
  "currency": "LKR",
  "pluginVersion": "1.0.0",
  "notifyUrl": "https://mystore.com/module/payplus/validation",
  "redirectUrl": "https://mystore.com/order-confirmation?id_order=123",
  "source": "PRESTASHOP",
  "description": "Order #ORD-2026-001",
  "doInitialPayment": false,
  "customerInfo": {
    "firstname": "John",
    "lastname": "Doe",
    "email": "john@example.com",
    "phoneNumber": "0771234567",
    "dialCode": "+94"
  }
}
```

## Authentication

### HMAC-SHA256 Signature

All requests to PayPlus API must include an HMAC-SHA256 signature.

#### Signature Generation Process

1. **Encode Payload**
   ```php
   $jsonPayload = json_encode($payload);
   $encodedPayload = base64_encode($jsonPayload);
   ```

2. **Generate HMAC**
   ```php
   $hmac = hash_hmac('sha256', $encodedPayload, $merchantSecret);
   ```

3. **Include in Request Header**
   ```
   Authorization: hmac {$hmac}
   ```

#### Example (PHP)

```php
$payload = [
    "merchantId" => "MERCHANT123",
    "applicationKey" => "APP_KEY_123",
    // ... other fields
];

$jsonPayload = json_encode($payload);
$encodedPayload = base64_encode($jsonPayload);
$hmac = hash_hmac('sha256', $encodedPayload, $merchantSecret);

$headers = [
    'Content-Type: application/json',
    'Authorization: hmac ' . $hmac
];
```

## API Endpoints

### Payment Session Creation

**Endpoint**: `POST /api/payment-session/create`

**Sandbox**: `https://gateway-api-dev.payplus.lk/api/payment-session/create`

**Live**: `https://gateway-live-api-dev.payplus.lk/api/payment-session/create`

### Request Headers

```
Content-Type: application/json
Authorization: hmac {signature}
```

### Request Body

Base64-encoded JSON payload (as string, not JSON object)

### Response Format

#### Success Response (HTTP 200)

```json
{
  "status": "success",
  "data": {
    "sessionId": "SESSION_ID_123",
    "link": "https://payplus.lk/pay/SESSION_ID_123",
    "expiresAt": "2026-01-12T12:30:00Z"
  }
}
```

#### Error Response (HTTP 4xx/5xx)

```json
{
  "status": "error",
  "message": "Error description",
  "code": "ERROR_CODE"
}
```

## Callback Notification

### Callback Payload Structure

```json
{
  "signature": "string",
  "payload": {
    "sessionId": "string",
    "orderId": "string",
    "status": "string",
    "amount": "number",
    "currency": "string",
    "transactionId": "string",
    "timestamp": "string"
  }
}
```

### Payment Status Values

| Status | Description |
|--------|-------------|
| COMPLETED | Payment successful |
| PENDING | Payment pending |
| FAILED | Payment failed |
| CANCELLED | Payment cancelled |
| EXPIRED | Payment session expired |

### Callback Validation

```php
// Validate signature
$signature = $callbackData['signature'];
$payload = $callbackData['payload'];

$encodedPayload = base64_encode(json_encode($payload));
$expectedHmac = hash_hmac('sha256', $encodedPayload, $merchantSecret);

if (hash_equals($expectedHmac, $signature)) {
    // Signature is valid, process payment
    processPayment($payload);
} else {
    // Signature validation failed
    return error('Invalid signature');
}
```

## Helper Functions

### PayPlusHelper Class

The plugin provides a helper class with utility functions:

```php
// Encode payload
$encoded = PayPlusHelper::encodePayload($payload);

// Generate HMAC
$hmac = PayPlusHelper::generateHmac($encoded, $secret);

// Validate HMAC
$isValid = PayPlusHelper::validateHmac($encoded, $signature, $secret);

// Get API endpoint
$endpoint = PayPlusHelper::getApiEndpoint('sandbox');

// Make API request
$response = PayPlusHelper::makeApiRequest($endpoint, $payload, $hmac);

// Get transaction by order reference
$transaction = PayPlusHelper::getTransactionByOrderReference('ORD-123');

// Get all transactions
$transactions = PayPlusHelper::getAllTransactions($limit = 50, $offset = 0);

// Get transactions by status
$pending = PayPlusHelper::getTransactionsByStatus('pending');

// Format amount
$formatted = PayPlusHelper::formatAmount(1000.50, 2);

// Validate configuration
$isConfigured = PayPlusHelper::validateConfiguration();

// Map payment status to PrestaShop status
$psStatus = PayPlusHelper::mapPaymentStatus('COMPLETED');

// Log payment action
PayPlusHelper::logPaymentAction('Payment processed', 1);
```

## Database Schema

### Transaction Table

```sql
CREATE TABLE `ps_payplus_transactions` (
  `id_transaction` INT(11) NOT NULL AUTO_INCREMENT,
  `id_order` INT(11) NOT NULL,
  `order_reference` VARCHAR(64) NOT NULL UNIQUE,
  `merchant_id` VARCHAR(255) NOT NULL,
  `session_id` VARCHAR(255),
  `amount` DECIMAL(20, 6) NOT NULL,
  `currency` VARCHAR(3) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `payment_type` VARCHAR(50),
  `response_data` LONGTEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transaction`),
  UNIQUE KEY `order_reference` (`order_reference`),
  INDEX `id_order` (`id_order`),
  INDEX `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Query Examples

```php
// Get transaction by order reference
$sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'payplus_transactions` 
        WHERE order_reference = "ORD-123"';
$transaction = Db::getInstance()->getRow($sql);

// Get all pending transactions
$sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'payplus_transactions` 
        WHERE status = "pending" 
        ORDER BY created_at DESC';
$pending = Db::getInstance()->executeS($sql);

// Update transaction status
$sql = 'UPDATE `' . _DB_PREFIX_ . 'payplus_transactions` 
        SET status = "completed", updated_at = NOW() 
        WHERE order_reference = "ORD-123"';
Db::getInstance()->execute($sql);
```

## Configuration API

### Get Configuration

```php
$config = PayPlusHelper::getConfiguration();
// Returns array with all configuration values
```

### Update Configuration

```php
Configuration::updateValue('PAYPLUS_MERCHANT_ID', 'NEW_ID');
Configuration::updateValue('PAYPLUS_ENVIRONMENT', 'live');
```

### Configuration Keys

```php
PAYPLUS_MERCHANT_ID          // Merchant identifier
PAYPLUS_MERCHANT_SECRET      // Secret for HMAC
PAYPLUS_APPLICATION_KEY      // Application key
PAYPLUS_ENVIRONMENT          // 'sandbox' or 'live'
PAYPLUS_SANDBOX_ENDPOINT     // Sandbox API URL
PAYPLUS_LIVE_ENDPOINT        // Live API URL
```

## Error Handling

### Common Error Codes

| Code | Message | Solution |
|------|---------|----------|
| INVALID_MERCHANT | Invalid merchant credentials | Verify merchant ID and keys |
| INVALID_SIGNATURE | Signature validation failed | Check merchant secret |
| INVALID_AMOUNT | Invalid payment amount | Ensure amount is positive |
| INVALID_CURRENCY | Unsupported currency | Use valid ISO 4217 code |
| SESSION_EXPIRED | Payment session expired | Create new payment session |
| NETWORK_ERROR | Network communication error | Check API endpoint accessibility |

### Logging

All errors are logged to PrestaShop logs:

```php
PrestaShopLogger::addLog('Error message', 3, null, 'PayPlus');
```

Access logs in admin: **Advanced Parameters** → **Logs**

## Integration Examples

### Complete Payment Flow

```php
// 1. Create payload
$payload = [
    'merchantId' => Configuration::get('PAYPLUS_MERCHANT_ID'),
    'applicationKey' => Configuration::get('PAYPLUS_APPLICATION_KEY'),
    // ... other fields
];

// 2. Encode and sign
$encoded = PayPlusHelper::encodePayload($payload);
$hmac = PayPlusHelper::generateHmac($encoded, Configuration::get('PAYPLUS_MERCHANT_SECRET'));

// 3. Make request
$endpoint = PayPlusHelper::getApiEndpoint();
$response = PayPlusHelper::makeApiRequest($endpoint, $encoded, $hmac);

// 4. Handle response
if ($response && isset($response['data']['link'])) {
    // Store session ID
    $sessionId = $response['data']['sessionId'];
    
    // Update transaction
    $sql = 'UPDATE `' . _DB_PREFIX_ . 'payplus_transactions` 
            SET session_id = "' . pSQL($sessionId) . '" 
            WHERE order_reference = "' . pSQL($orderId) . '"';
    Db::getInstance()->execute($sql);
    
    // Redirect to payment
    Tools::redirect($response['data']['link']);
} else {
    // Handle error
    PrestaShopLogger::addLog('Payment session creation failed', 3, null, 'PayPlus');
}
```

### Callback Processing

```php
// 1. Receive callback
$input = Tools::file_get_contents('php://input');
$data = json_decode($input, true);

// 2. Validate signature
$encoded = base64_encode(json_encode($data['payload']));
$expectedHmac = hash_hmac('sha256', $encoded, Configuration::get('PAYPLUS_MERCHANT_SECRET'));

if (!hash_equals($expectedHmac, $data['signature'])) {
    http_response_code(401);
    die('Unauthorized');
}

// 3. Process payment
$payload = $data['payload'];
$orderId = $payload['orderId'];
$status = $payload['status'];

// 4. Update order
$order = new Order();
$orders = $order->getByReference($orderId);
if (!empty($orders)) {
    $order = $orders[0];
    $newStatus = PayPlusHelper::mapPaymentStatus($status);
    $order->setCurrentState($newStatus);
}

// 5. Update transaction
$sql = 'UPDATE `' . _DB_PREFIX_ . 'payplus_transactions` 
        SET status = "' . pSQL(strtolower($status)) . '", updated_at = NOW() 
        WHERE order_reference = "' . pSQL($orderId) . '"';
Db::getInstance()->execute($sql);

http_response_code(200);
die('OK');
```

## Rate Limiting

PayPlus API may implement rate limiting. Implement retry logic:

```php
$maxRetries = 3;
$retryDelay = 2; // seconds

for ($i = 0; $i < $maxRetries; $i++) {
    $response = PayPlusHelper::makeApiRequest($endpoint, $payload, $hmac);
    
    if ($response) {
        return $response;
    }
    
    if ($i < $maxRetries - 1) {
        sleep($retryDelay);
    }
}

// Handle failure after retries
PrestaShopLogger::addLog('Payment request failed after ' . $maxRetries . ' retries', 3, null, 'PayPlus');
```

## Testing

### Sandbox Credentials

Use these credentials for testing:

```
Merchant ID: TEST_MERCHANT_123
Merchant Secret: TEST_SECRET_ABC123
Application Key: TEST_APP_KEY_XYZ
```

### Test Payment Amounts

- **Success**: Any amount
- **Decline**: Amounts ending in 99 (e.g., 99.99)
- **Timeout**: Amounts ending in 88 (e.g., 88.88)

### Test Cards

Sandbox typically accepts any valid card format:

```
Card Number: 4111111111111111
Expiry: 12/25
CVV: 123
```

## Versioning

Current API Version: **1.0.0**

The plugin follows semantic versioning:
- **Major**: Breaking changes
- **Minor**: New features, backward compatible
- **Patch**: Bug fixes

---

**Last Updated**: January 2026
**API Version**: 1.0.0
