<?php

/**
 * Get quote from order line items.
 */
class Bold_Checkout_Service_GetQuoteFromLineItems
{
    /**
     * Retrieve order quote if exists.
     *
     * @param stdClass[] $requestItems
     * @return Mage_Sales_Model_Quote|null
     * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
     */
    public static function getQuote(array $requestItems)
    {
        $quoteId = null;
        foreach ($requestItems as $item) {
            // phpcs:ignore Zend.NamingConventions.ValidVariableName.NotCamelCaps
            $quoteId = isset($item->properties->_quote_id) ? $item->properties->_quote_id : null;
            if ($quoteId) {
                break;
            }
            $quoteId = isset($item->custom_attributes->_quote_id->value)
                ? $item->custom_attributes->_quote_id->value
                : null;
            if ($quoteId) {
                break;
            }
        }
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote');
        $store = self::getStore($requestItems);
        $quote->setStore($store);
        $quote = $quote->loadActive($quoteId);
        $quote->setCollectTotalsFlag(false);
        Mage::dispatchEvent(
            'bold_checkout_get_quote_after',
            [
                'quote' => $quote,
                'request' => $requestItems,
            ]
        );

        return $quote->getId() ? $quote : null;
    }

    /**
     * Retrieve quote store store.
     *
     * @param array $requestItems
     * @return Mage_Core_Model_Store
     */
    private static function getStore(array $requestItems)
    {
        $storeId = null;
        foreach ($requestItems as $item) {
            // phpcs:ignore Zend.NamingConventions.ValidVariableName.NotCamelCaps
            $storeId = isset($item->properties->_store_id) ? $item->properties->_store_id : null;
            if ($storeId) {
                break;
            }
            $storeId = isset($item->custom_attributes->_store_id->value)
                ? $item->custom_attributes->_store_id->value
                : null;
            if ($storeId) {
                break;
            }
        }
        return Mage::app()->getStore($storeId);
    }
}
