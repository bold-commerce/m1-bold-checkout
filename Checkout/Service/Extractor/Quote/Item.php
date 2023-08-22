<?php

/**
 * Quote item entity to array extract service.
 *
 * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 */
class Bold_Checkout_Service_Extractor_Quote_Item
{
    /**
     * Extract quote items data.
     *
     * @param array $quoteItems
     * @return array
     */
    public static function extract(array $quoteItems)
    {
        $result = [];
        foreach ($quoteItems as $quoteItem) {
            $result[] = self::extractQuoteItem($quoteItem);
        }

        return $result;
    }

    /**
     * Extract quote item entity data into array.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return stdClass
     */
    private static function extractQuoteItem(Mage_Sales_Model_Quote_Item $item)
    {
        /** @var Bold_Checkout_Model_Option_Formatter $formatter */
        $formatter = Mage::getSingleton(Bold_Checkout_Model_Option_Formatter::MODEL_CLASS);
        /** @var Mage_Catalog_Helper_Product_Configuration $helper */
        $helper = Mage::helper('catalog/product_configuration');
        $lineItem = new stdClass();
        $lineItem->platform_id = (string)$item->getProduct()->getId();
        $lineItem->quantity = self::extractQuoteItemQuantity($item);
        $lineItem->line_item_key = (string)$item->getId();
        $lineItem->price_adjustment = self::calculatePriceAdjustment($item);
        $lineItem->line_item_properties = new stdClass();
        $lineItem->line_item_properties->_quote_id = (string)$item->getQuoteId();
        $lineItem->line_item_properties->_store_id = (string)$item->getQuote()->getStoreId();
        $item = $item->getParentItem() ?: $item;
        if ($item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            foreach ($helper->getConfigurableOptions($item) as $option) {
                $label = Mage::helper('core')->escapeHtml($option['label']);
                $value = Mage::helper('core')->escapeHtml($option['value']);
                $lineItem->line_item_properties->$label = $value;
            }
        }
        foreach ($helper->getCustomOptions($item) as $customOption) {
            $label = Mage::helper('core')->escapeHtml($customOption['label']);
            $lineItem->line_item_properties->$label = $formatter->format($customOption);
        }
        Mage::dispatchEvent('bold_checkout_line_item_extract_after', ['line_item' => $lineItem, 'quote_item' => $item]);

        return $lineItem;
    }

    /**
     * Get quote item quantity considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return int
     */
    private static function extractQuoteItemQuantity(Mage_Sales_Model_Quote_Item $item)
    {
        $parentItem = $item->getParentItem();
        if ($parentItem) {
            $item = $parentItem;
        }

        return (int)$item->getQty();
    }

    /**
     * Get quote item discount amount.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     */
    private static function calculatePriceAdjustment(Mage_Sales_Model_Quote_Item $item)
    {
        $parentItem = $item->getParentItem();
        $childProduct = $item->getProduct();
        $baseProductPrice = Mage::app()->getStore()->roundPrice($childProduct->getPrice());
        if ($parentItem) {
            $item = $parentItem;
        }
        $priceIncludesTax = Mage::getStoreConfigFlag('tax/calculation/price_includes_tax', $item->getStoreId());
        $baseItemPrice = $priceIncludesTax
            ? Mage::app()->getStore()->roundPrice($item->getBasePriceInclTax())
            : Mage::app()->getStore()->roundPrice($item->getBasePrice());
        $itemCustomPrice = Mage::app()->getStore()->roundPrice($item->getCustomPrice());
        $itemPrice = $item->getCustomPrice() ? $itemCustomPrice : $baseItemPrice;
        $priceAdjustment = $itemPrice - $baseProductPrice;
        return $priceAdjustment * 100;
    }
}
