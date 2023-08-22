<?php

/**
 * Manager for Action data adding to Order initialization step.
 */
class Bold_CheckoutSmartwaveOnepageCheckout_Service_QuoteActionManager
{
    const ACTIONS = [
        Bold_CheckoutSmartwaveOnepageCheckout_Service_Action_DiscountLineItem::class,
        Bold_CheckoutSmartwaveOnepageCheckout_Service_Action_AddFeeToCart::class,
    ];

    /**
     * Merge and return all Actions data.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    public static function generateActionsData(Mage_Sales_Model_Quote $quote)
    {
        return array_merge(
            ...array_map(
                function ($action) use ($quote) {
                    /** @var Bold_Checkout_Service_Action_QuoteActionInterface $action */
                    return $action::generateActionData($quote);
                },
                self::ACTIONS
            )
        );
    }
}
