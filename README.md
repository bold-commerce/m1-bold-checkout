# Bold Checkout on Magento 1

Bold Checkout is a world-class checkout solution that allows you to maintain PCI compliance on your Magento 1 store without the need to replatform.

Adobe [ended support](https://business.adobe.com/blog/basics/support-magento-1-software-ends-june-30-2020) for their Magento 1.x ecommerce platform on June 30, 2020. Since that date, Magento 1 merchants have been responsible for their own security and fraud compliance.

Bold Checkout provides a flexible solution to allow you to maintain your store security and continue using Magento 1. Bold Checkout on Magento 1 is implemented using a [_platform connector_](https://developer.boldcommerce.com/guides/platform-connector).

This document includes basic installation and configuration instructions, as well as how the platform connector works and debugging solutions.

## Installation

To install Bold Checkout on Magento 1, follow the instructions in the [Bold Help Center](https://support.boldcommerce.com/hc/en-us/articles/16254826518164-Installation-Guide-for-Bold-Checkout).

## Configuration

Basic configuration for the Bold Checkout Integration is located in the Magento admin. Navigate to **System** > **Configuration** > **Sales** > **Checkout** > **Bold Checkout Integration**.

![M1 Bold Checkout Integration Configuration](/.github/M1_Integration_Config.png)

The following list outlines the impacts of each configuration:

1. **Enable Bold Checkout** - If this configuration is set to "Yes" and configuration is saved, then the following is true:
   1. Category, product, and customer entities are automatically synchronized with Bold Checkout when saved on Magento.
   1. [Destinations](https://developer.boldcommerce.com/api/platform-connector-destinations#tag/Platform-Connector-Destinations) for customer, products, and orders are created or updated (in case the Magento base URL was changed).
   1. [Overrides](https://developer.boldcommerce.com/guides/checkout/api-overrides) for shipping, taxes, and discounts are created or updated (in case the store base URL was changed).
   1. [Webhooks](https://developer.boldcommerce.com/guides/checkout/webhooks) for orders are registered.
   1. [Zones](https://developer.boldcommerce.com/guides/getting-started/glossary#zone) for warehouse, shipping, and tax are created in Bold Checkout.
   1. Bold Checkout replaces Magento checkout.
   1. Orders created via Bold Checkout can be invoiced and refunded using Bold Checkout.
1. **API Token** - An encrypted Bold Checkout API token used for all outgoing API calls for Bold Checkout. [Generated in the Bold Account Center](https://support.boldcommerce.com/hc/en-us/articles/15975652843284-Create-an-API-Access-Token-in-Account-Center).
1. **Secret Key** - An encrypted Bold Checkout secret key used for all incoming API calls authorization from Bold Checkout. [Generated in the Bold Account Center](https://support.boldcommerce.com/hc/en-us/articles/15975652843284-Create-an-API-Access-Token-in-Account-Center).

Advanced configuration for the Bold Checkout Integration is located in the Magento admin. Navigate to **System** > **Configuration** > **Sales** > **Checkout** > **Bold Checkout Integration Advanced**.

![M1 Bold Checkout Advanced Integration Configuration](/.github/M1_Advanced_Integration_Config.png)

The following list outlines the impacts of each configuration:

1. **Enabled For** - This configuration limits the Bold Checkout process to certain audiences.
   1. **All** - All customers are redirected to Bold Checkout instead of native Magento checkout.
   1. **Specific IPs** - Only customers with listed IP addresses are redirected to Bold Checkout. All other customers continue to see Magento checkout. This setting is useful for testing Bold Checkout on the store.
   1. **Specific Customers** - Only customers with listed emails are redirected to Bold Checkout. All other customers continue to see Magento checkout. This setting is useful for testing Bold Checkout on the store.
   1. **Percentage of Orders** - The system redirects a specified percentage of customers to Bold Checkout.
1. **Enable Real-Time Synchronization** - Bold strongly recommends setting this value to **Yes** to ensure accurate synchronization.
   1. **Yes** - Categories, customers and products are synchronized with Bold Checkout during the saving process.
   1. **No** - The developer must create and run a cron job in order to synchronize categories, customers, and products with Bold Checkout. A one-minute or longer sync lag can occur.
1. **API URL** - The Bold Checkout API URL. This is where all API calls to Bold Checkout are routed. Do not change without significant reason.
1. **Checkout URL** - The Bold Checkout URL. This is the URL of the store's checkout page. Do not change without significant reason.
1. **Weight Unit** - The weight unit used on your store. This is used during product synchronization to ensure Bold Checkout and Magento 1 measure weight in the same way.
1. **Weight Unit Conversion Rate To Grams** - The conversion rate between your **Weight Unit** and grams. This is used during product synchronization to ensure Bold Checkout and Magento 1 measure weight in the same way.

## How it works

The following steps outline what happens during the checkout process:

1. Customer clicks the **Checkout** button in mini-cart or **Proceed to Checkout** button on the cart page.
1. Magento verifies that the cart can use Bold Checkout.
   1. The cart cannot be used with Bold Checkout in the following scenarios:
      1. Bold/Checkout module is disabled.
      1. Cart is limited by configuration setting "Enabled For" described in the [Configuration Section](#configuration).
      1. There is a bundle product in the cart.
      1. The cart has an item with a decimal quantity.
      1. Magento is configured to calculate taxes based on billing address.
      1. Magento is configured to calculate prices including taxes.
      1. Magento is configured to calculate taxes before applying discounts.
   1. Magento sends an [Initialize Order request](https://developer.boldcommerce.com/api/orders#tag/Orders/operation/InitializeOrder) with all items in the cart and discounts applied to the cart (including discount rules using discount codes).<br>
      **Note:** During checkout initialization, sometimes the following error appears: "There was an error during checkout. Please contact us or try again later. This occurs because some of the products in the cart are not synchronized with Bold Checkout. In this case, re-save the product(s) in the Magento admin. If **"Enable Real-Time Synchronization"** is set to **"No"**, also ensure the cron job is running.
   1. If the customer is authenticated, Magento sends [an authorization request](https://developer.boldcommerce.com/api/orders#tag/Customers/operation/CreateAuthenticatedCustomer) with customer data (email, first name, last name) and available addresses.
   1. Magento redirects the customer to the Bold Checkout page.
1. Bold Checkout triggers an [inventory override](https://developer.boldcommerce.com/guides/checkout/api-overrides#inventory) to verify that the requested product quantity is available.
1. The customer provides all necessary address data on Bold Checkout page and clicks the **Continue to shipping** button.
1. (Optional) If the customer applies a discount on the Bold Checkout page, Bold triggers a [discount override](https://developer.boldcommerce.com/guides/checkout/api-overrides#discount) call.
1. Bold Checkout triggers two override requests to Magento:
   1. [Shipping override](https://developer.boldcommerce.com/guides/checkout/api-overrides#shipping) - to calculate all available shipping methods and prices for the given cart.
   1. [Tax override](https://developer.boldcommerce.com/guides/checkout/api-overrides#tax) - to calculate all available taxes applied for the given cart.
1. The customer is redirected to the shipping methods page. The customer selects their preferred shipping method and clicks **Continue to payment**.
1. The customer is redirected to the payment methods page. The customer selects their preferred payment method and clicks **Complete order**.
1. Bold Checkout triggers an [inventory override](https://developer.boldcommerce.com/guides/checkout/api-overrides#inventory).
1. (Guest customer only) Bold Checkout sends a [List Customers request](https://developer.boldcommerce.com/api/platform-connector#tag/Customers/operation/ListCustomers) filtered by email to the Magento platform connector.
   1. (Guest customer only) If no customer with that email is found, Bold sends a [Create Customer request](https://developer.boldcommerce.com/api/platform-connector#tag/Customers/operation/CreateCustomer) to the Magento platform connector.
   1. (Guest Customer only) Magento creates the customer and sends a [Customer Saved notification](https://developer.boldcommerce.com/api/platform-event-notifications#tag/Customer-Event-Notifications/operation/CustomerSavedEventNotification) to Bold.
1. Bold Checkout sends a [Create Order request](https://developer.boldcommerce.com/api/platform-connector#tag/Orders/operation/CreateOrder) to the Magento platform connector.
   1. Magento verifies that the request payload has all necessary order information.
   1. Magento finds the active customer cart for this checkout session and updates this cart with all necessary data, such as: shipping and billing address, payment method, and customer identifier.
   1. The order is placed using customer cart, the order email is sent to customer, and the cart is disabled.
   1. If **Set up delayed payment capture** is disabled, an invoice is created. Find this setting in the Bold Checkout admin on the **Settings** > **General Settings** > **Checkout Process** page.
   1. The order's total is verified against the Bold payment transaction amount. If the totals are different, a comment with the amount difference information is added to the order.
   1. The created order data is sent back to Bold Checkout.
1. If necessary, Bold Checkout sends an [Update Order request](https://developer.boldcommerce.com/api/platform-connector#tag/Orders/operation/UpdateOrder) to the Magento platform connector.
1. If the order was successfully created on the Magento side, Bold sends the [order/created webhook](https://developer.boldcommerce.com/guides/checkout/webhooks#order-created-webhook) to Magento. Refer to the [webhooks section](#webhooks) for details.

### Overrides

Bold uses overrides to replace native Bold Checkout behavior with Magento behavior. For example, the [shipping override](https://developer.boldcommerce.com/guides/checkout/api-overrides#shipping) replaces Bold's available shipping methods with the Magento shipping methods.

Find more information about overrides on the [API Overrides page](https://developer.boldcommerce.com/guides/checkout/api-overrides).

### Webhooks

[Webhooks](https://developer.boldcommerce.com/guides/checkout/webhooks) are registered at the moment the merchant saves the Bold Checkout Integration configuration.

Currently, the module only uses one webhook: the `order/created` event. This webhook is used for the customer newsletter subscription, and it [should be removed](https://developer.boldcommerce.com/api/checkout-admin#tag/Webhooks/operation/DeleteWebhook) if the customer newsletter subscription data is sent with the order creation call.

### Zones

Zones for warehouse, shipping, and tax are created or updated in Bold Checkout at the moment the merchant saves the Bold Checkout Integration configuration.

These zones are created using data from the Magento 1 admin but are only meant to streamline setup. The information in these zones can be sample data and does not have to reflect an actual tax zone.

## Compatible Magento 1 Extensions

Bold Checkout supports a variety of Magento 1 extensions. Some extensions are automatically compatible, and some require the installation of an extra extension to ensure they work correctly on your store.

For a full list of the compatible extensions, refer to the [Bold Help Center](https://support.boldcommerce.com/hc/en-us/articles/16297581333652-Compatible-Magento-1-Extensions-).

## Debugging

You can find logs about all incoming and outgoing requests from/to Bold Checkout in `system.log`.

If you cannot see some logs, such as product sync requests, the website may not be in developer mode. Navigate to the Magento admin **System** > **Configuration** > **Developer** > **Log Setting** and set **Enabled** to **Yes**.

Some useful queries to verify data within the logs:

1. To get product data from Bold:
   ```sh
   curl --request GET 'https://api.boldcommerce.com/products/v2/shops/{shop_id}/products/pid/{magento_product_id}?deep=true' \
   --header 'Authorization: Bearer {api_token}'
   ```
1. To get customer data from Bold: <br>
   ```sh
   curl --request GET 'https://api.boldcommerce.com/customers/v2/shops/{shop_id}/customers/pid/{magento_customer_id}' \
   --header 'Authorization: Bearer {api_token}'
   ```
1. To get override data from Bold:
   ```sh
   curl --request GET 'https://api.boldcommerce.com/checkout/shop/{shopId}/overrides' \
   --header 'Authorization: Bearer {api_token}'
   ```
1. To get destination data from Bold:
   ```sh
   curl --request GET 'https://api.boldcommerce.com/integrations/v1/shops/{shopId}/platform_connector_destinations' \
   --header 'Authorization: Bearer {api_token}'
   ```
