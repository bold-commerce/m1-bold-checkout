<?php

/**
 * Product custom option formatter.
 */
class Bold_CheckoutCLSCustom_Model_Rewrite_Option_Formatter extends Bold_Checkout_Model_Option_Formatter
{
    /**
     * Format option value.
     *
     * @param array $option
     * @return string
     */
    public function format(array $option)
    {
        $formattedOptionValue = Mage::helper('cls_custom/personalizer')->getFormattedOptionValue(
            $option,
            ['max_length' => 55]
        );

        return isset($formattedOptionValue['value']) ? $formattedOptionValue['value'] : '';
    }
}
