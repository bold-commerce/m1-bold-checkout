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
            $lineItems[] = self::extractLineItem($orderItem, $config);
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
    private static function extractLineItem(Mage_Sales_Model_Order_Item $orderItem, Bold_Checkout_Model_Config $config)
    {
        $product = $orderItem->getProduct() ?: Mage::getModel('catalog/product')->load($orderItem->getProductId());
        $websiteId = $orderItem->getOrder()->getStore()->getWebsiteId();
        return [
            'platform_id' => (string)$orderItem->getId(),
            'platform_product_id' => $orderItem->getParentItem()
                ? (string)$orderItem->getParentItem()->getProductId()
                : (string)$orderItem->getProductId(),
            'platform_variant_id' => (string)$orderItem->getProductId(),
            'cart_line_item_platform_id' => (string)$orderItem->getQuoteItemId(),
            'title' => (string)$orderItem->getName(),
            'sku' => (string)$orderItem->getSku(),
            'url' => (string)$product->getProductUrl(),
            'image' => (string)Mage::helper('catalog/image')->init(
                $product,
                'image'
            ),
            'quantity' => (int)$orderItem->getQtyOrdered(),
            'grams' => (float)$orderItem->getWeight() * $config->getWeightConversionRate($websiteId),
            'weight' => (float)$orderItem->getWeight(),
            'weight_unit' => (string)Mage::getStoreConfig('checkout/bold/weight_unit') ?: 'kg',
            'taxable' => (int)$product->getTaxClassId() !== 6,
            'taxes' => self::getItemTaxes($orderItem),
            'requires_shipping' => !$orderItem->getIsVirtual(),
            'price_per_item' => $orderItem->getParentItem()
                ? (string)$orderItem->getParentItem()->getBasePrice()
                : (string)$orderItem->getBasePrice(),
            'discount_per_item' => $orderItem->getParentItem()
                ? (string)($orderItem->getParentItem()->getBaseDiscountAmount()
                    / $orderItem->getQtyOrdered())
                : (string)($orderItem->getBaseDiscountAmount() / $orderItem->getQtyOrdered()),
            'total' => $orderItem->getParentItem()
                ? (string)$orderItem->getParentItem()->getBaseRowTotalInclTax()
                : (string)$orderItem->getBaseRowTotalInclTax(),
            'subtotal' => $orderItem->getParentItem()
                ? (string)$orderItem->getParentItem()->getBaseRowTotal()
                : (string)$orderItem->getBaseRowTotal(),
            'total_tax' => $orderItem->getParentItem()
                ? (string)$orderItem->getParentItem()->getBaseTaxAmount()
                : (string)$orderItem->getBaseTaxAmount(),
            'discounted_subtotal' => $orderItem->getParentItem()
                ? (string)($orderItem->getParentItem()->getBaseRowTotal()
                    - $orderItem->getParentItem()->getBaseDiscountAmount())
                : (string)($orderItem->getBaseRowTotal() - $orderItem->getBaseDiscountAmount()),
        ];
    }

    /**
     * Extract order item taxes data.
     *
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @return array
     */
    private static function getItemTaxes(Mage_Sales_Model_Order_Item $orderItem)
    {
        if (!Mage::getModel('tax/sales_order_tax_item')) {
            return [];
        }
        /** @var Mage_Tax_Model_Resource_Sales_Order_Tax_Item $taxItemResource */
        $taxItemResource = Mage::getModel('tax/sales_order_tax_item')->getResource();
        $appliedTaxes = $taxItemResource->getTaxItemsByItemId($orderItem->getId()) ?: [];
        $result = [];
        foreach ($appliedTaxes as $appliedTax) {
            $result[] = [
                'amount' => $appliedTax['base_amount'],
                'name' => $appliedTax['title'],
                'rate' => $appliedTax['tax_percent'],
                'tag' => '',
            ];
        }

        return $result;
    }
}
