# Installation:

### Base Checkout module installation.
1. see README.md in Bold/Checkout.
### Bold Checkout Tax Exemption module installation.
1. see README.md in Bold/CheckoutTaxExempt.
### Self hosted with React App module installation.
1. see README.md in Bold/CheckoutSelfHosted.
### In case Magento has CLS_Custom module is installed.
1.  Install Bold/CheckoutCLSCustom. See README.md in Bold/CheckoutCLSCustom.
### In case Magento has Low_Sales module is installed.
1.  Install Bold/CheckoutLowSales.. See README.md in Bold/CheckoutLowSales.
### In case Magento ha MageCoders_PaypalMulticurrency module is installed.
1.  Install Bold/CheckoutMageCodersPaypalMulticurrency. See README.md in Bold/CheckoutMageCodersPaypalMulticurrency.
### In case Magento has MageWorx_MultiFees module is installed.
1.  Install Bold/CheckoutMageWorxMultiFees. See README.md in Bold/CheckoutMageWorxMultiFees.
### In case Magento has Smartwave_OnepageCheckout module is installed.
1. Install Bold/CheckoutMageWorxMultiFees module first.(This dependency will be removed in the future).
2. Install Bold/CheckoutSmartwaveOnepageCheckout. See README.md in Bold/CheckoutSmartwaveOnepageCheckout.
### In case Magento has TM_FireCheckout module is installed.
1. Install Bold/CheckoutTMFireCheckout. See README.md in Bold/CheckoutTMFireCheckout.
### In case Magento has TM_OrderAttachment module is installed.
1. Install Bold/CheckoutTaxExempt first.
2. Install Bold/CheckoutTMOrderAttachment. See README.md in Bold/CheckoutTMOrderAttachment.
    
<h1 id="configuration">Configuration Panel: </h1>
Basic Bold Checkout Integration Configuration located in Magento admin area "System > Configuration > Sales > Checkout > Bold Checkout Integration".
<ul>
    <li>
        "Enable Bold Checkout" - If this configuration is set to "Yes" and configuration is saved
            <ul>
                <li>
                    Category, product, customer entities will be automatically synchronized with Bold checkout in case ones are saved. 
                </li>
                <li>
                    Destinations customer, products and orders will be created or updated(in case Magento base url has been changed). 
                </li>
                <li>
                    Overrides for shipping, taxes and discounts will be created or updated(in case store base url has been changed).
                </li>
                <li>
                    Webhook for orders will be registered.
                </li>
                <li>
                    Magento checkout will be replaced with bold checkout.
                </li>
                <li>
                    Orders created via Bold Checkout can be invoiced, refunded using Bold Payment system.
                </li>
            </ul>
    </li>   
    <li>
        "API Token" - Encrypted Bold Checkout api token used for all outgoing api calls for Bold Checkout. Generated during store creation on Bold side.
    </li>
    <li>
        "Secret Key" - Encrypted Bold Checkout secret key used for all incoming api calls authorization from Bold Checkout. Generated during store creation on Bold side.
    </li>
</ul>

Advanced Bold Checkout Integration Configuration located in Magento admin area "System > Configuration > Sales > Checkout > Bold Checkout Integration Advanced".
<ul>
    <li>
    "Enabled For" - This configuration is responsible for Bold Checkout limitation. 
            <ul>
                <li>
                    If it set for "All" - all customers will be redirected to Bold Checkout instead of native Magento checkout. 
                </li>
                <li>
                    "Specific IPs" - only customers with listed ips will be redirected to Bold Checkout. Other customers will be redirected to native Magento checkout.
                </li>
                <li>
                    "Specific Customers" - only customers with listed emails will be redirected to Bold Checkout. 
                </li>
                <li>
                    "Percentage of Orders" - system will try to redirect to Bold Checkout only specified percentage of carts.
                </li>
            </ul>
    </li>
    <li>
        "Enable Real-Time Synchronization"
            <ul>
                <li>
                    "Yes" - Categories, customers and products will be synchronized with Bold Checkout during saving process.
                </li>
                <li>
                    "No" - <b>Note: Cron jobs should be run.</b> Categories, customers and products will be synchronized with Bold Checkout later by cron job after ones have been saved.
                           1 minute or bigger sync lag can occur.
                </li>
            </ul>
    </li>
    <li>
        "API Url" - Bold Checkout api url. Should not be changed without significant reason.
    </li>
    <li>
        "Checkout Url" - Bold Checkout url. Should not be changed without significant reason.
    </li>
    <li>
        "Weight Unit" - Weight unit used during product synchronization with Bold Checkout.
    </li>
    <li>
        "Weight Unit Conversion Rate To Grams" - Conversion rate to get product weight in grams. Should be changed accordingly to "Weight Unit" configuration. 
        Used for product synchronization with Bold Checkout.
    </li>
