# PayPlus Payment Gateway Plugin for PrestaShop 9.0.2

A comprehensive payment gateway integration module for PrestaShop that enables merchants to accept payments through PayPlus IPG with secure HMAC authentication and environment-based endpoint configuration.

## Features

- **Secure Payment Processing**: HMAC-SHA256 signature validation for all transactions
- **Environment Configuration**: Easy switching between sandbox and live environments
- **Admin Dashboard**: Intuitive configuration panel for merchant credentials
- **Order Management**: Automatic order status updates based on payment callbacks
- **Transaction Logging**: Comprehensive transaction history and status tracking
- **Error Handling**: Robust error handling and logging for debugging
- **Multi-Currency Support**: Support for multiple currencies
- **Responsive Design**: Mobile-friendly payment information display

## Requirements

- PrestaShop 9.0.2 or compatible version (1.7.0+)
- PHP 7.4 or higher
- cURL extension enabled
- MySQL 5.7 or higher
- HTTPS enabled (recommended for production)

## Installation

### Step 1: Upload Plugin Files

1. Download the `payplus.zip` file
2. Extract the contents to your PrestaShop modules directory:
   ```
   /path/to/prestashop/modules/payplus/
   ```

### Step 2: Install Module in PrestaShop Admin

1. Log in to your PrestaShop admin panel
2. Navigate to **Modules and Services** → **Modules and Services**
3. Search for "PayPlus" in the module list
4. Click **Install** on the PayPlus Payment Gateway module
5. Once installed, click **Configure**

### Step 3: Configure Merchant Credentials

1. In the PayPlus configuration page, enter the following details:
   - **Merchant ID**: Your PayPlus merchant identifier
   - **Merchant Secret**: Your PayPlus merchant secret key
   - **Application Key**: Your PayPlus application key
   - **Environment**: Select "Sandbox" for testing or "Live" for production
   - **Sandbox API Endpoint**: `https://gateway-api-dev.payplus.lk/api/payment-session/create`
   - **Live API Endpoint**: `https://gateway-live-api-dev.payplus.lk/api/payment-session/create`

2. Click **Save** to store the configuration

### Step 4: Enable Payment Method

1. Go to **Shop Parameters** → **Payment** → **Payment Methods**
2. Ensure PayPlus is enabled and configured
3. Set the payment method as active for your store

## Configuration

### Admin Settings

The plugin provides a comprehensive configuration interface accessible from the module management page:

| Setting | Description | Required |
|---------|-------------|----------|
| Merchant ID | Your PayPlus merchant identifier | Yes |
| Merchant Secret | Secret key for HMAC signature generation | Yes |
| Application Key | Application key for payment requests | Yes |
| Environment | Choose between Sandbox and Live | Yes |
| Sandbox Endpoint | API endpoint for sandbox testing | No |
| Live Endpoint | API endpoint for production payments | No |

### Environment Variables

The module uses the following configuration keys:

```php
PAYPLUS_MERCHANT_ID          // Merchant identifier
PAYPLUS_MERCHANT_SECRET      // Secret for HMAC generation
PAYPLUS_APPLICATION_KEY      // Application key
PAYPLUS_ENVIRONMENT          // 'sandbox' or 'live'
PAYPLUS_SANDBOX_ENDPOINT     // Sandbox API URL
PAYPLUS_LIVE_ENDPOINT        // Live API URL
```

## Payment Flow

### Customer Checkout Process

1. Customer selects "Pay with PayPlus" at checkout
2. Order is created in PrestaShop with "Pending" status
3. Customer is redirected to PayPlus payment gateway
4. Customer completes payment on PayPlus platform
5. PayPlus sends callback notification to PrestaShop
6. Order status is automatically updated based on payment result

### Technical Flow

```
Customer Checkout
    ↓
Create Order (Pending)
    ↓
Generate Payment Payload
    ↓
Encode Payload (Base64)
    ↓
Generate HMAC-SHA256 Signature
    ↓
Send to PayPlus API
    ↓
Receive Payment Link
    ↓
Redirect Customer to PayPlus
    ↓
Customer Completes Payment
    ↓
PayPlus Sends Callback
    ↓
Validate Signature
    ↓
Update Order Status
    ↓
Update Transaction Record
```

## Payload Structure

The plugin sends the following payload to PayPlus:

