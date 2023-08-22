<?php

/**
 * Discount override service.
 */
class Bold_Checkout_Api_Platform_Discount
{
    /**
     * Apply discount code to cart.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
     */
    public static function apply(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        Mage::app()->loadArea(Mage_Core_Model_App_Area::AREA_FRONTEND);
        $requestBody = json_decode($request->getRawBody());
        Mage::dispatchEvent('bold_checkout_apply_discount_before', ['request_body' => $requestBody]);
        $quote = Bold_Checkout_Service_GetQuoteFromLineItems::getQuote($requestBody->line_items);
        if (!$quote) {
            $message = Mage::helper('core')->__('Active quote has not been found.');
            return self::sendResponse($response, false, 0, $message);
        }
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getModel(Bold_Checkout_Model_Config::RESOURCE);
        if ($config->isCheckoutTypeSelfHosted($quote->getStore()->getWebsiteId())) {
            $message = Mage::helper('core')->__(
                'Coupon code "%s" was applied.',
                Mage::helper('core')->escapeHtml($requestBody->discount)
            );
            $amount = $quote->getBaseSubtotal() - $quote->getBaseSubtotalWithDiscount();
            return self::sendResponse($response, true, $amount * 100, $message);
        }
        Mage::app()->setCurrentStore($quote->getStoreId());
        $quote->setCouponCode('')->collectTotals();
        $initialFreeShipping = $quote->getShippingAddress()->getFreeShipping();
        $initialDiscount = $quote->getBaseSubtotal() - $quote->getBaseSubtotalWithDiscount();
        $isCodeLengthValid = strlen($requestBody->discount) <= 255;
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->setTotalsCollectedFlag(false);
        $quote->setCouponCode($isCodeLengthValid ? $requestBody->discount : '')->collectTotals()->save();
        if (!$isCodeLengthValid || $requestBody->discount !== $quote->getCouponCode()) {
            $message = Mage::helper('core')->__(
                'Coupon code "%s" is not valid.',
                Mage::helper('core')->escapeHtml($requestBody->discount)
            );
            return self::sendResponse($response, false, 0, $message);
        }
        $message = Mage::helper('core')->__(
            'Coupon code "%s" was applied.',
            Mage::helper('core')->escapeHtml($requestBody->discount)
        );
        $finalDiscount = $quote->getBaseSubtotal() - $quote->getBaseSubtotalWithDiscount();
        $couponCodeDiscount = ($finalDiscount - $initialDiscount) * 100;
        Mage::dispatchEvent(
            'bold_checkout_apply_discount_after',
            [
                'request_body' => $requestBody,
                'discount' => $couponCodeDiscount,
            ]
        );
        return self::sendResponse($response, true, $couponCodeDiscount, $message);
    }

    /**
     * Send apply coupon code response.
     *
     * @param Mage_Core_Controller_Response_Http $response
     * @param bool $valid
     * @param int $amount
     * @param string $message
     * @return Mage_Core_Controller_Response_Http
     */
    private static function sendResponse(Mage_Core_Controller_Response_Http $response, $valid, $amount, $message)
    {
        $responseResult = [
            'found' => true,
            'valid' => $valid,
            'kind' => 'FixedAmount',
            'amount' => $amount,
            'message' => $message,
            'tag_line' => 'Discount',
        ];
        return Bold_Checkout_Rest::buildResponse($response, json_encode($responseResult));
    }
}
