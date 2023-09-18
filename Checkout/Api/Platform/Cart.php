<?php

/**
 * Cart rest service.
 */
class Bold_Checkout_Api_Platform_Cart
{
    /**
     * Get cart endpoint.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function getCart(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        preg_match('/cart\/(.*)/', $request->getRequestUri(), $cartIdMatches);
        $cartId = isset($cartIdMatches[1]) ? $cartIdMatches[1] : null;
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote');
        $quote->loadActive($cartId);
        if (!$quote->getId()) {
            return self::getErrorResult($cartId, $response);
        }
        $quote->collectTotals();
        $quoteData = Bold_Checkout_Service_Extractor_Quote::extract($quote);
        return Bold_Checkout_Rest::buildResponse($response, json_encode($quoteData));
    }

    /**
     * Set cart addresses endpoint.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function setAddresses(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        preg_match('/cart\/(.*)\/addresses/', $request->getRequestUri(), $cartIdMatches);
        $cartId = isset($cartIdMatches[1]) ? $cartIdMatches[1] : null;
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote');
        $quote->loadActive($cartId);
        if (!$quote->getId()) {
            return self::getErrorResult($cartId, $response);
        }
        $payload = json_decode($request->getRawBody());
        if ($payload->billing_address === null) {
            $quote->removeAddress($quote->getBillingAddress()->getId());
            $quote->removeAddress($quote->getShippingAddress()->getId());
            $quote->collectTotals();
            $quote->save();
            $quoteData = Bold_Checkout_Service_Extractor_Quote::extract($quote);
            return Bold_Checkout_Rest::buildResponse($response, json_encode($quoteData));
        }
        $billingAddress = self::getAddress($payload->billing_address);
        $shippingAddress = $payload->shipping_address !== null
            ? self::getAddress($payload->shipping_address)
            : null;
        $shippingAddress = $shippingAddress === null || $shippingAddress->getSameAsBilling()
            ? $billingAddress
            : $shippingAddress;
        self::setBillingAddress($quote, $billingAddress);
        if (!$quote->isVirtual()) {
            self::setShippingAddress($quote, $shippingAddress);
        }
        $quote->collectTotals();
        $quote->save();
        $quoteData = Bold_Checkout_Service_Extractor_Quote::extract($quote);
        return Bold_Checkout_Rest::buildResponse($response, json_encode($quoteData));
    }

    /**
     * Estimate cart shipping methods endpoint.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function estimateShippingMethods(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        preg_match('/cart\/(.*)\/estimate-shipping-methods/', $request->getRequestUri(), $cartIdMatches);
        $cartId = isset($cartIdMatches[1]) ? $cartIdMatches[1] : null;
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote');
        $quote->loadActive($cartId);
        if (!$quote->getId()) {
            return self::getErrorResult($cartId, $response);
        }
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return Bold_Checkout_Rest::buildResponse($response, json_encode([]));
        }
        $payload = json_decode($request->getRawBody());
        // todo: implement estimate-shipping methods.
        return Bold_Checkout_Rest::buildResponse($response, json_encode([]));
    }

    /**
     * Set cart coupon code endpoint.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     * @throws Mage_Core_Model_Store_Exception
     */
    public static function setCoupon(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        preg_match('/cart\/(.*)\/coupons/', $request->getRequestUri(), $cartIdMatches);
        $cartId = isset($cartIdMatches[1]) ? $cartIdMatches[1] : null;
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote');
        $quote->loadActive($cartId);
        if (!$quote->getId()) {
            return self::getErrorResult($cartId, $response);
        }
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getModel(Bold_Checkout_Model_Config::RESOURCE);
        if ($config->isCheckoutTypeSelfHosted((int)$quote->getStore()->getWebsiteId())) {
            $quoteData = Bold_Checkout_Service_Extractor_Quote::extract($quote);
            return Bold_Checkout_Rest::buildResponse($response, json_encode($quoteData));
        }
        $requestBody = json_decode($request->getRawBody());
        $isCodeLengthValid = strlen($requestBody->couponCode) <= 255;
        if (!$quote->getIsVirtual()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }
        $quote->setTotalsCollectedFlag(false);
        $quote->setCouponCode($isCodeLengthValid ? $requestBody->couponCode : '')->collectTotals()->save();
        if (!$isCodeLengthValid || $requestBody->couponCode !== $quote->getCouponCode()) {
            $message = Mage::helper('core')->__(
                'Coupon code "%s" is not valid.',
                Mage::helper('core')->escapeHtml($requestBody->couponCode)
            );
            $error = new stdClass();
            $error->message = $message;
            $error->code = 422;
            $error->type = 'server.validation_error';
            return Bold_Checkout_Rest::buildResponse($response, json_encode(
                    [
                        'errors' => [$error],
                    ]
                )
            );
        }
        $quoteData = Bold_Checkout_Service_Extractor_Quote::extract($quote);
        return Bold_Checkout_Rest::buildResponse($response, json_encode($quoteData));
    }

    /**
     * Remove cart coupon code endpoint.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     * @throws Mage_Core_Model_Store_Exception
     */
    public static function removeCoupon(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        preg_match('/cart\/(.*)\/coupons/', $request->getRequestUri(), $cartIdMatches);
        $cartId = isset($cartIdMatches[1]) ? $cartIdMatches[1] : null;
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote');
        $quote->loadActive($cartId);
        if (!$quote->getId()) {
            return self::getErrorResult($cartId, $response);
        }
        if (!$quote->getIsVirtual()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }
        $quote->setTotalsCollectedFlag(false);
        $quote->setCouponCode('')->collectTotals()->save();
        $quoteData = Bold_Checkout_Service_Extractor_Quote::extract($quote);
        return Bold_Checkout_Rest::buildResponse($response, json_encode($quoteData));
    }

