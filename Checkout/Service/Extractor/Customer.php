<?php

/**
 * Customer entity to array extract service.
 */
class Bold_Checkout_Service_Extractor_Customer
{
    /**
     * Extract customers data.
     *
     * @param array $customers
     * @return array
     */
    public static function extract(array $customers)
    {
        $result = [];
        foreach ($customers as $customer) {
            $result[] = self::extractCustomer($customer);
        }

        return $result;
    }

    /**
     * Extract customer entity data into array.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     */
    private static function extractCustomer(Mage_Customer_Model_Customer $customer)
    {
        $addresses = [];
        foreach ($customer->getAddresses() as $customerAddress) {
            if ($customerAddress->getCustomer()) {
                $addresses[] = Bold_Checkout_Service_Extractor_Customer_Address::extract($customerAddress);
            }
        }
        $createdAt = strtotime($customer->getCreatedAt()) > 0 ? $customer->getCreatedAt() : now();
        $updatedAt = strtotime($customer->getUpdatedAt()) > 0 ? $customer->getUpdatedAt() : now();
        $customerResult = [
            'id' => (int)$customer->getId(),
            'group_id' => (int)$customer->getGroupId(),
            'default_billing' => $customer->getDefaultBilling(),
            'default_shipping' => $customer->getDefaultShipping(),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'created_in' => $customer->getCreatedIn(),
            'dob' => $customer->getDob(),
            'email' => $customer->getEmail(),
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'middlename' => $customer->getMiddlename(),
            'prefix' => $customer->getPrefix(),
            'suffix' => $customer->getSuffix(),
            'gender' => (int)$customer->getGender(),
            'store_id' => (int)$customer->getStoreId(),
            'taxvat' => $customer->getTaxvat(),
            'website_id' => (int)$customer->getWebsiteId(),
            'disable_auto_group_change' => 0,
        ];
        $customerResult['addresses'] = $addresses;
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);
        if (!$subscriber->getId()) {
            $customerResult['extension_attributes']['is_subscribed'] = false;
            return $customerResult;
        }
        $isSubscribed = $subscriber->getSubscriberStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED;
        $customerResult['extension_attributes']['is_subscribed'] = $isSubscribed;
        return $customerResult;
    }

    /**
     * Extract customer data for order init request.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    public static function extractForOrder(Mage_Sales_Model_Quote $quote)
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
        return [
            'platform_id' => (string)$quote->getCustomerId(),
            'first_name' => (string)$quote->getCustomerFirstname(),
            'last_name' => (string)$quote->getCustomerLastname(),
            'email_address' => (string)$quote->getCustomerEmail(),
            'accepts_marketing' => (bool)$subscriber->isSubscribed(),
            'saved_addresses' => $customerAddresses,
        ];
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
}
