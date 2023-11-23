<?php

/**
 * "Bold Checkout Template Type" system configuration source class.
 */
class Bold_CheckoutSelfHosted_Model_System_Config_Source_Template_Type
{
    const VALUE_TYPE_ONE_PAGE = 'one_page';
    const VALUE_TYPE_THREE_PAGE = 'three_page';

    /**
     * Get template types options.
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('core');
        return [
            ['value' => self::VALUE_TYPE_THREE_PAGE, 'label' => $helper->__('Three Page (Default)')],
            ['value' => self::VALUE_TYPE_ONE_PAGE, 'label' => $helper->__('One Page')],
        ];
    }
}
