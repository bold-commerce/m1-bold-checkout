<?php

/**
 * Initialize order on Bold side service.
 */
class Bold_Checkout_Api_Bold_Orders_BoldOrder
{
    const ORDER_INIT_URL = '/checkout/orders/{{shopId}}/init';

    /**
     * Initialize order on bold side.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return stdClass
     * @throws Exception
     */
    public static function init(Mage_Sales_Model_Quote $quote)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $lineItems = self::getLineItems($quote);
        $body = [
            'cart_items' => Bold_Checkout_Service_Extractor_Quote_Item::extract($lineItems),
        ];
        if (!$config->isCheckoutTypeSelfHosted($quote->getStore()->getWebsiteId())) {
            $body['actions'] = Bold_Checkout_Service_QuoteActionManager::generateActionsData($quote);
        }
        if ($quote->getCustomer()->getId()) {
            $body = self::addCustomerData($quote, $body);
        }
        $orderData = json_decode(
            Bold_Checkout_Service::call(
                'POST',
                self::ORDER_INIT_URL,
                $quote->getStore()->getWebsiteId(),
                json_encode($body)
            )
        );
        if (!isset($orderData->data->public_order_id)) {
            Mage::throwException('Cannot initialize order, quote id ' . $quote->getId());
        }
        if ($quote->getCustomer()->getId() && !isset($orderData->data->application_state->customer->public_id)) {
            Mage::throwException('Cannot authenticate customer, customer id ' . $quote->getCustomerId());
        }
        return $orderData;
    }

    /**
     * Retrieve customer addresses.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return Mage_Directory_Model_Country[]
     */
    private static function getCustomerCountries(Mage_Sales_Model_Quote $quote)
    {
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
     * Add customer data to order init body.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param array $body
     * @return array
     */
    private static function addCustomerData(Mage_Sales_Model_Quote $quote, array $body)
    {
        $customerAddresses = [];
        $countries = self::getCustomerCountries($quote);
        /** @var Bold_Checkout_Model_RegionCodeMapper $regionCodeMapper */
        $regionCodeMapper = Mage::getSingleton(Bold_Checkout_Model_RegionCodeMapper::RESOURCE);
        foreach ($quote->getCustomer()->getAddresses() as $address) {
            $provinceCode = $regionCodeMapper->getIsoCode($address->getCountryId(), $address->getRegionCode());
            $customerAddresses[] = [
                'id' => (int)$address->getId(),
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
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($quote->getCustomer());
        $body['customer'] = [
            'platform_id' => (string)$quote->getCustomerId(),
            'first_name' => (string)$quote->getCustomerFirstname(),
            'last_name' => (string)$quote->getCustomerLastname(),
            'email_address' => (string)$quote->getCustomerEmail(),
            'accepts_marketing' => (bool)$subscriber->isSubscribed(),
            'saved_addresses' => $customerAddresses,
        ];
        return $body;
    }

    /**
     * Extract line items from the cart.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    private static function getLineItems(Mage_Sales_Model_Quote $quote)
    {
        $cartItems = $quote->getAllItems();
        $lineItems = [];
        foreach ($cartItems as $cartItem) {
            if ($cartItem->getChildren()) {
                continue;
            }
            $lineItems[] = $cartItem;
        }
        return $lineItems;
    }
}
