<?php

/**
 * Customer address entity to guest data array extract service.
 *
 * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 */
class Bold_Checkout_Service_Extractor_Quote_Guest
{
    /**
     * Extract customer address entity data into guest data array.
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return array
     */
    public static function extract(Mage_Sales_Model_Quote_Address $address)
    {
        return [
                'first_name' => (string)$address->getFirstname(),
                'last_name' =>  (string)$address->getLastname(),
                'email_address' =>  (string)$address->getEmail(),
                'accepts_marketing' => false
        ];
    }
}
