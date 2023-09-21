<?php

class Bold_Checkout_Service_Extractor_Order_ShippingAssignmentItem
{
    public static function extract(Mage_Sales_Model_Order $order)
    {
        $shippingAssignmentItems = [];
        /** @var Mage_Sales_Model_Order_Item $orderItem */
        foreach ($order->getAllItems() as $orderItem) {
            if ($orderItem->getChildrenItems()) {
                continue;
            }
            $shippingAssignmentItems[] = self::extractItem($orderItem);
        }
        return $shippingAssignmentItems;
    }

    private static function extractItem(Mage_Sales_Model_Order_Item $orderItem)
    {
        return [
            'amount_refunded' => Mage::app()->getStore()->roundPrice($orderItem->getAmountRefunded()),
            'base_amount_refunded' => Mage::app()->getStore()->roundPrice($orderItem->getBaseAmountRefunded()),
            'base_discount_amount' => Mage::app()->getStore()->roundPrice($orderItem->getBaseDiscountAmount()),
            'base_discount_invoiced' => Mage::app()->getStore()->roundPrice($orderItem->getBaseDiscountInvoiced()),
            'base_discount_tax_compensation_amount' => 0,
            'base_original_price' => Mage::app()->getStore()->roundPrice($orderItem->getBaseOriginalPrice()),
            'base_price' => Mage::app()->getStore()->roundPrice($orderItem->getBasePrice()),
            'base_price_incl_tax' => Mage::app()->getStore()->roundPrice($orderItem->getBasePriceInclTax()),
            'base_row_invoiced' => Mage::app()->getStore()->roundPrice($orderItem->getBaseRowInvoiced()),
            'base_row_total' => Mage::app()->getStore()->roundPrice($orderItem->getBaseRowTotal()),
            'base_row_total_incl_tax' => Mage::app()->getStore()->roundPrice($orderItem->getBaseRowTotalInclTax()),
            'base_tax_amount' => Mage::app()->getStore()->roundPrice($orderItem->getBaseTaxAmount()),
            'base_tax_invoiced' => Mage::app()->getStore()->roundPrice($orderItem->getBaseTaxInvoiced()),
            'created_at' => $orderItem->getCreatedAt(),
            'discount_amount' => Mage::app()->getStore()->roundPrice($orderItem->getDiscountAmount()),
            'discount_invoiced' => Mage::app()->getStore()->roundPrice($orderItem->getDiscountInvoiced()),
            'discount_percent' => Mage::app()->getStore()->roundPrice($orderItem->getDiscountPercent()),
            'free_shipping' => (int)$orderItem->getFreeShipping(),
            'discount_tax_compensation_amount' => 0,
            'is_qty_decimal' => (int)$orderItem->getIsQtyDecimal(),
            'is_virtual' => (int)$orderItem->getIsVirtual(),
            'item_id' => (int)$orderItem->getId(),
            'name' => $orderItem->getName(),
            'no_discount' => (int)$orderItem->getNoDiscount(),
            'order_id' => (int)$orderItem->getOrderId(),
            'original_price' => Mage::app()->getStore()->roundPrice($orderItem->getOriginalPrice()),
            'price' => Mage::app()->getStore()->roundPrice($orderItem->getPrice()),
            'price_incl_tax' => Mage::app()->getStore()->roundPrice($orderItem->getPriceInclTax()),
            'product_id' => (int)$orderItem->getProductId(),
            'product_type' => $orderItem->getProductType(),
            'qty_canceled' => (float)$orderItem->getQtyCanceled(),
            'qty_invoiced' => (float)$orderItem->getQtyInvoiced(),
            'qty_ordered' => (float)$orderItem->getQtyOrdered(),
            'qty_refunded' => (float)$orderItem->getQtyRefunded(),
            'qty_shipped' => (float)$orderItem->getQtyShipped(),
            'quote_item_id' => (int)$orderItem->getQuoteItemId(),
            'row_invoiced' => Mage::app()->getStore()->roundPrice($orderItem->getRowInvoiced()),
            'row_total' => Mage::app()->getStore()->roundPrice($orderItem->getRowTotal()),
            'row_total_incl_tax' => Mage::app()->getStore()->roundPrice($orderItem->getRowTotalInclTax()),
            'row_weight' => (float)$orderItem->getRowWeight(),
            'sku' => $orderItem->getSku(),
            'store_id' => (int)$orderItem->getStoreId(),
            'tax_amount' => Mage::app()->getStore()->roundPrice($orderItem->getTaxAmount()),
            'tax_invoiced' => Mage::app()->getStore()->roundPrice($orderItem->getTaxInvoiced()),
            'tax_percent' => (float)$orderItem->getTaxPercent(),
            'updated_at' => $orderItem->getUpdatedAt(),
        ];
    }
}
