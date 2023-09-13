<?php

/**
 * Order item entity to array extract service.
 */
class Bold_Checkout_Service_Extractor_Order_Item
{
    /**
     * Extract order item entity data into array.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public static function extract(Mage_Sales_Model_Order $order)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getModel(Bold_Checkout_Model_Config::RESOURCE);
        $lineItems = [];
        /** @var Mage_Sales_Model_Order_Item $orderItem */
        foreach ($order->getAllItems() as $orderItem) {
            if ($orderItem->getChildrenItems()) {
                continue;
            }
            $lineItems[] = self::extractItem($orderItem, $config);
        }
        return $lineItems;
    }

    /**
     * Extract order item data.
     *
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @param Bold_Checkout_Model_Config $config
     * @return array
     */
    private static function extractItem(Mage_Sales_Model_Order_Item $orderItem, Bold_Checkout_Model_Config $config)
    {
        $product = $orderItem->getProduct() ?: Mage::getModel('catalog/product')->load($orderItem->getProductId());
        return [
            'applied_rule_ids' => (string)$orderItem->getAppliedRuleIds(),
            'base_discount_amount' => (float)$orderItem->getBaseDiscountAmount(),
            'base_discount_tax_compensation_amount' => (float)$orderItem->getBaseDiscountTaxCompensationAmount(),
            'base_original_price' => (float)$orderItem->getBaseOriginalPrice(),
            'base_price' => (float)$orderItem->getBasePrice(),
            'base_price_incl_tax' => (float)$orderItem->getBasePriceInclTax(),
            'base_row_total' => (float)$orderItem->getBaseRowTotal(),
            'base_row_total_incl_tax' => (float)$orderItem->getBaseRowTotalInclTax(),
            'base_tax_amount' => (float)$orderItem->getBaseTaxAmount(),
            'created_at' => $orderItem->getCreatedAt(),
            'discount_amount' => (float)$orderItem->getDiscountAmount(),
            'discount_percent' => (float)$orderItem->getDiscountPercent(),
            'free_shipping' => (int)$orderItem->getFreeShipping(),
            'discount_tax_compensation_amount' => 0,
            'is_qty_decimal' => (int)$orderItem->getIsQtyDecimal(),
            'is_virtual' => (int)$orderItem->getIsVirtual(),
            'item_id' => (int)$orderItem->getId(),
            'name' => $orderItem->getName(),
            'order_id' => (int)$orderItem->getOrderId(),
            'original_price' => (float)$orderItem->getOriginalPrice(),
            'price' => (float)$orderItem->getPrice(),
            'price_incl_tax' => (float)$orderItem->getPriceInclTax(),
            'product_id' => (int)$orderItem->getProductId(),
            'product_type' => $orderItem->getProductType(),
            'qty_ordered' => (float)$orderItem->getQtyOrdered(),
            'quote_item_id' => (int)$orderItem->getQuoteItemId(),
            'row_total' => (float)$orderItem->getRowTotal(),
            'row_total_incl_tax' => (float)$orderItem->getRowTotalInclTax(),
            'row_weight' => (float)$orderItem->getRowWeight(),
            'sku' => (string)$orderItem->getSku(),
            'store_id' => (int)$orderItem->getStoreId(),
            'tax_amount' => (float)$orderItem->getTaxAmount(),
            'tax_percent' => (float)$orderItem->getTaxPercent(),
            'updated_at' => $orderItem->getUpdatedAt(),
            'extension_attributes' => [
                'product' => current(Bold_Checkout_Service_Extractor_Product::extract([$product])),
            ],
        ];
    }
}
