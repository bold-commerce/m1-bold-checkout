<?php

/**
 * Customer address entity to array extract service.
 */
class Bold_Checkout_Service_Extractor_Quote_Address
{
    /**
     * Extract customer address entity data into array.
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return array
     */
    public static function extract(Mage_Sales_Model_Quote_Address $address)
    {
        /** @var Bold_Checkout_Model_RegionCodeMapper $regionCodeMapper */
        $regionCodeMapper = Mage::getSingleton(Bold_Checkout_Model_RegionCodeMapper::RESOURCE);
        $provinceCode = $regionCodeMapper->getIsoCode($address->getCountryId(), $address->getRegionCode());
        $province = $address->getRegion();
        if (!$provinceCode && !$province) {
            /** @var Mage_Directory_Model_Country $country */
            $country = Mage::getModel('directory/country');
            $country->loadByCode($address->getCountryId());
            $region = $country->getRegionCollection()->getFirstItem();
            $provinceCode = $regionCodeMapper->getIsoCode($address->getCountryId(), $region->getCode());
            $province = $region->getName();
        }
        return [
            'id' => (int)$address->getId() ?: null,
            'business_name' => (string)$address->getCompany(),
            'country_code' => (string)$address->getCountryId(),
            'country' => (string)$address->getCountryModel()->getName(),
            'city' => (string)$address->getCity(),
            'first_name' => (string)$address->getFirstname(),
            'last_name' => (string)$address->getLastname(),
            'phone_number' => (string)$address->getTelephone(),
            'postal_code' => (string)$address->getPostcode(),
            'province' => (string)$province,
            'province_code' => (string)$provinceCode,
            'address_line_1' => (string)$address->getStreet1(),
            'address_line_2' => (string)$address->getStreet2(),
        ];
    }
}
