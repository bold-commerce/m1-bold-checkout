<?php

/**
 * Block for parallel checkout handling.
 */
class Bold_Checkout_Block_Parallel extends Mage_Core_Block_Template
{
    const KEY_PARALLEL = 'parallel';

    /**
     * Check if parallel checkout is enabled.
     *
     * @return bool
     */
    public function isParallelCheckoutEnabled()
    {
        $websiteId = Mage::app()->getWebsite()->getId();
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        $renderBlock = new Varien_Object(['result' => true]);
        Mage::dispatchEvent ('bold_checkout_parallel_render_block', ['render' => $renderBlock, 'block' => $this]);
        return $renderBlock->getResult()
            && Bold_Checkout_Service_IsBoldCheckoutAllowedForQuote::isAllowed($quote)
            && $boldConfig->isCheckoutTypeParallel($websiteId);
    }

    /**
     * Check if checkout is disabled by quote.
     *
     * @return bool
     */
    public function isDisabledByQuote()
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        return !$quote->validateMinimumAmount() && $quote->getErrors();
    }

    /**
     * Get parallel checkout url.
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->getUrl('checkout/onepage', ['_secure' => true, self::KEY_PARALLEL => true]);
    }
}
