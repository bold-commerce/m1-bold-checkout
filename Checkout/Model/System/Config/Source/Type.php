<?php

/**
 * Bold Checkout Integration 'Bold Checkout Type' source.
 */
class Bold_Checkout_Model_System_Config_Source_Type
{
    /**
     * Options to show.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            Bold_Checkout_Model_Config::VALUE_TYPE_STANDARD =>
                Mage::helper('core')->__('Standard'),
            Bold_Checkout_Model_Config::VALUE_TYPE_PARALLEL =>
                Mage::helper('core')->__('Parallel'),
            Bold_Checkout_Model_Config::VALUE_TYPE_SELF =>
                Mage::helper('core')->__('Self-Hosted'),
        ];
    }
}
