<?php

/**
 * Checks if Bold functionality is enabled for specific Quote.
 */
class Bold_Checkout_Service_IsBoldCheckoutAllowedForQuote
{
    /**
     * Checks if Bold functionality is enabled for specific Quote.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     * @throws Mage_Core_Exception
     */
    public static function isAllowed(Mage_Sales_Model_Quote $quote)
    {
        $websiteId = $quote->getStore()->getWebsiteId();
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$boldConfig->isCheckoutEnabled($websiteId)) {
            return false;
        }
        if (!self::isEnabledFor($quote)) {
            return false;
        }
        if (self::isExcludedFor($quote)) {
            return false;
        }
        foreach ($quote->getAllItems() as $item) {
            if ($item->getIsQtyDecimal()) {
                return false;
            }
        }
        return (bool)Mage::getStoreConfigFlag('tax/calculation/apply_after_discount');
    }

    /**
     * Verify quote against "Enabled For" bold checkout config.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     */
    private static function isEnabledFor(Mage_Sales_Model_Quote $quote)
    {
        $websiteId = $quote->getStore()->getWebsiteId();
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        switch ($config->getEnabledFor($websiteId)) {
            case Bold_Checkout_Model_Config::VALUE_ENABLED_FOR_ALL:
                return true;
            case Bold_Checkout_Model_Config::VALUE_ENABLED_FOR_IP:
                return in_array($quote->getRemoteIp(), $config->getIpWhitelist($websiteId));
            case Bold_Checkout_Model_Config::VALUE_ENABLED_FOR_CUSTOMER:
                return in_array($quote->getCustomerEmail(), $config->getCustomerWhitelist($websiteId));
            case Bold_Checkout_Model_Config::VALUE_ENABLED_FOR_PERCENTAGE:
                return self::resolveByPercentage($quote);
            default:
                return false;
        }
    }

    /**
     * Resolve if Bold functionality is enabled for specific Quote by Orders Percentage.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     */
    private static function resolveByPercentage(Mage_Sales_Model_Quote $quote)
    {
        $websiteId = $quote->getStore()->getWebsiteId();
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $quoteId = $quote->getId();
        $percentage = $config->getOrdersPercentage($websiteId);

        return ($quoteId % 10) < ($percentage / 10);
    }

    /**
     * Verify quote against "Excluded For" bold checkout config.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     */
    private static function isExcludedFor(Mage_Sales_Model_Quote $quote)
    {
        $websiteId = $quote->getStore()->getWebsiteId();
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $excludedCustomerGroups = $config->getExcludedCustomerGroups($websiteId);
        $customerGroupId = $quote->getCustomerGroupId();
        return in_array($customerGroupId, $excludedCustomerGroups);
    }
}