</ul>


<h1>Checkout Workflow: </h1>
<ol>
   <li>
      Customer clicks "Checkout" button in mini-cart or "Proceed to Checkout" button on the cart page.
      <ol>
         <li>
            Magento verifies the cart for ability to use Bold Checkout.<br>
            Cart cannot be used with Bold Checkout in case:
            <ul>
               <li>
                  Bold/Checkout module is disabled.
               </li>
               <li>
                  Cart is limited by configuration setting "Enabled For" described in <a href="#configuration">Configuration Section</a>.
               </li>
               <li>
                  There is bundle product in the cart.
               </li>
               <li>
                  Cart has item with decimal quantity.
               </li>
               <li>
                  Magento configured to calculate taxes based on billing address.
               </li>
               <li>
                  Magento configured to calculate prices including taxes.
               </li>
               <li>
                  Magento configured to calculate taxes before applying discounts.
               </li>
            </ul>
         </li>
        <li>
            Magento sends <a href="https://developer.boldcommerce.com/default/api/orders#tag/Orders/operation/InitializeOrder">init order request</a> with all items in the cart and discounts applied to the cart including discount rules using discount codes.<br>
            <b>Note: </b>Sometimes "There was an error during checkout. Please contact us or try again later." error may appear during checkout init.
            This may occur due to some of the products in the cart are not synchronized with Bold Checkout.
            In this case product(s) should be re-saved in admin area. If "Enable Real-Time Synchronization" is set to "No", please also make sure the Cron is running.
        </li>
        <li>
            In case customer is not a guest customer Magento sends <a href="https://developer.boldcommerce.com/default/api/orders#tag/Customers/operation/CreateAuthenticatedCustomer">authorization request</a> with customer data(email, first name, last name) and available addresses.
        </li>
        <li>
            Magento redirects customer to Bold Checkout page.
        </li>
      </ol>
    </li>
    <li>
         Bold checkout calls <a href="https://developer.boldcommerce.com/default/guides/checkout/api-overrides#inventory">inventory override</a>(Overrides are described in <a href="#overrides">Overrides Section</a>) to verify that requested product qty is available.
    </li>
    <li>
      Customer fills all necessary address data on Bold Checkout page and clicks "Continue to Shipping Methods" button.
    </li>
    <li>
        Optional: In case customer applies discount code on Bold Checkout Page, <a href="https://developer.boldcommerce.com/default/guides/checkout/api-overrides#inventory">discount override</a> is called.
    </li>
    <li>Bold Checkout sends two requests to Magento:
        <ol>
            <li>
                <a href="https://developer.boldcommerce.com/default/guides/checkout/api-overrides#shipping">Shipping override</a> - to calculate all available shipping methods with prices for given cart.
            </li>
            <li>
                <a href="https://developer.boldcommerce.com/default/guides/checkout/api-overrides#inventory">Tax override</a> - to calculate all available taxes applied for given cart.
            </li>
        </ol>
    </li>
    <li>
        Customer is redirected to shipping methods page.
    </li>
    <li>
        Customer selects shipping method and clicks "Continue to Payment Methods".
    </li>
    <li>
        Customer is redirected to Payment Methods Page.
    </li>
    <li>
        Customer selects payment method and clicks "Complete Order".
    </li>
    <li>
        Bold Checkout sends request to Magento <a href="https://developer.boldcommerce.com/default/guides/checkout/api-overrides#inventory">inventory override</a>.
    </li>
    <li>
        (Guest Customer only) Bold Checkout sends get Customer by e-mail request to Magento.
    </li>
    <li>
        (Guest Customer only) If no Customer found by e-mail, Bold sends a create Customer request to Magento.
    </li>
    <li>
        (Guest Customer only) Magento creates the Customer and sends a <a href="https://developer.boldcommerce.com/default/api/platform-event-notifications#tag/Customer-Event-Notifications/operation/CustomerSavedEventNotification">Customer Saved</a> notification to Bold.
    </li>
    <li>
        Bold Checkout sends request to Magento <a href="https://developer.boldcommerce.com/default/api/platform-connector#tag/Orders/operation/CreateOrder">order create</a> destination.
    </li>
    <ol>
        <li>
            Magento verifies request payload has all necessary order information.
        </li>
        <li>
            Magento finds active customer cart for this checkout session and updates this cart with all necessary data like: shipping|billing address, payment method, customer id.
        </li>
        <li>
            Order is placed using customer cart. Order email sent to customer, cart is disabled.
        </li>
        <li>
            In case "Set up delayed payment capture" is disable in Bold Cashier App, invoice is created.
        </li>
        <li>
            Order's total is verified against Bold payment transaction amount. In case totals are different, comment with amount difference information is added to the order.
        </li>
        <li>
            Created order data is sent back to Bold Checkout.
        </li>
    </ol>
    <li>
        Bold Checkout sends update order statuses request to Magento <a href="https://developer.boldcommerce.com/default/api/platform-connector#tag/Orders/operation/UpdateOrder">update order</a> destination.
    </li>
    <li>
        If the Order was successfully created on the Magento side, Bold sends the  Webhook "order/created" request to Magento. Please see the <a href="#webhooks">Webhooks Section</a> for details.
    </li>
