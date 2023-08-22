<?php

/**
 * Platform shipping calculation service.
 *
 * phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 */
class Bold_Checkout_Api_Platform_Shipping
{
    /**
     * Calculate shipping rates for given cart.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function calculate(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        $requestBody = json_decode($request->getRawBody());
        Mage::app()->loadArea(Mage_Core_Model_App_Area::AREA_FRONTEND);
        Mage::dispatchEvent('bold_checkout_shipping_calculate_before', ['request_body' => $requestBody]);
        $quote = Bold_Checkout_Service_GetQuoteFromLineItems::getQuote($requestBody->cart);
        if (!$quote) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                'Cannot find active cart.',
                400,
                'server.validation_error'
            );
        }
        $websiteId = $quote->getStore()->getWebsiteId();
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$boldConfig->isCheckoutTypeSelfHosted($websiteId)) {
            Mage::app()->setCurrentStore($quote->getStoreId());
            try {
                Bold_Checkout_Service_QuoteAddress::updateShippingAddress($requestBody->destination_address, $quote);
                $quote->getBillingAddress()->setShouldIgnoreValidation(true);
                $quote->getShippingAddress()->setShouldIgnoreValidation(true);
                $quote->setTotalsCollectedFlag(false);
                $quote->collectTotals();
            } catch (Exception $e) {
                return Bold_Checkout_Rest::buildErrorResponse($response, $e->getMessage());
            }
        }
        $shippingRates = self::extractShippingRates($quote->getShippingAddress());
        Mage::dispatchEvent(
            'bold_checkout_shipping_calculate_after',
            [
                'request_body' => $requestBody,
                'shipping_rates' => $shippingRates,
            ]
        );
        return Bold_Checkout_Rest::buildResponse(
            $response,
            json_encode($shippingRates)
        );
    }

    /**
     * Extract shipping rates data.
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return array
     */
    private static function extractShippingRates(Mage_Sales_Model_Quote_Address $address)
    {
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $selfHostedEnabled = $boldConfig->isCheckoutTypeSelfHosted($address->getQuote()->getStore()->getWebsiteId());
        $rates = [];
        $shippingRates = Mage::getSingleton(Bold_Checkout_Model_GetAddressShippingRates::RESOURCE)->getRates(
            $address
        );
        foreach ($shippingRates as $index => $rate) {
            if ($address->getShippingMethod() !== $rate->getCode()) {
                continue;
            }
            $shippingLine = [
                'line_text' => strip_tags(sprintf('%s: %s', $rate->getCarrierTitle(), $rate->getMethodTitle())),
                'code' => $rate->getCode(),
                'value' => round($address->getBaseShippingAmount(), 2),
                'selected' => true,
            ];
            $rates[] = $shippingLine;
            unset($shippingRates[$index]);
            break;
        }
        foreach ($shippingRates as $rate) {
            $discount = !$selfHostedEnabled ? self::calculateShippingDiscount($address, $rate) : 0;
            $rates[] = [
                'line_text' => strip_tags(sprintf('%s: %s', $rate->getCarrierTitle(), $rate->getMethodTitle())),
                'code' => $rate->getCode(),
                'value' => round((float)$rate->getPrice() - $discount, 2),
            ];
        }
        return [
            'name' => '',
            'rates' => $rates,
        ];
    }

    /**
     * Get shipping discount for given shipping method.
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @param Mage_Sales_Model_Quote_Address_Rate $rate
     * @return float
     */
    private static function calculateShippingDiscount(
        Mage_Sales_Model_Quote_Address $address,
        Mage_Sales_Model_Quote_Address_Rate $rate
    ) {
        $calculator = Mage::getSingleton('salesrule/quote_discount');
        $address->setBaseShippingAmount($rate->getPrice());
        $address->setShippingAmount($rate->getPrice());
        $address->setShippingMethod($rate->getCode());
        $calculator->collect($address);
        return (float)$address->getBaseShippingDiscountAmount();
    }
}
