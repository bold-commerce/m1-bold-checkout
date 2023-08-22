<?php

/**
 * Product custom option formatter.
 */
class Bold_Checkout_Model_Option_Formatter
{
    const MODEL_CLASS = 'bold_checkout/option_formatter';

    /**
     * Format option value.
     *
     * @param array $option
     * @return string
     */
    public function format(array $option)
    {
        $formattedOptionValue = Mage::helper('catalog/product_configuration')->getFormattedOptionValue(
            $option,
            ['max_length' => 55]
        );

        return isset($formattedOptionValue['value']) ? $formattedOptionValue['value'] : '';
    }
}
