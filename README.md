# FastSpring Unified

A WordPress/WooCommerce plugin that provides FastSpring payment gateway integration with support for multiple payment methods. With this you can "Split Your Fastspring From One Option into Multiple Options"

## Description

FastSpring Unified is a WooCommerce payment gateway plugin that integrates with [FastSpring](https://fastspring.com), a complete e-commerce platform. This plugin splits the FastSpring payment gateway into separate payment methods, giving you more control over how each payment option appears at checkout.

### Features

- **Multiple Payment Methods**: Separate gateways for:
  - PayPal
  - Credit Card
  - Amazon Pay
  - Google Pay
  - Wire Transfer

- **WooCommerce Subscriptions Support**: Full support for subscription lifecycle events including:
  - Subscription activation
  - Subscription cancellation
  - Subscription suspension
  - Subscription reactivation
  - Subscription amount changes
  - Subscription date changes
  - Multiple subscriptions

- **WooCommerce Blocks Integration**: Full support for WooCommerce Block Checkout

- **Customizable Checkout**: Options to remove billing address fields from checkout

- **Test Mode**: Built-in sandbox testing environment

- **Logging**: Debug logging capability for troubleshooting

## Requirements

- WordPress 6.0+
- WooCommerce 3.0.0+
- PHP 8.0+
- SSL Certificate (required for live transactions)

## Installation

1. Download the plugin zip file
2. Go to WordPress Admin > Plugins > Add New > Upload Plugin
3. Upload the zip file and activate
4. Go to WooCommerce > Settings > Payments
5. Configure your FastSpring settings

## Configuration

### Required Settings

1. **Storefront Path**: Your FastSpring storefront URL (e.g., `mystore.onfastspring.com/mystore`)
2. **Access Key**: Your FastSpring access key
3. **Private Key**: Your RSA private key

### Optional Settings

- **Test Mode**: Enable to test transactions without processing real payments
- **Payment Icons**: Select which payment method icons to display
- **Billing Address**: Option to remove billing address fields
- **Logging**: Enable debug logging

## Support

For support inquiries, please contact your FastSpring account representative or visit the [FastSpring documentation](https://docs.fastspring.com).

## Version

2.2.5
