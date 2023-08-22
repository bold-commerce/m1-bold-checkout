<?php

/**
 * Hydrate customer address data service.
 *
 * phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 * phpcs:disable Zend.NamingConventions.ValidVariableName.ContainsNumbers
 */
class Bold_Checkout_Service_Hydrator_CustomerAddress
{
    /**
     * @var string[]
     */
    private static $requiredFields = [
        'company',
        'city',
        'country',
        'country_code',
        'first_name',
        'last_name',
        'phone',
        'postal_code',
        'province_code',
        'province',
        'street_1',
        'street_2',
    ];

    /**
     * Hydrate customer address data.
     *
     * @param stdClass $dataSource
     * @return Mage_Customer_Model_Address
     * @throws Mage_Core_Exception
     */
    public static function hydrate(stdClass $dataSource)
    {
        Bold_Checkout_Service_PayloadValidator::validate($dataSource, self::$requiredFields);
        /** @var Bold_Checkout_Model_RegionCodeMapper $regionCodeMapper */
        $regionCodeMapper = Mage::getSingleton(Bold_Checkout_Model_RegionCodeMapper::RESOURCE);
        $regionCode = $regionCodeMapper->getRegionCode($dataSource->country_code, $dataSource->province_code);
        /** @var Mage_Customer_Model_Address $customerAddress */
        $customerAddress = Mage::getModel('customer/address');
        $customerAddress->setCompany($dataSource->company);
        $customerAddress->setCity($dataSource->city);
        $customerAddress->setCountry($dataSource->country);
        $customerAddress->setCountryId($dataSource->country_code);
        $customerAddress->setFirstname($dataSource->first_name);
        $customerAddress->setLastname($dataSource->last_name);
        $customerAddress->setTelephone($dataSource->phone);
        $customerAddress->setPostcode($dataSource->postal_code);
        $customerAddress->setRegion($dataSource->province);
        $customerAddress->setRegionId(
            Bold_Checkout_Service_DirectoryRegion::getRegionId($dataSource->country_code, $regionCode)
        );
        $street = $dataSource->street_2 ? [$dataSource->street_1, $dataSource->street_2] : $dataSource->street_1;
        $customerAddress->setStreet($street);
        if (!isset($dataSource->address_use)) {
            return $customerAddress;
        }
        $dataSource->address_use === 'shipping' && $customerAddress->setIsDefaultShipping(true);
        $dataSource->address_use === 'billing' && $customerAddress->setIsDefaultBilling(true);

        return $customerAddress;
    }
}