    /**
     * Set cart shipping method endpoint.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     * @throws Mage_Core_Model_Store_Exception
     */
    public static function setShippingMethod(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        preg_match('/cart\/(.*)\/shippingMethod/', $request->getRequestUri(), $cartIdMatches);
        $cartId = isset($cartIdMatches[1]) ? $cartIdMatches[1] : null;
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote');
        $quote->loadActive($cartId);
        if (!$quote->getId()) {
            return self::getErrorResult($cartId, $response);
        }
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getModel(Bold_Checkout_Model_Config::RESOURCE);
        if ($config->isCheckoutTypeSelfHosted((int)$quote->getStore()->getWebsiteId())) {
            $quoteData = Bold_Checkout_Service_Extractor_Quote::extract($quote);
            return Bold_Checkout_Rest::buildResponse($response, json_encode($quoteData));
        }
        $payload = json_decode($request->getRawBody());
        foreach ($quote->getShippingAddress()->getShippingRatesCollection() as $rate) {
            if ($payload->shippingMethodCode === $rate->getMethod()) {
                $quote->getShippingAddress()->setShippingMethod($rate->getCode());
            }
        }
        $quote->collectTotals();
        $quote->save();
        $quoteData = Bold_Checkout_Service_Extractor_Quote::extract($quote);
        return Bold_Checkout_Rest::buildResponse($response, json_encode($quoteData));
    }

    /**
     * Set cart billing address.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param Mage_Sales_Model_Quote_Address $billingAddress
     * @return void
     */
    private static function setBillingAddress(
        Mage_Sales_Model_Quote $quote,
        Mage_Sales_Model_Quote_Address $billingAddress
    ) {
        $billingAddress->setCustomerId($quote->getCustomerId());
        $quote->removeAddress($quote->getBillingAddress()->getId());
        $quote->setBillingAddress($billingAddress);
        $quote->setDataChanges(true);
    }

    /**
     * Set shipping address to cart.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param Mage_Sales_Model_Quote_Address $shippingAddress
     * @return void
     */
    private static function setShippingAddress(
        Mage_Sales_Model_Quote $quote,
        Mage_Sales_Model_Quote_Address $shippingAddress
    ) {
        $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
        if (!$shippingAddress->getShippingMethod() && $shippingMethod) {
            $shippingAddress->setShippingMethod($shippingMethod);
        }
        $shippingAddress->setCustomerId($quote->getCustomerId());
        $quote->removeAddress($quote->getShippingAddress()->getId());
        $quote->setShippingAddress($shippingAddress);
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->setDataChanges(true);
    }

    /**
     * Get address from payload.
     *
     * @param stdClass $addressPayload
     * @return Mage_Sales_Model_Quote_Address
     */
    private static function getAddress(stdClass $addressPayload)
    {
        /** @var Mage_Sales_Model_Quote_Address $address */
        $address = Mage::getModel('sales/quote_address');
        $email = isset($addressPayload->email)
            ? $addressPayload->email
            : null;
        $regionId = isset($addressPayload->region_id)
            ? $addressPayload->region_id
            : null;
        $regionCode = isset($addressPayload->region_code)
            ? $addressPayload->region_code
            : null;
        $region = isset($addressPayload->region)
            ? $addressPayload->region
            : null;
        $countryId = isset($addressPayload->country_id)
            ? $addressPayload->country_id
            : null;
        $street1 = isset($addressPayload->street[0])
            ? $addressPayload->street[0]
            : null;
        $street2 = isset($addressPayload->street[1])
            ? $addressPayload->street[1]
            : null;
        $postcode = isset($addressPayload->postcode)
            ? $addressPayload->postcode
            : null;
        $telephone = isset($addressPayload->telephone)
            ? $addressPayload->telephone
            : null;
        $city = isset($addressPayload->city)
            ? $addressPayload->city
            : null;
        $firstname = isset($addressPayload->firstname)
            ? $addressPayload->firstname
            : null;
        $lastname = isset($addressPayload->lastname)
            ? $addressPayload->lastname
            : null;
        $sameAsBilling = isset($addressPayload->same_as_billing)
            ? $addressPayload->same_as_billing
            : null;
        $saveInAddressBook = isset($addressPayload->save_in_address_book)
            ? $addressPayload->save_in_address_book
            : null;
        $address->setEmail($email);
        $address->setRegionId($regionId);
        $address->setRegion($region);
        $address->setRegionCode($regionCode);
        $address->setCountryId($countryId);
        $address->setStreet([$street1, $street2]);
        $address->setPostcode($postcode);
        $address->setTelephone($telephone);
        $address->setCity($city);
        $address->setFirstname($firstname);
        $address->setLastname($lastname);
        $address->setSameAsBilling($sameAsBilling);
        $address->setSaveInAddressBook($saveInAddressBook);
        return $address;
    }

    /**
     * Build cart error result.
     *
     * @param int $cartId
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    private static function getErrorResult($cartId, Mage_Core_Controller_Response_Http $response)
    {
        $error = new stdClass();
        $error->message = 'There is no active cart with id: ' . $cartId;
        $error->code = 422;
        $error->type = 'server.validation_error';
        return Bold_Checkout_Rest::buildResponse(
            $response,
            json_encode(
                [
                    'errors' => [$error],
                ]
            )
        );
    }
}
