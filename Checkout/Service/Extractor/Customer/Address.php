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
        $street = [
            $address->getStreet1(),
        ];
        if ($address->getStreet2()) {
            $street[] = $address->getStreet2();
        }
        return [
            'id' => (int)$address->getId(),
            'customer_id' => (int)$address->getCustomerId(),
            'region' => [
                'region_code' => $provinceCode,
                'region' => $address->getRegion(),
                'region_id' => (int)$address->getRegionId(),
            ],
            'region_id' => (int)$address->getRegionId(),
            'country_id' => $address->getCountryId(),
            'street' => $street,
            'telephone' => $address->getTelephone(),
            'postcode' => $address->getPostcode(),
            'city' => $address->getCity(),
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'default_shipping' => (bool)$address->getIsDefaultShipping(),
            'default_billing' => (bool)$address->getIsDefaultBilling(),
        ];
    }
}
