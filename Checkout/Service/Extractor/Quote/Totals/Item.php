<?php

/**
 * Extract quote totals items.
 */
class Bold_Checkout_Service_Extractor_Quote_Totals_Item
{
    /**
     * Extract quote totals items.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public static function extract(Mage_Sales_Model_Quote $quote)
    {
        $items = [];
        foreach ($quote->getAllItems() as $item) {
            if (!Bold_Checkout_Service_Extractor_Quote_Item::shouldAppearInCart($item)) {
                continue;
            }
            $items[] = self::extractTotalsItem($item);
            $newItem = self::extractTotalsItem($item);
            Mage::dispatchEvent('bold_checkout_item_totals_extract_after', ['item' => $newItem, 'quote_item' => $item]);
            $items[] = $newItem;
        }
        return $items;
    }

    /**
     * Extract quote totals item entity data into array.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return stdClass
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function extractTotalsItem(Mage_Sales_Model_Quote_Item $item)
    {
        $lineItem = new stdClass();
        $options = self::extractOptions($item);
        
        $lineItem->item_id = (int)$item->getId();
        $lineItem->price = self::getPrice($item);
        $lineItem->base_price = self::getBasePrice($item);
        $lineItem->qty = $item->getParentItem() ? (int)$item->getParentItem()->getQty() : (int)$item->getQty();
        $lineItem->row_total = self::getRowTotal($item);
        $lineItem->base_row_total = self::getBaseRowTotal($item);
        $lineItem->row_total_with_discount = self::getRowTotalWithDiscount($item);
        $lineItem->tax_amount = self::getTaxAmount($item);
        $lineItem->base_tax_amount = self::getBaseTaxAmount($item);
        $lineItem->tax_percent = self::getTaxPercent($item);
        $lineItem->discount_amount = self::getDiscountAmount($item);
        $lineItem->base_discount_amount = self::getBaseDiscountAmount($item);
        $lineItem->discount_percent = self::getDiscountPercent($item);
        $lineItem->price_incl_tax = self::getPriceIncludingTax($item);
        $lineItem->base_price_incl_tax = self::getBasePriceIncludingTax($item);
        $lineItem->row_total_incl_tax = self::getRowTotalIncludingTax($item);
        $lineItem->base_row_total_incl_tax = self::getBaseRowTotalIncludingTax($item);
        $lineItem->options = $options ? json_encode($options) : json_encode([]);
        $lineItem->weee_tax_applied_amount = self::getWeeeTaxAppliedAmount($item);
        $lineItem->weee_tax_applied = self::getWeeeTaxApplied($item);
        $lineItem->name = $item->getName();

        return $lineItem;
    }

    /**
     * Extract quote item options.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return array
     */
    private static function extractOptions(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        $options = [];
        /** @var Mage_Catalog_Helper_Product_Configuration $helper */
        $helper = Mage::helper('catalog/product_configuration');
        if ($item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            foreach ($helper->getConfigurableOptions($item) as $option) {
                $options[] = [
                    'label' => Mage::helper('core')->escapeHtml($option['label']),
                    'value' => Mage::helper('core')->escapeHtml($option['value']),
                ];
            }
        }
        if ($item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $options = array_merge($options, self::getBundleOptions($item));
        }
        /** @var Bold_Checkout_Model_Option_Formatter $formatter */
        $formatter = Mage::getSingleton(Bold_Checkout_Model_Option_Formatter::MODEL_CLASS);
        foreach ($helper->getCustomOptions($item) as $customOption) {
            $options[] = [
                'label' => Mage::helper('core')->escapeHtml($customOption['label']),
                'value' => $formatter->format($customOption),
            ];
        }
        return $options;
    }

    /**
     * Extract bundle product options.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return array
     */
    private static function getBundleOptions(Mage_Sales_Model_Quote_Item $item)
    {
        $bundleOptions = [];
        /** @var Mage_Bundle_Model_Product_Type $bundleType */
        $bundleType = Mage::getSingleton('bundle/product_type');
        $options = $bundleType->getOptionsCollection($item->getProduct());
        $children = $item->getChildren();
        foreach (array_values($options->getItems()) as $i => $option) {
            $childItem = isset($children[$i]) ? $children[$i] : null;
            if (!$childItem) {
                continue;
            }
            $qty = (int)$childItem->getQty();
            $name = $childItem->getName();
            $bundleOptions[] = [
                'label' => $option->getDefaultTitle(),
                'value' => $qty . 'x' . $name,
            ];
        }
        return $bundleOptions;
    }

    /**
     * Extract quote item price considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function getPrice(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        if ($item->getProductType() === 'bundle') {
            return Mage::app()->getStore()->roundPrice($item->getRowTotal() / $item->getQty());
        }
        return Mage::app()->getStore()->roundPrice($item->getPrice());
    }

    /**
     * Extract quote item base price considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function getBasePrice(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        if ($item->getProductType() === 'bundle') {
            return Mage::app()->getStore()->roundPrice($item->getBaseRowTotal() / $item->getQty());
        }
        return Mage::app()->getStore()->roundPrice($item->getBasePrice());
    }

    /**
     * Extract quote item row total considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function getRowTotal(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return Mage::app()->getStore()->roundPrice($item->getRowTotal());
    }

    /**
     * Extract quote item base row total considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function getBaseRowTotal(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return Mage::app()->getStore()->roundPrice($item->getBaseRowTotal());
    }

    /**
     * Extract quote item row total with discount considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function getRowTotalWithDiscount(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return Mage::app()->getStore()->roundPrice($item->getRowTotalWithDiscount());
    }

    /**
     * Extract quote item tax amount considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function getTaxAmount(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return Mage::app()->getStore()->roundPrice($item->getTaxAmount());
    }

    /**
     * Extract quote item base tax amount considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function getBaseTaxAmount(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return Mage::app()->getStore()->roundPrice($item->getBaseTaxAmount());
    }

    /**
     * Extract quote item tax percent considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     */
    private static function getTaxPercent(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return (float)$item->getTaxPercent();
    }

    /**
     * Extract quote item discount amount considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     */
    private static function getDiscountAmount(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return (float)$item->getDiscountAmount();
    }

    /**
     * Extract quote item base discount amount considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     */
    private static function getBaseDiscountAmount(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return (float)$item->getBaseDiscountAmount();
    }

    /**
     * Extract quote item discount percent considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     */
    private static function getDiscountPercent(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return (float)$item->getDiscountPercent();
    }

    /**
     * Extract quote item price including tax considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function getPriceIncludingTax(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return Mage::app()->getStore()->roundPrice($item->getPriceInclTax());
    }

    /**
     * Extract quote item base price including tax considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function getBasePriceIncludingTax(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return Mage::app()->getStore()->roundPrice($item->getBasePriceInclTax());
    }

    /**
     * Extract quote item row total including tax considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     * @throws Mage_Core_Model_Store_Exception
     */
    public static function getRowTotalIncludingTax(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return Mage::app()->getStore()->roundPrice($item->getRowTotalInclTax());
    }

    /**
     * Extract quote item base row total including tax considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function getBaseRowTotalIncludingTax(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return Mage::app()->getStore()->roundPrice($item->getBaseRowTotalInclTax());
    }

    /**
     * Extract quote item weee tax applied amount considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float|null
     */
    private static function getWeeeTaxAppliedAmount(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return (float)$item->getWeeeTaxAppliedAmount() ?: null;
    }

    /**
     * Extract quote item weee tax applied considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float|null
     */
    private static function getWeeeTaxApplied(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return (float)$item->getWeeeTaxApplied() ?: null;
    }
}
