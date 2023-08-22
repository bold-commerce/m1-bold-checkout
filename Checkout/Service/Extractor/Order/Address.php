<?php

/**
 * Order address entity to array extract service.
 */
class Bold_Checkout_Service_Extractor_Order_Address
{
    /**
     * Extract order address entity data into array.
     *
     * @param Mage_Sales_Model_Order_Address $address
     * @return array
     */
    public static function extract(Mage_Sales_Model_Order_Address $address)
    {
        /** @var Bold_Checkout_Model_RegionCodeMapper $regionCodeMapper */
        $regionCodeMapper = Mage::getSingleton(Bold_Checkout_Model_RegionCodeMapper::RESOURCE);
        $provinceCode = $regionCodeMapper->getIsoCode($address->getCountry(), $address->getRegionCode());
        return [
            'platform_id' => (string)$address->getId(),
            'address_type' => (string)$address->getAddressType(),
            'first_name' => (string)$address->getFirstname(),
            'last_name' => (string)$address->getLastname(),
            'street_1' => (string)$address->getStreet1(),
            'street_2' => (string)$address->getStreet2(),
            'city' => (string)$address->getCity(),
            'province' => (string)$address->getRegion(),
            'country' => (string)$address->getCountryModel()->getName(),
            'country_code' => (string)$address->getCountryModel()->getCountryId(),
            'phone' => (string)$address->getTelephone(),
            'postal_code' => (string)$address->getPostcode(),
            'email' => (string)$address->getEmail(),
            'province_code' => $provinceCode,
            'company' => (string)$address->getCompany(),
        ];
    }
}
