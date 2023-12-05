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
            [
                'value' => Bold_Checkout_Model_Config::VALUE_TYPE_STANDARD,
                'label' => __('Standard'),
            ],
            [
                'value' => Bold_Checkout_Model_Config::VALUE_TYPE_PARALLEL,
                'label' => __('Dual'),
            ],
            [
                'value' => Bold_Checkout_Model_Config::VALUE_TYPE_SELF,
                'label' => __('Self-Hosted (Magento storefront)'),
            ],
            [
                'value' => Bold_Checkout_Model_Config::VALUE_TYPE_SELF_REACT,
                'label' => __('Self-Hosted (Bold Templates)'),
            ],
        ];
    }
}
