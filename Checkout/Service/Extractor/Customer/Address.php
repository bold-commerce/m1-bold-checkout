<?php

/**
 * Customer address entity to array extract service.
 */
class Bold_Checkout_Service_Extractor_Customer_Address
{
    /**
     * Extract customer address entity data into array.
     *
     * @param Mage_Customer_Model_Address $address
     * @return array
     */
    public static function extract(Mage_Customer_Model_Address $address)
    {
        /** @var Bold_Checkout_Model_RegionCodeMapper $regionCodeMapper */
        $regionCodeMapper = Mage::getSingleton(Bold_Checkout_Model_RegionCodeMapper::RESOURCE);
        $provinceCode = $regionCodeMapper->getIsoCode($address->getCountry(), $address->getRegionCode());
        return [
            'platform_id' => (string)$address->getId(),
            'company' => (string)$address->getCompany(),
            'country_code' => (string)$address->getCountryId(),
            'country' => (string)$address->getCountry(),
            'city' => (string)$address->getCity(),
            'first_name' => (string)$address->getFirstname(),
            'last_name' => (string)$address->getLastname(),
            'phone' => (string)$address->getTelephone(),
            'postal_code' => (string)$address->getPostcode(),
            'province' => (string)$address->getRegion(),
            'province_code' => $provinceCode,
            'street_1' => (string)$address->getStreet1(),
            'street_2' => (string)$address->getStreet2(),
            'is_default' => $address->getCustomer()->getData('default_billing') === $address->getId()
                || $address->getCustomer()->getData('default_shipping') === $address->getId(),
            'address_type' => 'residential',
        ];
    }
}
