<?php

/**
 * Bold Checkout Integration 'Orders Percentage' source.
 */
class Bold_Checkout_Model_System_Config_Source_Percentage
{
    /**
     * Options to show.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach (range(10, 90, 10) as $value ) {
            $options[$value] = $value . '%';
        }

        return $options;
    }
}
