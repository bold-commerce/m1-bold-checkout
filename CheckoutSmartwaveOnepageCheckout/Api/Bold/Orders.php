<?php

/**
 * Initialize order information on bold side service.
 */
class Bold_CheckoutSmartwaveOnepageCheckout_Api_Bold_Orders
{
    /**
     * Initialize order on bold side.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return stdClass
     * @throws Exception
     */
    public static function init(Mage_Sales_Model_Quote $quote)
    {
        $cartItems = $quote->getAllItems();
        $lineItems = [];
        foreach ($cartItems as $cartItem) {
            if ($cartItem->getChildren()) {
                continue;
            }
            $lineItems[] = $cartItem;
        }
        if (!$cartItems) {
            return null;
        }
        self::saveMultiFeesData($quote);
        $body = [
            'cart_items' => self::getCartItems($lineItems),
            'actions' => Bold_CheckoutSmartwaveOnepageCheckout_Service_QuoteActionManager::generateActionsData($quote),
        ];
        $url = '/checkout/orders/{{shopId}}/init';
        $websiteId = $quote->getStore()->getWebsiteId();
        $orderData = json_decode(Bold_Checkout_Service::call('POST', $url, $websiteId, json_encode($body)));
        if (!isset($orderData->data->public_order_id)) {
            Mage::throwException('Cannot initialize order, quote id ' . $quote->getId());
        }
        if (!$quote->getCustomer()->getId()) {
            return $orderData;
        }
        $authenticateUrl = sprintf(
            '/checkout/orders/{{shopId}}/%s/customer/authenticated',
            $orderData->data->public_order_id
        );
        $customerAddresses = [];
        $countries = self::getCustomerCountries($quote);
        /** @var Bold_Checkout_Model_RegionCodeMapper $regionCodeMapper */
        $regionCodeMapper = Mage::getSingleton(Bold_Checkout_Model_RegionCodeMapper::RESOURCE);
        foreach ($quote->getCustomer()->getAddresses() as $address) {
            $provinceCode = $regionCodeMapper->getIsoCode($address->getCountryId(), $address->getRegionCode());
            $customerAddresses[] = [
                'first_name' => (string)$address->getFirstname(),
                'last_name' => (string)$address->getLastname(),
                'address_line_1' => (string)$address->getStreet1(),
                'address_line_2' => (string)$address->getStreet2(),
                'country' => self::getCountryName($countries, $address),
                'city' => (string)$address->getCity(),
                'province' => (string)$address->getRegion(),
                'country_code' => (string)$address->getCountryId(),
                'province_code' => $provinceCode,
                'postal_code' => (string)$address->getPostcode(),
                'business_name' => (string)$address->getCompany(),
                'phone_number' => (string)$address->getTelephone(),
            ];
        }
        $authenticateBody = [
            'first_name' => (string)$quote->getCustomerFirstname(),
            'last_name' => (string)$quote->getCustomerLastname(),
            'email_address' => (string)$quote->getCustomerEmail(),
            'platform_id' => (string)$quote->getCustomerId(),
            'accepts_marketing' => false,
            'saved_addresses' => $customerAddresses,
        ];
        $authenticateResponse = json_decode(
            Bold_Checkout_Service::call('POST', $authenticateUrl, $websiteId, json_encode($authenticateBody))
        );
        if (!isset($authenticateResponse->data->application_state->customer->public_id)) {
            Mage::throwException('Cannot authenticate customer, customer id ' . $quote->getCustomerId());
        }

        return $orderData;
    }

