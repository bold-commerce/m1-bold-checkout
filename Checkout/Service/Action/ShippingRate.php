<?php

/**
 * Send pre-selected shipping method to Bold.
 */
class Bold_Checkout_Service_Action_ShippingRate implements Bold_Checkout_Service_Action_QuoteActionInterface
{
    const TYPE = 'set_shipping_rate';

    /**
     * @inheritDoc
     */
    public static function generateActionData(Mage_Sales_Model_Quote $quote)
    {
        if (!$quote->getShippingAddress()->getShippingMethod()) {
            return [];
        }
        $rate = $quote->getShippingAddress()->getShippingRateByCode($quote->getShippingAddress()->getShippingMethod());
        return [
            [
                'type' => self::TYPE,
                'data' => [
                    'shipping_rate' => [
                        'price' => round($quote->getShippingAddress()->getBaseShippingAmount() * 100, 2),
                        'name' => strip_tags(sprintf('%s: %s', $rate->getCarrierTitle(), $rate->getMethodTitle())),
                        'code' => $quote->getShippingAddress()->getShippingMethod(),
                    ],
                ],
            ],
        ];
    }
}
