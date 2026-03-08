# FastSpring Unified - Technical Documentation

## Overview

FastSpring Unified is a WooCommerce payment gateway plugin that integrates with FastSpring's e-commerce platform. The plugin provides a unified approach to handling multiple payment methods through FastSpring while giving merchants fine-grained control over each payment option.

## Architecture

### Main Components

#### 1. Main Plugin File (`woocommerce-gateway-fastspring.php`)

- Initializes the plugin
- Defines constants and version
- Manages plugin settings
- Registers payment gateways
- Handles environment checks

#### 2. Main Gateway (`includes/class-wc-gateway-fastspring.php`)

- Base gateway class extending `WC_Payment_Gateway`
- Handles payment processing
- Manages payment icons
- Enqueues necessary scripts

#### 3. Sub-Gateways (`includes/class-fastspring-sub-gateways.php`)

Splits the main gateway into multiple payment methods:

- `WC_Gateway_FastSpring_PayPal` - PayPal payments
- `WC_Gateway_FastSpring_CreditCard` - Credit card payments
- `WC_Gateway_FastSpring_Amazon` - Amazon Pay
- `WC_Gateway_FastSpring_Wire` - Wire transfer
- `WC_Gateway_FastSpring_GooglePay` - Google Pay

#### 4. Settings (`includes/settings-fastspring.php`)

- Configurable form fields for the gateway
- Access credentials configuration
- Webhook and API settings

## Payment Flow

1. Customer selects a FastSpring payment method at checkout
2. Plugin generates a secure JSON payload with order data
3. FastSpring builder script is initialized with the payload
4. Customer completes payment on FastSpring's hosted checkout
5. Webhook or API callback updates the order status in WooCommerce

## Supported Features

### WooCommerce Features

- Products
- Refunds
- Subscriptions (with full lifecycle support)
- Multiple subscriptions per order
- WooCommerce Blocks checkout

### FastSpring Features

- Hosted storefront
- Popup storefront
- Secure payload generation
- Webhook notifications
- API order verification

## Configuration

### Getting FastSpring Credentials

1. Log in to your FastSpring dashboard
2. Go to Integrations > Store Builder Library
3. Generate or upload your RSA key pair
4. Get your Access Key from the dashboard
5. Enter credentials in WooCommerce settings

### Webhook Setup

1. In WooCommerce settings, generate a Webhook Secret
2. Configure webhook URL: `https://yoursite.com/?wc-api=wc_gateway_fastspring`
3. Add the URL and secret in FastSpring dashboard under Integrations > Webhooks

### API Setup (Optional)

1. Generate API credentials in FastSpring dashboard under Integrations > API Credentials
2. Enter API username and password in WooCommerce settings

## Filter Hooks

- `woocommerce_checkout_fields` - Override checkout fields
- `woocommerce_payment_gateways` - Add gateways
- `script_loader_tag` - Modify FastSpring script loading
- `plugin_action_links` - Add plugin settings link
- `woocommerce_gateway_icon` - Modify payment icons
- `woocommerce_fastspring_params` - Modify JS parameters

## Action Hooks

- `woocommerce_fastspring_updated` - Plugin update hook
- `wc_ajax_wc_fastspring_order_complete` - Order completion handler

## Security

- RSA private key encryption for secure payload
- HMAC SHA256 webhook verification
- WordPress nonces for AJAX requests
- Input sanitization on all settings

## File Structure

```
fastspring-unified/
├── woocommerce-gateway-fastspring.php  # Main plugin file
├── README.md                            # Basic readme
├── docs/                                # Documentation
│   └── documentation.md                 # Detailed documentation
├── includes/
│   ├── class-wc-gateway-fastspring.php # Main gateway class
│   ├── class-fastspring-sub-gateways.php # Split gateways
│   ├── class-wc-gateway-fastspring-builder.php
│   ├── class-wc-gateway-fastspring-handler.php
│   └── settings-fastspring.php          # Settings form
├── assets/
│   ├── css/style.css
│   ├── js/
│   │   ├── fastspring-checkout.js
│   │   ├── fastspring-checkout.min.js
│   │   └── fastspring-checkout-integrated.js
│   ├── icons/                           # Payment method icons
│   └── img/                             # Payment method images
└── uninstall.php                        # Cleanup on uninstall
```

## Troubleshooting

### Enable Debug Logging

1. Go to WooCommerce > Settings > Payments > FastSpring
2. Enable "Log debug messages"
3. Check WooCommerce > Status > Logs for messages

### Common Issues

- **Orders not completing**: Check webhook URL is accessible and secret matches
- **Payment not redirecting**: Verify FastSpring script loads correctly
- **SSL errors**: Ensure site has valid SSL certificate

## Version History

- 2.2.5 - Current version
