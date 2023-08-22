<?php

/**
 * Bold Checkout Integration 'Enable For' source.
 */
class Bold_Checkout_Model_System_Config_Source_Filter
{
    /**
     * Options to show.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            Bold_Checkout_Model_Config::VALUE_ENABLED_FOR_ALL =>
                Mage::helper('core')->__('All'),
            Bold_Checkout_Model_Config::VALUE_ENABLED_FOR_IP =>
                Mage::helper('core')->__('Specific IPs'),
            Bold_Checkout_Model_Config::VALUE_ENABLED_FOR_CUSTOMER =>
                Mage::helper('core')->__('Specific Customers'),
            Bold_Checkout_Model_Config::VALUE_ENABLED_FOR_PERCENTAGE =>
                Mage::helper('core')->__('Percentage Of Orders'),
        ];
    }
}
