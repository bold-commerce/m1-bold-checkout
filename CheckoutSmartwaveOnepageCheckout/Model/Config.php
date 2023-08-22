<?php

/**
 * Bold checkout config extended config.
 */
class Bold_CheckoutSmartwaveOnepageCheckout_Model_Config extends Bold_Checkout_Model_Config
{
    const PATH_ADAPT_FRACTIONAL_PRICE_ENABLED = 'checkout/bold_advanced/adapt_fractional_price';

    /**
     * Get is adapt fractional price feature enabled in config.
     *
     * @param int $websiteId
     * @return bool
     */
    public function isAdaptFractionalPriceEnabled($websiteId)
    {
        return (bool)Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_ADAPT_FRACTIONAL_PRICE_ENABLED);
    }
}
