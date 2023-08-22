<?php

/**
 * Platform taxes calculation service.
 *
 * phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 */
class Bold_Checkout_Api_Platform_Taxes
{
    /**
     * Calculate taxes for given cart.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function calculate(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        Mage::app()->loadArea(Mage_Core_Model_App_Area::AREA_FRONTEND);
        $requestData = json_decode($request->getRawBody());
        Mage::dispatchEvent('bold_checkout_taxes_calculate_before', ['request_body' => $requestData]);
        $errors = self::validatePayload($requestData);
        if ($errors) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                implode(' ', $errors),
                400,
                'server.validation_error'
            );
        }
        $quote = Bold_Checkout_Service_GetQuoteFromLineItems::getQuote($requestData->cart);
        if (!$quote) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                'Cannot find active quote.',
                400,
                'server.validation_error'
            );
        }
        Mage::app()->setCurrentStore($quote->getStoreId());
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$config->isCheckoutTypeSelfHosted($quote->getStore()->getWebsiteId())) {
            $addressData = self::unifyShippingAddressData($requestData);
            try {
                Bold_Checkout_Service_QuoteAddress::updateShippingAddress($addressData, $quote);
                $quote->getBillingAddress()->setShouldIgnoreValidation(true);
                $quote->getShippingAddress()->setShouldIgnoreValidation(true);
                $quote->collectTotals()->save();
            } catch (Mage_Core_Exception $e) {
                return Bold_Checkout_Rest::buildErrorResponse($response, $e->getMessage());
            }
        }
        $taxes = self::getShippingTax($quote->getShippingAddress());
        $taxes = self::addProductTaxes($quote, $taxes);
        Mage::dispatchEvent(
            'bold_checkout_taxes_calculate_after',
            [
                'request_body' => $requestData,
                'taxes' => $taxes,
            ]
        );
        return Bold_Checkout_Rest::buildResponse($response, json_encode($taxes));
    }

    /**
     * Check payload has all required data.
     *
     * @param stdClass $requestBody
     * @return array
     */
    private static function validatePayload(stdClass $requestBody)
    {
        $errors = [];
        if (!$requestBody->cart) {
            $errors[] = 'No quote line items provided.';
        }
        if (!$requestBody->shipping_address) {
            $errors[] = 'No shipping address provided.';
        }
        if (!$requestBody->shipping_total) {
            $errors[] = 'No shipping total provided.';
        }

        return $errors;
    }

    /**
     * Build shipping address from request.
     *
     * @param stdClass $requestBody
     * @return stdClass|null
     */
    private static function unifyShippingAddressData(stdClass $requestBody)
    {
        $shippingAddress = $requestBody->shipping_address ?: null;
        /** @var Bold_Checkout_Model_RegionCodeMapper $regionCodeMapper */
        $regionCodeMapper = Mage::getSingleton(Bold_Checkout_Model_RegionCodeMapper::RESOURCE);
        $provinceCode = $regionCodeMapper->getRegionCode($shippingAddress->country, $shippingAddress->province);
        if ($shippingAddress) {
            $shippingAddress->country_code = $shippingAddress->country;
            $shippingAddress->province_code = $provinceCode;
        }

        return $shippingAddress;
    }

    /**
     * Calculate and add shipping tax.
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return array
     */
    private static function getShippingTax(Mage_Sales_Model_Quote_Address $address)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $rate = $address->getShippingAmount()
            ? round($address->getShippingTaxAmount() / $address->getShippingAmount(), 6)
            : 0;
        $rate = $config->isCheckoutTypeSelfHosted($address->getQuote()->getStore()->getWebsiteId())
            ? $rate
            : self::calculateShippingTax($address);
        return [
            'shipping' => [
                [
                    'name' => Mage::helper('core')->__('Shipping Tax'),
                    'rate' => $rate,
                ],
            ],
            'sub_total' => [],
        ];
    }

    /**
     * Calculate and add product taxes to result.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param array $result
     * @return array
     */
    public static function addProductTaxes(Mage_Sales_Model_Quote $quote, array $result)
    {
        /** @var Mage_Sales_Model_Quote_Item $item */
        foreach ($quote->getAllItems() as $item) {
            $result['line_items'][$item->getItemId()][] =
                [
                    'name' => $item->getName(),
                    'rate' => self::calculateItemTax($item),
                ];
        }
        return $result;
    }

    /**
     * Calculate order item tax.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     */
    private static function calculateItemTax(Mage_Sales_Model_Quote_Item $item)
    {
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }

        return $item->getTaxPercent() / 100;
    }

    /**
     * Calculate shipping tax.
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return float
     */
    private static function calculateShippingTax(Mage_Sales_Model_Quote_Address $address)
    {
        $originalShippingAmount = $address->getShippingAmount();
        $originalBaseShippingAmount = $address->getBaseShippingAmount();
        $address->setShippingAmount(100);
        $address->setBaseShippingAmount(100);
        $calc = Mage::getSingleton('tax/calculation');
        $config = Mage::getSingleton('tax/config');
        $store = $address->getQuote()->getStore();
        $storeTaxRequest = $calc->getRateOriginRequest($store);
        $addressTaxRequest = $calc->getRateRequest(
            $address,
            $address->getQuote()->getBillingAddress(),
            $address->getQuote()->getCustomerTaxClassId(),
            $store
        );
        $shippingTaxClass = $config->getShippingTaxClass($store);
        $storeTaxRequest->setProductClassId($shippingTaxClass);
        $addressTaxRequest->setProductClassId($shippingTaxClass);
        $rate = $calc->getRate($addressTaxRequest);
        $address->setShippingAmount($originalShippingAmount);
        $address->setBaseShippingAmount($originalBaseShippingAmount);

        return round($rate / 100, 6);
    }
}
