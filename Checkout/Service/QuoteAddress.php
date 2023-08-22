<?php

/**
 * Update Quote Address data service.
 *
 * phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 * phpcs:disable Zend.NamingConventions.ValidVariableName.ContainsNumbers
 */
class Bold_Checkout_Service_QuoteAddress
{
    /**
     * @var string[]
     */
    private static $requiredFields = [
        'city',
        'country_code',
        'postal_code',
        'province',
        'province_code',
    ];

    /**
     * Update cart shipping address data.
     *
     * @param stdClass $dataSource
     * @param Mage_Sales_Model_Quote $quote
     * @return void
     * @throws Mage_Core_Exception
     */
    public static function updateShippingAddress(stdClass $dataSource, Mage_Sales_Model_Quote $quote)
    {
        self::update($dataSource, $quote, true);
    }

    /**
     * Update cart billing address data.
     *
     * @param stdClass $dataSource
     * @param Mage_Sales_Model_Quote $quote
     * @return void
     * @throws Mage_Core_Exception
     */
    public static function updateBillingAddress(stdClass $dataSource, Mage_Sales_Model_Quote $quote)
    {
        self::update($dataSource, $quote, false);
    }

    /**
     * Update Quote Address data.
     *
     * @param stdClass $dataSource
     * @param Mage_Sales_Model_Quote $quote
     * @param bool $isShipping
     * @return void
     * @throws Mage_Core_Exception
     */
    private static function update(stdClass $dataSource, Mage_Sales_Model_Quote $quote, $isShipping)
    {
        Bold_Checkout_Service_PayloadValidator::validate($dataSource, self::$requiredFields);
        $address = $isShipping ? $quote->getShippingAddress() : $quote->getBillingAddress();
        $address->setCustomerId($quote->getCustomerId());
        $address->setCity($dataSource->city);
        $address->setPostcode($dataSource->postal_code);
        $countryId = Mage::getModel('directory/country')->loadByCode($dataSource->country_code)->getId();
        /** @var Bold_Checkout_Model_RegionCodeMapper $regionCodeMapper */
        $regionCodeMapper = Mage::getSingleton(Bold_Checkout_Model_RegionCodeMapper::RESOURCE);
        $provinceCode = $regionCodeMapper->getRegionCode($countryId, $dataSource->province_code);
        $region = Mage::getModel('directory/region')->loadByCode(
            $provinceCode,
            $countryId
        );
        $address->setCountryId($countryId);
        $address->setRegionId($region->getId());
        $address->setRegion($region->getCode());
        isset($dataSource->address_type) && $address->setAddressType($dataSource->address_type);
        isset($dataSource->company) && $address->setCompany($dataSource->company);
        isset($dataSource->first_name) && $address->setFirstname($dataSource->first_name);
        isset($dataSource->last_name) && $address->setLastname($dataSource->last_name);
        isset($dataSource->email) && $address->setEmail($dataSource->email);
        isset($dataSource->phone) && $address->setTelephone($dataSource->phone);
        isset($dataSource->address) && $address->setStreet($dataSource->address);
        if (isset($dataSource->street_1)) {
            $street = $dataSource->street_2 ? [$dataSource->street_1, $dataSource->street_2] : $dataSource->street_1;
            $address->setStreet($street);
        }
        if (isset($dataSource->address1)) {
            $street = $dataSource->address2 ? [$dataSource->address1, $dataSource->address2] : $dataSource->address1;
            $address->setStreet($street);
        }
        if ($isShipping) {
            $address->setCollectShippingRates(true);
        }
    }
}
