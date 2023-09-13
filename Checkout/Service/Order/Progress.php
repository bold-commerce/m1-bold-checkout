<?php

/**
 * Create order progress service.
 */
class Bold_Checkout_Service_Order_Progress
{
    /**
     * Check if order create already in progress.
     *
     * @param int $quoteId
     * @return bool
     */
    public static function isInProgress($quoteId)
    {
        return Bold_Checkout_Model_Resource_Order_ProgressResource::getIsInProgress($quoteId);
    }

    /**
     * Start order create progress.
     *
     * @param int $quoteId
     * @return void
     */
    public static function start($quoteId)
    {
        Bold_Checkout_Model_Resource_Order_ProgressResource::create($quoteId);
    }

    /**
     * Stop order create progress.
     *
     * @param int $quoteId
     * @return void
     */
    public static function stop($quoteId)
    {
        Bold_Checkout_Model_Resource_Order_ProgressResource::delete($quoteId);
    }
}
