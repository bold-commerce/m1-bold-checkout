<?php

class Bold_Checkout_Service_Extractor_Order_Tax
{
    public static function extractAppliedTaxes(Mage_Sales_Model_Order $order)
    {
        $appliedTaxes = [];
        $fullTaxInfo = $order->getFullTaxInfo();
        foreach ($fullTaxInfo as $tax) {
            $appliedTaxes[] = [
                'code' => $tax['id'],
                'title' => $tax['id'],
                'percent' => $tax['percent'],
                'amount' => (float)$tax['amount'],
                'base_amount' => (float)$tax['base_amount'],
            ];
        }
        return $appliedTaxes;
    }

    public static function extractItemAppliedTaxes(Mage_Sales_Model_Order $order)
    {
        $itemAppliedTaxes = [];
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllItems() as $item) {
            if ($item->getChildrenItems()) {
                continue;
            }
            $itemAppliedTaxes[] = [
                'type' => 'product',
                'item_id' => (int)$item->getId(),
                'applied_taxes' => self::extractItemTaxes($item),
            ];
        }
        return $itemAppliedTaxes;
    }

    private static function extractItemTaxes(Mage_Sales_Model_Order_Item $item)
    {
        /** @var Mage_Tax_Model_Resource_Sales_Order_Tax_Item $taxItemResource */
        $taxItemResource = Mage::getSingleton('tax/sales_order_tax_item')->getResource();
        $appliedTaxes = $taxItemResource->getTaxItemsByItemId($item->getId()) ?: [];
        $result = [];
        foreach ($appliedTaxes as $appliedTax) {
            $result[] = [
                'code' => $appliedTax['title'],
                'title' => $appliedTax['title'],
                'percent' => (float)$appliedTax['percent'],
                'amount' => (float)$appliedTax['base_amount'],
                'base_amount' => (float)$appliedTax['base_amount'],
            ];
        }

        return $result;
    }
}
