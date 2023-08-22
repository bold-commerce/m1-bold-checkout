<?php

/**
 * Quote action generator interface.
 */
interface Bold_Checkout_Service_Action_QuoteActionInterface
{
    /**
     * Generate action.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array[]
     */
    public static function generateActionData(Mage_Sales_Model_Quote $quote);
}