</ol>

<h1 id="overrides">Overrides: </h1>
<ul>
    <li>
        <a href="https://developer.boldcommerce.com/default/guides/checkout/api-overrides#shipping">Shipping override</a> - replaces Bold Checkout available shipping methods with Magento shipping methods.
        Receives shipping address data and line items data with cart id from Bold Checkout. 
        Finds active cart by cart id, updates cart shipping address and recalculate cart to get available shipping methods and prices.
        Returns array with available shipping methods.
        Every shipping method has data: shipping method title, shipping method code, shipping method price
    </li>
</ul>

<h1 id="webhooks">Webhooks: </h1>
<p><a href="https://developer.boldcommerce.com/default/guides/checkout/webhooks">Webhooks</a> are registered at the moment of the Bold Checkout Integration configuration save.</p>
<p>Currently module is using only one Webhook, called on `order/created` event. This Webhook is used for the Customer newsletter subscription, and should be removed in case the Customer newsletter subscription data is sent with Order creation call.</p>

<h1>Additional modules</h1>
<ul>
    <li>
        Bold/CheckoutCLSCustom - adapts product options to human readable format on bold checkout page.
        Prevents errors when CLSCustom module tries to distinguish browser on api calls.
    </li>
    <li>
        Bold/CheckoutMageWorxMultiFees - adapts wageworx multi fees for Bold Checkout.
    </li> 
    <li>
        Bold/CheckoutSmartwaveOnepageCheckout - integrates Bold Checkout into Smartwave onepage checkout.
    </li>
</ul>

