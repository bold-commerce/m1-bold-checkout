<?php

/**
 * Cart Items discount calculation service.
 */
class Bold_Checkout_Service_Action_DiscountLineItem implements Bold_Checkout_Service_Action_QuoteActionInterface
{
    const TYPE = 'discount_line_items';
    const DISCOUNT_TYPE_FIXED = 'fixed';
    const SOURCE_CART = 'cart';
    const SOURCE_COUPON = 'coupon';

    /**
     * @var array
     */
    private static $discounts = [];

    /**
     * Calculate Cart Items discounts before order initialization.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array[]
     */
    public static function generateActionData(Mage_Sales_Model_Quote $quote)
    {
        $couponCode = $quote->getCouponCode();
        if ($couponCode) {
            $quote->setCouponCode('');
            $quote->collectTotals();
        }
        $lineItems = self::getLineItems($quote);
        $itemsData = [];
        foreach ($lineItems as $lineItem) {
            self::$discounts[$lineItem->getId()] = (float)$lineItem->getBaseDiscountAmount();
            $itemsData[] = [
                'type' => self::TYPE,
                'data' => [
                    'line_item_keys' => [$lineItem->getId()],
                    'discount_type' => self::DISCOUNT_TYPE_FIXED,
                    'value' => $lineItem->getBaseDiscountAmount() * 100 / $lineItem->getQty(),
                    'line_text' => Mage::helper('core')->__('Discount'),
                    'discount_source' => self::SOURCE_CART,
                ],
            ];
        }
        if (!$couponCode) {
            return $itemsData;
        }
        $quote->setCouponCode($couponCode);
        $quote->collectTotals();
        $lineItems = self::getLineItems($quote);
        foreach ($lineItems as $lineItem) {
            $couponDiscount = $lineItem->getBaseDiscountAmount() - self::$discounts[$lineItem->getId()];
            $value = $couponDiscount * 100 / $lineItem->getQty();
            $itemsData[] = [
                'type' => self::TYPE,
                'data' => [
                    'line_item_keys' => [$lineItem->getId()],
                    'discount_type' => self::DISCOUNT_TYPE_FIXED,
                    'value' => $value,
                    'line_text' => $couponCode,
                    'discount_source' => self::SOURCE_COUPON,
                ],
            ];
        }
        return $itemsData;
    }

    /**
     * Retrieve line items from quote.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    private static function getLineItems(Mage_Sales_Model_Quote $quote)
    {
        $lineItems = [];
        foreach ($quote->getAllItems() as $cartItem) {
            if ($cartItem->getChildren()) {
                continue;
            }
            $lineItems[] = $cartItem;
        }
        return $lineItems;
    }
}
