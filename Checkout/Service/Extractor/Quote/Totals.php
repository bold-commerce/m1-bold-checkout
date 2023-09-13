<?php

/**
 * Quote totals extract service.
 */
class Bold_Checkout_Service_Extractor_Quote_Totals
{
    /**
     * Extract quote totals.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public static function extract(Mage_Sales_Model_Quote $quote)
    {
        $discount = isset($quote->getTotals()['discount'])
            ? (float)$quote->getTotals()['discount']->getValue()
            : 0;
        $baseDiscount = isset($quote->getTotals()['discount'])
            ? (float)$quote->getTotals()['discount']->getBaseValue()
            : 0;
        $totals =  [
            'grand_total' => (float)$quote->getGrandTotal(),
            'base_grand_total' => (float)$quote->getBaseGrandTotal(),
            'subtotal' => (float)$quote->getSubtotal(),
            'base_subtotal' => (float)$quote->getBaseSubtotal(),
            'discount_amount' => $discount,
            'base_discount_amount' => $baseDiscount,
            'subtotal_with_discount' => (float)$quote->getSubtotalWithDiscount(),
            'base_subtotal_with_discount' => (float)$quote->getBaseSubtotalWithDiscount(),
            'shipping_amount' => (float)$quote->getShippingAddress()->getShippingAmount(),
            'base_shipping_amount' => (float)$quote->getShippingAddress()->getBaseShippingAmount(),
            'shipping_discount_amount' => (float)$quote->getShippingAddress()->getShippingDiscountAmount(),
            'base_shipping_discount_amount' => (float)$quote->getShippingAddress()->getBaseShippingDiscountAmount(),
            'tax_amount' => (float)$quote->getShippingAddress()->getTaxAmount(),
            'base_tax_amount' => (float)$quote->getShippingAddress()->getBaseTaxAmount(),
            'weee_tax_applied_amount' => (float)$quote->getShippingAddress()->getWeeeTaxAppliedAmount(),
            'shipping_tax_amount' => (float)$quote->getShippingAddress()->getShippingTaxAmount(),
            'base_shipping_tax_amount' => (float)$quote->getShippingAddress()->getBaseShippingTaxAmount(),
            'subtotal_incl_tax' => (float)$quote->getShippingAddress()->getSubtotalInclTax(),
            'base_subtotal_incl_tax' => (float)$quote->getShippingAddress()->getBaseSubtotalTotalInclTax(),
            'shipping_incl_tax' => (float)$quote->getShippingAddress()->getShippingInclTax(),
            'base_shipping_incl_tax' => (float)$quote->getShippingAddress()->getBaseShippingInclTax(),
            'base_currency_code' => $quote->getBaseCurrencyCode(),
            'quote_currency_code' => $quote->getQuoteCurrencyCode(),
            'items_qty' => $quote->getItemsQty(),
            'items' => Bold_Checkout_Service_Extractor_Quote_Totals_Item::extract($quote),
            'total_segments' => Bold_Checkout_Service_Extractor_Quote_Totals_Segment::extract($quote),
        ];
        if ($quote->getCouponCode()) {
            $totals['coupon_code'] = $quote->getCouponCode();
        }
        return $totals;
    }
}
