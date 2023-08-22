<?php

/**
 * Get quote shipping rates.
 */
class Bold_Checkout_Model_GetAddressShippingRates
{
    const RESOURCE = 'bold_checkout/getaddressshippingrates';

    /**
     * Get rates for address.
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return array
     */
    public function getRates(Mage_Sales_Model_Quote_Address $address)
    {
        return $address->getAllShippingRates();
    }
}
