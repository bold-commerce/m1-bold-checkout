<?php

/**
 * Gets currency for the active cart.
 */
class Bold_Checkout_Service_Action_Currency implements Bold_Checkout_Service_Action_QuoteActionInterface
{
    const SET_CURRENCY = 'set_currency';
    const SET_GATEWAY_CURRENCY = 'set_gateway_currency';

    /**
     * @inheritDoc
     */
    public static function generateActionData(Mage_Sales_Model_Quote $quote)
    {
        /** @var Mage_Directory_Model_Currency $cartCurrency */
        $cartCurrency = Mage::getModel('directory/currency');
        $cartCurrency->load($quote->getQuoteCurrencyCode());
        $currencyFormat = $cartCurrency->format('1', [], false);
        $format = preg_replace('/\d.*\d|\d/', '{{amount}}', $currencyFormat);
        /** @var Mage_Directory_Model_Currency $cartCurrency */
        $baseCurrency = Mage::getModel('directory/currency');
        $baseCurrency->load(Mage::app()->getBaseCurrencyCode());
        return [
            [
                'type' => self::SET_CURRENCY,
                'data' => [
                    'currency' => $cartCurrency->getCurrencyCode(),
                    'rate' => $baseCurrency->getRate($cartCurrency),
                    'format_string' => $format,
                ],
            ],
            [
                'type' => self::SET_GATEWAY_CURRENCY,
                'data' => [
                    'currency' => $cartCurrency->getCurrencyCode(),
                ],
            ],
        ];
    }
}
