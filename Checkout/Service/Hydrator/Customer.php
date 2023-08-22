<?php

/**
 * Hydrate customer data service.
 */
class Bold_Checkout_Service_Hydrator_Customer
{
    /**
     * @var string[]
     */
    private static $requiredFields = [
        'email',
        'first_name',
        'last_name',
        'phone',
        'addresses',
    ];

    /**
     * Hydrate customer data.
     *
     * @param stdClass $dataSource
     * @return Mage_Customer_Model_Customer
     * @throws Mage_Core_Exception
     * phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
     */
    public static function hydrate(stdClass $dataSource)
    {
        Bold_Checkout_Service_PayloadValidator::validate($dataSource, self::$requiredFields);
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        $customer->setEmail($dataSource->email);
        $customer->setFirstname($dataSource->first_name);
        $customer->setLastname($dataSource->last_name);
        $customer->setTelephone($dataSource->phone);
        array_map(
            function ($dataSource) use ($customer) {
                $address = Bold_Checkout_Service_Hydrator_CustomerAddress::hydrate($dataSource);
                $customer->addAddress($address);
            },
            array_unique($dataSource->addresses, SORT_REGULAR)
        );

        return $customer;
    }
}
