<?php

/**
 * Shipping methods entity to array extract service.
 */
class Bold_Checkout_Service_Extractor_Quote_ShippingMethods
{
    /**
     * Extract shipping methods.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public static function extract(Mage_Sales_Model_Quote $quote)
    {
        $shippingMethods = [];
        foreach ($quote->getShippingAddress()->getShippingRatesCollection() as $rate) {
            $shippingMethod = self::extractShippingMethod($rate);
            $shippingMethods[$shippingMethod['carrier_code'] . '_' . $shippingMethod['method_code']] = $shippingMethod;
        }
        return array_values($shippingMethods);
    }

    /**
     * Extract shipping method entity data into array.
     *
     * @param Mage_Sales_Model_Quote_Address_Rate $rate
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function extractShippingMethod(Mage_Sales_Model_Quote_Address_Rate $rate)
    {
        return [
            'carrier_code' => $rate->getCarrier(),
            'method_code' => $rate->getMethod(),
            'carrier_title' => $rate->getCarrierTitle(),
            'method_title' => strip_tags($rate->getMethodTitle()),
            'amount' => (float)$rate->getPrice(),
            'base_amount' => (float)$rate->getPrice(),
            'available' => true,
            'error_message' => (string)$rate->getErrorMessage(),
            'price_excl_tax' => Mage::app()->getStore()->roundPrice($rate->getPrice()),
            'price_incl_tax' => Mage::app()->getStore()->roundPrice($rate->getPrice() + $rate->getAddress()->getShippingTaxAmount()),
        ];
    }
}