<h1>Compatibility confirmed with</h1>
<ul>
    <li>Affirm/Affirm</li>
    <li>Affirm/AffirmPromo</li>
    <li>Amasty/Base</li>
    <li>Amasty/Geoip</li>
    <li>Amasty/Orderattr</li>
    <li>Amasty/PaymentDetect</li>
    <li>Amasty/Preorder</li>
    <li>Amasty/Scheckout</li>
    <li>Aoe/CacheCleaner</li>
    <li>Aoe/FilePicker</li>
    <li>Aoe/QuoteCleaner</li>
    <li>Aoe/Scheduler</li>
    <li>Apptrian/ImageOptimizer</li>
    <li>Apptrian/Minify</li>
    <li>Aschroder/SMTPPro</li>
    <li>AW/Afptc</li>
    <li>AW/All</li>
    <li>AW/Core</li>
    <li>AW/Helpdeskultimate</li>
    <li>AW/Pmatch</li>
    <li>AW/Productupdates</li>
    <li>AW/Rma</li>
    <li>AW/Zblocks</li>
    <li>Bitpay/Core</li>
    <li>Bread/BreadCheckout</li>
    <li>Bss/DeleteOrder</li>
    <li>CartFee/Edit</li>
    <li>Ced/Amazon</li>
    <li>Ced/Googleexpress</li>
    <li>Ced/Newegg</li>
    <li>Ced/Neweggb2b</li>
    <li>Ced/Walmart</li>
    <li>Cm/RedisSession</li>
    <li>Collinsharper/Wiretransfer</li>
    <li>CommerceExtensions/GuestToReg</li>
    <li>Dwolla/DwollaPaymentModule</li>
    <li>EmPayTech/GetFinancing</li>
    <li>Ess/M2ePro</li>
    <li>Flurrybox/EnhancedPrivacy</li>
    <li>Gene/Braintree</li>
    <li>GoIvvy/UspsPatch</li>
    <li>Hm/Testimonial</li>
    <li>Inchoo/InvalidatedBlockCacheFix</li>
    <li>Infomodus/Caship</li>
    <li>Infomodus/Dhllabel</li>
    <li>Infomodus/Fedexlabel</li>
    <li>Infomodus/Upsap</li>
    <li>Infomodus/Upslabel</li>
    <li>Itembase/Plugin</li>
    <li>Low/Sales</li>
    <li>LUKA/GoogleAdWords</li>
    <li>Mage/All</li>
    <li>Mage/Api</li>
    <li>Mage/Api2</li>
    <li>Mage/Authorizenet</li>
    <li>Mage/Bundle</li>
    <li>Mage/Captcha</li>
    <li>Mage/Centinel</li>
    <li>Mage/Compiler</li>
    <li>Mage/ConfigurableSwatches</li>
    <li>Mage/Connect</li>
    <li>Mage/CurrencySymbol</li>
    <li>Mage/Downloadable</li>
    <li>Mage/GoogleShopping</li>
    <li>Mage/GoogleShoppingxml</li>
    <li>Mage/ImportExport</li>
    <li>Mage/Oauth</li>
    <li>Mage/PageCache</li>
    <li>Mage/Persistent</li>
    <li>Mage/Weee</li>
    <li>Mage/Widget</li>
    <li>Mage/XmlConnect</li>
    <li>Mageplace/Callforprice</li>
    <li>MagePsycho/Easypathhints</li>
    <li>MageWorx/AccountOrdersStatus</li>
    <li>MageWorx/Adminhtml</li>
    <li>MageWorx/All</li>
    <li>MageWorx/CustomerCredit</li>
    <li>MageWorx/CustomerLocation</li>
    <li>MageWorx/CustomOptions</li>
    <li>MageWorx/CustomPrice</li>
    <li>MageWorx/GeoIP</li>
    <li>MageWorx/InstantCart</li>
    <li>MageWorx/MageBox</li>
    <li>MageWorx/MultiFees</li>
    <li>MageWorx/OrdersBase</li>
    <li>MageWorx/OrdersGrid</li>
    <li>MageWorx/SeoAll</li>
    <li>MageWorx/SeoBase</li>
    <li>MageWorx/SeoBreadcrumbs</li>
    <li>MageWorx/SeoCrossLinks</li>
    <li>MageWorx/SeoExtended</li>
    <li>MageWorx/SeoMarkup</li>
    <li>MageWorx/SeoRedirects</li>
    <li>MageWorx/SeoReports</li>
    <li>MageWorx/SeoSuiteUltimate</li>
    <li>MageWorx/SeoXTemplates</li>
    <li>MageWorx/StoreSwitcher</li>
    <li>MageWorx/XSitemap</li>
    <li>Magpleasure/Paypalcurrency</li>
    <li>Maven/Html5uploader</li>
    <li>MDN/ExtensionConflict</li>
    <li>MT/ElasticSearch</li>
    <li>MW/HelpDesk</li>
    <li>OnePica/AvaTax</li>
    <li>Ophirah/Core</li>
    <li>Ophirah/Crmaddon</li>
    <li>Ophirah/CustomProducts</li>
    <li>Ophirah/Qquoteadv</li>
    <li>Ophirah/RequestNotification</li>
    <li>PayItSimple/Payment</li>
    <li>PayTomorrow/PayTomorrow</li>
    <li>Phoenix/Moneybookers</li>
    <li>RocketWeb/All</li>
    <li>RocketWeb/ShoppingFeeds</li>
    <li>SafeMage/Extensions</li>
    <li>SafeMage/ImageOtimization</li>
    <li>SafeMage/ReCaptcha3</li>
    <li>SafeMage/TimelessReindex</li>
    <li>SimpleRelevance/Integration</li>
    <li>Staylime/Backorders</li>
    <li>Staylime/Onsale</li>
    <li>TM/FireCheckout</li>
    <li>Webtex/CustomerGroupsPrice1</li>
    <li>Yireo/CheckoutTester</li>
    <li>Yoast/CanonicalUrl</li>
    <li>Yoast/MetaRobots</li>
    <li>Zendesk/Zendesk</li>
</ul>

<h1>Debugging: </h1>
<p>You can find logs about all income and outcome requests from/to Bold Checkout in system.log.
In case you cannot see some logs, e.g. product sync request - probably website is not in developer mode. 
In this case navigate to Magento admin area <b>System > Configuration > Developer > Log Setting > Enabled = Yes</b></p>
<p>Some of the useful queries to verify data:</p>
<ul>
    <li>
        To get product data on Bold side: <br>
        GET https://api.boldcommerce.com/products/v2/shops/{{shop_id}}/products/pid/{{magento_product_id}}?deep=true <br>
        Headers: <br>
            Authorization: Bearer {{api_token}}
    </li>
    <li>
        To get customer data on Bold side: <br>
        GET https://api.boldcommerce.com/customers/v2/shops/{{shop_id}}/customers/pid/{{magento_customer_id}} <br>
        Headers: <br>
            Authorization: Bearer {{api_token}}
    </li>
    <li>
        To get overrides data on Bold side: <br>
        GET https://api.boldcommerce.com/checkout/shop/{{shopId}}/overrides <br>
        Headers: <br>
            Authorization: Bearer {{api_token}}
    </li>
    <li>
        To get destinations data on Bold side: <br>
        GET https://api.boldcommerce.com/integrations/v1/shops/{{shopId}}/platform_connector_destinations <br>
        Headers: <br>
            Authorization: Bearer {{api_token}}
    </li>
</ul>
