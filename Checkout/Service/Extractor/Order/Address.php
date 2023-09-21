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
        $street = [$address->getStreet1()];
        if ($address->getStreet2()) {
            $street[] = $address->getStreet2();
        }
        return [
            'address_type' => $address->getAddressType(),
            'city' => $address->getCity(),
            'country_id' => $address->getCountryId(),
            'email' => $address->getEmail(),
            'entity_id' => (int)$address->getId(),
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'parent_id' => (int)$address->getParentId(),
            'postcode' => $address->getPostcode(),
            'region' => $address->getRegion(),
            'region_code' => $address->getRegionCode(),
            'region_id' => (int)$address->getRegionId(),
            'street' => $street,
            'telephone' => $address->getTelephone(),
        ];
    }
}
