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
     * Calculate Cart Items discounts before order initialization.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array[]
     */
    public static function generateActionData(Mage_Sales_Model_Quote $quote)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if ($config->isCheckoutTypeSelfHosted((int)$quote->getStore()->getWebsiteId())) {
            return [];
        }
        $lineItems = self::getLineItems($quote);
        $itemsData = [];
        /** @var Mage_Sales_Model_Quote_Item $lineItem */
        foreach ($lineItems as $lineItem) {
            $itemsData[] = [
                'type' => self::TYPE,
                'data' => [
                    'line_item_keys' => [$lineItem->getId()],
                    'discount_type' => self::DISCOUNT_TYPE_FIXED,
                    'value' => self::getDiscountValue($lineItem),
                    'line_text' => $quote->getCouponCode()
                        ? $quote->getCouponCode()
                        : Mage::helper('core')->__('Discount'),
                    'discount_source' => $quote->getCouponCode() ? self::SOURCE_COUPON : self::SOURCE_CART,
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
            if (!Bold_Checkout_Service_Extractor_Quote_Item::shouldAppearInCart($cartItem)) {
                continue;
            }
            $lineItems[] = $cartItem;
        }
        return $lineItems;
    }

    /**
     * Get discount value considering product type.
     *
     * @param Mage_Sales_Model_Quote_Item $lineItem
     * @return float|int
     */
    private static function getDiscountValue(Mage_Sales_Model_Quote_Item $lineItem)
    {
        $bundleDiscount = 0;
        if ($lineItem->getProductType() === 'bundle') {
            foreach ($lineItem->getChildren() as $child) {
                $bundleDiscount += $child->getDiscountAmount();
            }
            return $bundleDiscount * 100 / $lineItem->getQty();
        }
        $lineItem = $lineItem->getParentItem() ?: $lineItem;
        return $lineItem->getDiscountAmount() * 100 / $lineItem->getQty();
    }
}
