# PayPlus Plugin Installation Guide

## Quick Start

### Prerequisites

Before installing the PayPlus plugin, ensure you have:

- PrestaShop 9.0.2 or compatible version installed
- Admin access to PrestaShop backend
- FTP/SFTP access to your server (or file manager)
- PayPlus merchant account with credentials:
  - Merchant ID
  - Merchant Secret
  - Application Key

### Installation Steps

#### Method 1: Manual Installation (Recommended)

1. **Download the Plugin**
   - Download `payplus.zip` from the provided source

2. **Extract Files**
   - Extract the ZIP file to get the `payplus` folder

3. **Upload to Server**
   - Connect to your server via FTP/SFTP
   - Navigate to `/modules/` directory in your PrestaShop installation
   - Upload the `payplus` folder

4. **Install in PrestaShop Admin**
   - Log in to PrestaShop admin panel
   - Go to **Modules and Services** → **Modules and Services**
   - Search for "PayPlus" in the module list
   - Click the **Install** button

5. **Configure Settings**
   - After installation, click **Configure**
   - Enter your PayPlus merchant credentials:
     - Merchant ID
     - Merchant Secret
     - Application Key
   - Select your environment (Sandbox or Live)
   - Click **Save**

#### Method 2: ZIP Upload (If Available)

1. **Access Module Manager**
   - Log in to PrestaShop admin
   - Go to **Modules and Services** → **Modules and Services**

2. **Upload Module**
   - Click **Upload a module**
   - Select `payplus.zip`
   - Click **Upload**

3. **Install and Configure**
   - Follow the installation prompt
   - Configure merchant credentials
   - Save settings

### Post-Installation Configuration

#### Step 1: Verify Installation

1. Navigate to **Modules and Services** → **Modules and Services**
2. Search for "PayPlus"
3. Confirm the module shows as "Installed"

#### Step 2: Configure Payment Method

1. Go to **Shop Parameters** → **Payment** → **Payment Methods**
2. Locate PayPlus in the payment methods list
3. Ensure it's enabled (toggle switch is ON)
4. Click the settings icon to configure

#### Step 3: Enter Merchant Credentials

1. In the PayPlus module configuration:
   - **Merchant ID**: Enter your PayPlus merchant ID
   - **Merchant Secret**: Enter your merchant secret key
   - **Application Key**: Enter your application key
   - **Environment**: Select "Sandbox" for testing

2. Click **Save** to store configuration

#### Step 4: Test Payment

1. Create a test order in your PrestaShop store
2. At checkout, select "Pay with PayPlus"
3. Verify you're redirected to PayPlus payment gateway
4. Complete a test payment
5. Verify order status updates automatically

### Configuration Details

#### API Endpoints

**Sandbox (Development)**
```
https://gateway-api-dev.payplus.lk/api/payment-session/create
```

**Live (Production)**
```
https://gateway-live-api-dev.payplus.lk/api/payment-session/create
```

#### Required Credentials

| Credential | Description | Where to Find |
|-----------|-------------|----------------|
| Merchant ID | Your unique merchant identifier | PayPlus Dashboard |
| Merchant Secret | Secret key for HMAC signing | PayPlus Dashboard |
| Application Key | Your application's API key | PayPlus Dashboard |

### Troubleshooting Installation

#### Module Not Appearing in List

**Solution**:
1. Clear PrestaShop cache:
   - Go to **Advanced Parameters** → **Performance**
   - Click **Clear cache**
2. Refresh the module list page
3. Search again for "PayPlus"

#### Permission Denied Error

**Solution**:
1. Check folder permissions (should be 755)
2. Ensure the `/modules/` directory is writable
3. Contact your hosting provider if needed

#### cURL Extension Not Found

**Solution**:
1. Contact your hosting provider to enable cURL
2. Verify in **Advanced Parameters** → **System Information**
3. Look for "cURL" in the PHP extensions list

#### Database Error During Installation

**Solution**:
1. Check database credentials in `config/settings.inc.php`
2. Verify database user has CREATE TABLE permissions
3. Clear PrestaShop cache and try again
4. Check PrestaShop logs for detailed error messages

### Switching Environments

#### From Sandbox to Live

1. Go to **Modules and Services** → **PayPlus** → **Configure**
2. Change **Environment** from "Sandbox" to "Live"
3. Verify API endpoints are correct
4. Click **Save**

**Important**: Always test thoroughly in sandbox before switching to live!

#### From Live to Sandbox

1. Go to **Modules and Services** → **PayPlus** → **Configure**
2. Change **Environment** from "Live" to "Sandbox"
3. Click **Save**

### Verification Checklist

After installation, verify the following:

- [ ] Module appears in module list as "Installed"
- [ ] Module configuration page is accessible
- [ ] All merchant credentials are entered
- [ ] Environment is set correctly
- [ ] Payment method appears at checkout
- [ ] Test payment completes successfully
- [ ] Order status updates after payment
- [ ] Transaction appears in logs

### Security Recommendations

1. **Use HTTPS**: Ensure your PrestaShop store uses HTTPS
2. **Secure Credentials**: Never share merchant credentials
3. **Regular Updates**: Keep the plugin updated
4. **Monitor Logs**: Check logs regularly for errors
5. **Backup Database**: Before installation, backup your database

### File Permissions

After uploading, ensure proper file permissions:

```bash
chmod 755 /modules/payplus/
chmod 644 /modules/payplus/*.php
chmod 755 /modules/payplus/controllers/
chmod 755 /modules/payplus/controllers/front/
chmod 755 /modules/payplus/views/
chmod 755 /modules/payplus/views/templates/
chmod 755 /modules/payplus/classes/
chmod 755 /modules/payplus/translations/
```

### Database Tables

The plugin automatically creates the following table:

```
ps_payplus_transactions
```

This table stores all payment transaction records. Ensure your database has sufficient space.

### Support Resources

- **PrestaShop Documentation**: https://devdocs.prestashop.com/
- **PayPlus Documentation**: Contact PayPlus support
- **PrestaShop Community**: https://www.prestashop.com/forums/

### Next Steps

After successful installation:

1. Read the main README.md for detailed documentation
2. Configure your payment settings
3. Test with sandbox credentials
4. Review security best practices
5. Switch to live credentials when ready

---

**Installation Support**: For installation issues, check the troubleshooting section or contact support.
