<?php

/**
 * Customer address convertor.
 */
class Bold_Checkout_Service_Customer_Address_Convertor
{
    /**
     * Create customer address model from customer address data.
     *
     * @param stdClass $addressData
     * @return Mage_Customer_Model_Address
     */
    public static function getAddress(stdClass $addressData)
    {
        /** @var Mage_Customer_Model_Address $address */
        $address = Mage::getModel('customer/address');
        $regionId = isset($addressData->region->region_code)
            ? $addressData->region->region_code
            : null;
        if (!$regionId) {
            $regionId = isset($addressData->region->region_id) ? $addressData->region->region_id : null;
        }
        $countryId = isset($addressData->country_id)
            ? $addressData->country_id
            : null;
        $street1 = isset($addressData->street[0])
            ? $addressData->street[0]
            : null;
        $street2 = isset($addressData->street[1])
            ? $addressData->street[1]
            : null;
        $postcode = isset($addressData->postcode)
            ? $addressData->postcode
            : null;
        $telephone = isset($addressData->telephone)
            ? $addressData->telephone
            : null;
        $city = isset($addressData->city)
            ? $addressData->city
            : null;
        $firstname = isset($addressData->firstname)
            ? $addressData->firstname
            : null;
        $lastname = isset($addressData->lastname)
            ? $addressData->lastname
            : null;
        $address->setRegionId($regionId);
        $address->setCountryId($countryId);
        $address->setStreet([$street1, $street2]);
        $address->setPostcode($postcode);
        $address->setTelephone($telephone);
        $address->setCity($city);
        $address->setFirstname($firstname);
        $address->setLastname($lastname);
        $address->setIsDefaultBilling(isset($addressData->default_billing) ? $addressData->default_billing : false);
        $address->setIsDefaultShipping(isset($addressData->default_shipping) ? $addressData->default_shipping : false);
        return $address;
    }
}
