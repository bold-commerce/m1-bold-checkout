<?php

/**
 * Manager for Action data adding to Order initialization step.
 */
class Bold_Checkout_Service_QuoteActionManager
{
    const ACTIONS = [
        Bold_Checkout_Service_Action_DiscountLineItem::class,
        Bold_Checkout_Service_Action_ShippingRate::class,
    ];

    /**
     * Merge and return all Actions data.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    public static function generateActionsData(Mage_Sales_Model_Quote $quote)
    {
        return
            array_merge(
                ...array_map(
                    function ($action) use ($quote) {
                        if (is_subclass_of($action, Bold_Checkout_Service_Action_QuoteActionInterface::class)) {
                            return $action::generateActionData($quote);
                        }
                        throw new InvalidArgumentException('Incorrect Action provided.');
                    },
                    self::ACTIONS
                )
            );
    }
}
