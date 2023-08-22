<?php

/**
 * Cart Items discount calculation service.
 */
class Bold_CheckoutSmartwaveOnepageCheckout_Service_Action_DiscountLineItem
    implements Bold_Checkout_Service_Action_QuoteActionInterface
{
    const TYPE = 'discount_line_items';
    const DISCOUNT_TYPE_FIXED = 'fixed';

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
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
        }
        $itemsData = [];
        $messageBase = Mage::helper('core')->__('Discount');
        $lineItems = [];
        foreach ($quote->getAllItems() as $cartItem) {
            if ($cartItem->getChildren()) {
                continue;
            }
            $lineItems[] = $cartItem;
        }
        foreach ($lineItems as $lineItem) {
            if ((float)$lineItem->getBaseDiscountAmount()) {
                $itemsData[] = [
                    'type' => self::TYPE,
                    'data' => [
                        'line_item_keys' => [$lineItem->getId()],
                        'discount_type' => self::DISCOUNT_TYPE_FIXED,
                        'value' => self::getValue($lineItem),
                        'line_text' => $messageBase,
                        'discount_source' => 'cart',
                    ],
                ];
            }
        }
        if ($couponCode) {
            $quote->setCouponCode($couponCode);
            $quote->setTotalsCollectedFlag(true);
            $quote->collectTotals();
        }
        return $itemsData;
    }

    /**
     * @param Mage_Sales_Model_Quote_Item $lineItem
     * @return float|int
     */
    public static function getValue(Mage_Sales_Model_Quote_Item $lineItem)
    {
        /** @var Bold_CheckoutSmartwaveOnepageCheckout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $websiteId = $lineItem->getQuote()->getStore()->getWebsiteId();
        if (!$config->isAdaptFractionalPriceEnabled($websiteId)) {
            return (int)($lineItem->getBaseDiscountAmount() * 100) / $lineItem->getQty();
        }
        $parentItem = $lineItem->getParentItem();
        if ($parentItem) {
            $lineItem = $parentItem;
        }
        $qtyOptions = $lineItem->getQtyOptions();
        return $qtyOptions
            ? (int)($lineItem->getBaseDiscountAmount() * 100) / current($qtyOptions)->getValue()
            : (int)($lineItem->getBaseDiscountAmount() * 100) / $lineItem->getQty();
    }
}
