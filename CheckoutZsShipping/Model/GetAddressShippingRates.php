<?php

/**
 * Get quote shipping rates.
 */
class Bold_CheckoutZsShipping_Model_GetAddressShippingRates extends Bold_Checkout_Model_GetAddressShippingRates
{
    /**
     * Get rates for address.
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return array
     */
    public function getRates(Mage_Sales_Model_Quote_Address $address)
    {
        $quote = $address->getQuote();
        Mage::getModel('zs_shipping/rateManager')->setShippingMethod($quote);
        $minimalShippingMethod = $quote->getShippingAddress()->getShippingMethod();
        return array_filter(
            $address->getAllShippingRates(),
            function (Mage_Sales_Model_Quote_Address_Rate $rate) use ($minimalShippingMethod) {
                return $rate->getCode() === $minimalShippingMethod;
            }
        );
    }
}