    /**
     * Update order line items fulfilment status.
     *
     * @param Mage_Sales_Model_Order $order
     * @return void
     * @throws Mage_Core_Exception
     */
    public static function fulfilItems(Mage_Sales_Model_Order $order)
    {
        if ($order->isCanceled()) {
            return;
        }
        $extOrderDta = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE)->load(
            $order->getEntityId(),
            Bold_Checkout_Model_Resource_Order::ORDER_ID
        );
        $publicOrderId = $extOrderDta->getPublicId();
        if (!$publicOrderId) {
            return;
        }
        $url = sprintf('/checkout/orders/{{shopId}}/%s/line_items', $publicOrderId);
        $itemsToFulfill = [];
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllItems() as $item) {
            if ($item->getChildrenItems()) {
                continue;
            }
            $fulfilledQty = self::getFulfilledQty($item);
            if (!$fulfilledQty) {
                continue;
            }
            $itemsToFulfill[] = [
                'line_item_key' => $item->getQuoteItemId(),
                'fulfilled_quantity' => $fulfilledQty,
            ];
        }
        if (!$itemsToFulfill) {
            return;
        }
        $websiteId = Mage::app()->getStore($order->getStoreId())->getWebsiteId();
        $body = ['line_items' => $itemsToFulfill];
        Bold_Checkout_Service::call('PATCH', $url, $websiteId, json_encode($body));
    }

    /**
     * Retrieve invoiced and shipped qty for non-virtual and invoiced qty for virtual items.
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @return int
     */
    private static function getFulfilledQty(Mage_Sales_Model_Order_Item $item)
    {
        $quoteItem = Mage::getModel('sales/quote_item')->load($item->getQuoteItemId());
        $qtyOrdered = (float)$item->getQtyOrdered();
        $qtyShipped = $item->getParentItem() ? $item->getParentItem()->getQtyShipped() : $item->getQtyShipped();
        $qtyInvoiced = $item->getParentItem() ? $item->getParentItem()->getQtyInvoiced() : $item->getQtyInvoiced();
        return (float)$qtyShipped === $qtyOrdered && (float)$qtyInvoiced === $qtyOrdered
            ? (int)$quoteItem->getQty()
            : 0;
    }

    /**
     * Retrieve customer addresses.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return Mage_Directory_Model_Country[]
     */
    private static function getCustomerCountries(Mage_Sales_Model_Quote $quote)
    {
        /** @var Mage_Directory_Model_Resource_Country_Collection $countryCollection */
        $countryCollection = Mage::getModel('directory/country')->getCollection();
        $countryIds = [];
        foreach ($quote->getCustomer()->getAddresses() as $address) {
            $countryIds[] = $address->getCountryId();
        }
        if (!$countryIds) {
            return [];
        }
        return $countryCollection->addFieldToFilter('country_id', $countryIds)->getItems();
    }

    /**
     * Get country name by address country id.
     *
     * @param Mage_Directory_Model_Country[] $countries
     * @param Mage_Customer_Model_Address $address
     * @return string
     */
    private static function getCountryName(array $countries, Mage_Customer_Model_Address $address)
    {
        foreach ($countries as $country) {
            if ($country->getCountryId() === $address->getCountryId()) {
                return $country->getName();
            }
        }
        return 'N/A';
    }

    /**
     * Convert magento quote items into bold cart items.
     *
     * @param array $lineItems
     * @return array
     */
    private static function getCartItems(array $lineItems)
    {
        $result = [];
        foreach ($lineItems as $lineItem) {
            $result[] = self::getCartItem($lineItem);
        }

        return $result;
    }

    /**
     * Convert magento quote item into bold cart item.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return array
     */
    private static function getCartItem(Mage_Sales_Model_Quote_Item $item)
    {
        /** @var Bold_Checkout_Model_Option_Formatter $formatter */
        $formatter = Mage::getSingleton(Bold_Checkout_Model_Option_Formatter::MODEL_CLASS);
        /** @var Mage_Catalog_Helper_Product_Configuration $helper */
        $helper = Mage::helper('catalog/product_configuration');
        $lineItem = [
            'platform_id' => (string)$item->getProduct()->getId(),
            'quantity' => self::extractQuoteItemQuantity($item),
            'line_item_key' => (string)$item->getId(),
            'price_adjustment' => self::calculate($item),
            'line_item_properties' => [
                '_quote_id' => (string)$item->getQuoteId(),
                '_store_id' => (string)$item->getQuote()->getStoreId(),
            ],
        ];
        $item = $item->getParentItem() ?: $item;
        if ($item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            foreach ($helper->getConfigurableOptions($item) as $option) {
                $label = Mage::helper('core')->escapeHtml($option['label']);
                $value = Mage::helper('core')->escapeHtml($option['value']);
                $lineItem['line_item_properties'][$label] = $value;
            }
        }
        foreach ($helper->getCustomOptions($item) as $customOption) {
            $label = Mage::helper('core')->escapeHtml($customOption['label']);
            $lineItem['line_item_properties'][$label] = $formatter->format($customOption);
        }
        return $lineItem;
    }

    /**
     * Get quote item quantity considering product type and qty options.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return int
     */
    private static function extractQuoteItemQuantity(Mage_Sales_Model_Quote_Item $item)
    {
        /** @var Bold_CheckoutSmartwaveOnepageCheckout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $parentItem = $item->getParentItem();
        if ($parentItem) {
            $item = $parentItem;
        }
        $websiteId = $item->getQuote()->getStore()->getWebsiteId();
        $options = $item->getQtyOptions();
        if ($options && $config->isAdaptFractionalPriceEnabled($websiteId)) {
            $option = current($options);
            return (int)$option->getValue();
        }
        return (int)$item->getQty();
    }

    /**
     * Calculate cart item price adjustment.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float|int
     */
    private static function calculate(Mage_Sales_Model_Quote_Item $item)
    {
        /** @var Bold_CheckoutSmartwaveOnepageCheckout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $parentItem = $item->getParentItem();
        $childProduct = $item->getProduct();
        if ($parentItem) {
            $item = $parentItem;
        }
        $options = $item->getQtyOptions();
        $websiteId = $item->getQuote()->getStore()->getWebsiteId();
        if ($options && $config->isAdaptFractionalPriceEnabled($websiteId)) {
            return $item->getBaseRowTotal() * 100;
        }
        $priceAdjustment = $item->getBasePrice() - $childProduct->getPrice();

        return $priceAdjustment * 100;
    }

    /**
     * Save multi fees data for given quote.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return void
     * @throws Exception
     */
    private static function saveMultiFeesData(Mage_Sales_Model_Quote $quote)
    {
        $session = Mage::getSingleton('checkout/session');
        $feesData = $session->getDetailsMultifees();
        /** @var Bold_CheckoutMageWorxMultiFees_Model_Quote_Fees_Data $quoteFeesData */
        $quoteFeesData = Mage::getModel(Bold_CheckoutMageWorxMultiFees_Model_Quote_Fees_Data::RESOURCE);
        $quoteFeesData->load($quote->getId(), Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::QUOTE_ID);
        $quoteFeesData->setQuoteId($quote->getId());
        $quoteFeesData->setFeesData(json_encode($feesData));
        $quoteFeesData->save();
    }
}