```json
{
  "merchantId": "your_merchant_id",
  "applicationKey": "your_app_key",
  "domain": "your-domain.com",
  "amount": 1000.00,
  "paymentType": "ONE_TIME",
  "orderId": "ORDER_REF_123",
  "currency": "LKR",
  "pluginVersion": "1.0.0",
  "notifyUrl": "https://your-domain.com/module/payplus/validation",
  "redirectUrl": "https://your-domain.com/order-confirmation",
  "source": "PRESTASHOP",
  "description": "Order #ORDER_REF_123",
  "doInitialPayment": false,
  "customerInfo": {
    "firstname": "John",
    "lastname": "Doe",
    "email": "john@example.com",
    "phoneNumber": "+94771234567",
    "dialCode": "+94"
  }
}
```

## Security

### HMAC Signature Validation

All requests and callbacks are secured using HMAC-SHA256 signatures:

1. **Request Signature**: Generated before sending payment request to PayPlus
2. **Callback Validation**: Signature is validated when receiving payment status updates
3. **Secret Key**: Merchant secret is used for signature generation and validation

### Best Practices

- **Keep Credentials Secure**: Never share your Merchant Secret or Application Key
- **Use HTTPS**: Always use HTTPS for your PrestaShop installation
- **Regular Updates**: Keep the plugin updated with the latest security patches
- **Monitor Logs**: Regularly check PrestaShop logs for any payment-related errors
- **Test Thoroughly**: Always test in sandbox environment before going live

## Database

The plugin creates a transaction tracking table:

```sql
payplus_transactions
├── id_transaction (INT, Primary Key)
├── id_order (INT, Foreign Key)
├── order_reference (VARCHAR)
├── merchant_id (VARCHAR)
├── session_id (VARCHAR)
├── amount (DECIMAL)
├── currency (VARCHAR)
├── status (VARCHAR)
├── payment_type (VARCHAR)
├── response_data (LONGTEXT)
├── created_at (DATETIME)
└── updated_at (DATETIME)
```

## Order Status Mapping

The plugin automatically maps PayPlus payment statuses to PrestaShop order statuses:

| PayPlus Status | PrestaShop Status | Description |
|----------------|-------------------|-------------|
| COMPLETED | Payment Accepted | Payment successful |
| PENDING | Pending | Payment awaiting confirmation |
| FAILED | Error | Payment failed |
| CANCELLED | Cancelled | Payment cancelled by customer |
| EXPIRED | Error | Payment session expired |

## Troubleshooting

### Payment Session Creation Failed

**Issue**: "Failed to create payment session" error

**Solutions**:
1. Verify merchant credentials are correct
2. Check if cURL is enabled on your server
3. Ensure API endpoint is accessible
4. Check PrestaShop logs for detailed error messages

### Callback Not Received

**Issue**: Order status not updating after payment

**Solutions**:
1. Verify callback URL is accessible from PayPlus servers
2. Check firewall/security settings
3. Ensure HTTPS certificate is valid
4. Review PrestaShop logs for callback errors

### Signature Validation Failed

**Issue**: Callback validation errors

**Solutions**:
1. Verify merchant secret is correct
2. Check if payload encoding is correct
3. Ensure HMAC algorithm is SHA256
4. Review PayPlus documentation for signature format

### Module Not Appearing

**Issue**: PayPlus payment option not showing at checkout

**Solutions**:
1. Verify module is installed and enabled
2. Check if payment method is active
3. Ensure currency is supported
4. Clear PrestaShop cache

## Logging

The plugin logs all important events to PrestaShop's system log:

- Payment session creation
- API requests and responses
- Callback receipts
- Order status updates
- Errors and exceptions

Access logs in PrestaShop admin: **Advanced Parameters** → **Logs**

## Support

For technical support or issues:

1. Check PrestaShop logs for error details
2. Review this documentation
3. Contact PayPlus support team
4. Consult PrestaShop community forums

## File Structure

```
payplus/
├── payplus.php                          # Main module file
├── classes/
│   └── PayPlusHelper.php               # Helper functions
├── controllers/
│   └── front/
│       ├── payment.php                 # Payment initiation controller
│       └── validation.php              # Callback handler controller
├── views/
│   └── templates/
│       ├── front/
│       │   └── payment_info.tpl        # Payment info display
│       └── admin/
│           └── transactions.tpl        # Admin transaction list
├── translations/
│   └── en.php                          # English translations
├── index.php                           # Directory protection
└── README.md                           # This file
```

## Version History

### Version 1.0.0
- Initial release
- HMAC-SHA256 authentication
- Sandbox and Live environment support
- Transaction tracking
- Order status management
- Admin configuration interface

## License

This module is licensed under the Academic Free License (AFL 3.0).

## Changelog

### 1.0.0 (Initial Release)
- Complete PayPlus IPG integration
- Secure HMAC authentication
- Environment-based configuration
- Transaction management
- Order status automation

---

**Last Updated**: January 2026
**Compatible with**: PrestaShop 9.0.2 and above
