# PayPlus Plugin - Quick Start Guide

## 5-Minute Setup

### Step 1: Upload Plugin (1 minute)

1. Download `payplus.zip`
2. Extract to `/modules/payplus/` in your PrestaShop installation
3. Done!

### Step 2: Install in Admin (1 minute)

1. Log in to PrestaShop admin
2. Go to **Modules and Services** → **Modules and Services**
3. Search for "PayPlus"
4. Click **Install**

### Step 3: Configure (2 minutes)

1. Click **Configure** on the PayPlus module
2. Enter your credentials:
   - **Merchant ID**: `your_merchant_id`
   - **Merchant Secret**: `your_secret`
   - **Application Key**: `your_app_key`
3. Select **Environment**: Sandbox (for testing) or Live
4. Click **Save**

### Step 4: Test (1 minute)

1. Create a test order
2. At checkout, select "Pay with PayPlus"
3. Complete the payment
4. Verify order status updates

## Configuration Checklist

- [ ] Module installed
- [ ] Merchant ID entered
- [ ] Merchant Secret entered
- [ ] Application Key entered
- [ ] Environment selected
- [ ] Payment method enabled
- [ ] Test payment successful

## Common Issues

### Payment Option Not Showing?
- Clear cache: **Advanced Parameters** → **Performance** → **Clear Cache**
- Verify payment method is enabled
- Check currency is supported

### Payment Session Failed?
- Verify credentials are correct
- Check if cURL is enabled
- Ensure API endpoint is accessible

### Order Status Not Updating?
- Check PrestaShop logs: **Advanced Parameters** → **Logs**
- Verify callback URL is accessible
- Ensure HTTPS is enabled

## Next Steps

1. **Read Full Documentation**: See `README.md`
2. **Review Installation Guide**: See `INSTALLATION.md`
3. **API Integration**: See `API_DOCUMENTATION.md`
4. **Monitor Transactions**: Check admin logs regularly

## Support

- **Documentation**: See included markdown files
- **PrestaShop Logs**: **Advanced Parameters** → **Logs**
- **PayPlus Support**: Contact PayPlus team

## Environment URLs

**Sandbox**: `https://gateway-api-dev.payplus.lk/api/payment-session/create`

**Live**: `https://gateway-live-api-dev.payplus.lk/api/payment-session/create`

---

**Ready to go live?** Change environment from "Sandbox" to "Live" in module configuration!
