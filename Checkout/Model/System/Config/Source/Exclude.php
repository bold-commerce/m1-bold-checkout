<?php

/**
 * Bold Checkout Integration 'Exclude Customer Groups' source.
 */
class Bold_Checkout_Model_System_Config_Source_Exclude
{
    /**
     * Options to show.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            Bold_Checkout_Model_Config::VALUE_EXCLUDE_FOR_NONE =>
                Mage::helper('core')->__('None'),
            Bold_Checkout_Model_Config::VALUE_EXCLUDE_FOR_SPECIFIED_GROUPS =>
                Mage::helper('core')->__('Specific Customer Groups'),
        ];
    }
}
