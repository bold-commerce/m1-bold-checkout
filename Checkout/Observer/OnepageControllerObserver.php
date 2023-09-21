<?php

/**
 * Observer for one-page-checkout-controller-fired events.
 */
class Bold_Checkout_Observer_OnepageControllerObserver
{
    /**
     * Send billing address to Bold.
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function afterBillingSave(Varien_Event_Observer $event)
    {
        try {
            $session = Mage::getSingleton('checkout/session');
            $boldCheckoutData = $session->getBoldCheckoutData();
            if (!$boldCheckoutData) {
                return;
            }
            $controllerAction = $event->getControllerAction();
            if (!$session->getQuote()->getCustomer()->getId()) {
                $guestUpdateRequest = Bold_Checkout_StorefrontService::call(
                    'POST',
                    'customer/guest',
                    Bold_Checkout_Service_Extractor_Quote_Guest::extract(
                        $controllerAction->getOnepage()->getQuote()->getBillingAddress()
                    )
                );
                if (isset($guestUpdateRequest->errors)) {
                    $session->setBoldCheckoutData(null);
                    return;
                }
            }
            $billingAddress = Bold_Checkout_StorefrontService::call(
                'POST',
                'addresses/billing',
                Bold_Checkout_Service_Extractor_Quote_Address::extractInBoldFormat(
                    $controllerAction->getOnepage()->getQuote()->getBillingAddress()
                )
            );
            if (isset($billingAddress->errors)) {
                $session->setBoldCheckoutData(null);
                return;
            }
            $postData = $controllerAction->getRequest()->getPost('billing');
            $useForShipping = isset($postData['use_for_shipping']) && $postData['use_for_shipping'] == 1;
            if (!$useForShipping) {
                return;
            }
            $shippingAddress = Bold_Checkout_StorefrontService::call(
                'POST',
                'addresses/shipping',
                Bold_Checkout_Service_Extractor_Quote_Address::extractInBoldFormat(
                    $controllerAction->getOnepage()->getQuote()->getBillingAddress()
                )
            );
            if (isset($shippingAddress->errors)) {
                $session->setBoldCheckoutData(null);
            }
        } catch (Mage_Core_Exception $e) {
            $session->setBoldCheckoutData(null);
            return;
        }
    }

    /**
     * Send shipping address to Bold.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Mage_Core_Exception
     */
    public function afterShippingSave(Varien_Event_Observer $event)
    {
        $session = Mage::getSingleton('checkout/session');
        $boldCheckoutData = $session->getBoldCheckoutData();
        if (!$boldCheckoutData) {
            return;
        }
        $controllerAction = $event->getControllerAction();
        $quote = $controllerAction ? $controllerAction->getOnepage()->getQuote() : $event->getQuote();
        $address = Bold_Checkout_StorefrontService::call(
            'POST',
            'addresses/shipping',
            Bold_Checkout_Service_Extractor_Quote_Address::extractInBoldFormat(
                $quote->getShippingAddress()
            )
        );
        if (isset($address->errors)) {
            Mage::throwException(Mage::helper('checkout')->__('Cannot save shipping address'));
        }
        if ($quote->getShippingAddress()->getShippingMethod()) {
            $this->sendShippingMethodIndex($quote->getShippingAddress()->getShippingMethod());
        }
    }

    /**
     * Send shipping method index to Bold.
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function afterShippingMethodSave(Varien_Event_Observer $event)
    {
        try {
            $boldCheckoutData = Mage::getSingleton('checkout/session')->getBoldCheckoutData();
            if (!$boldCheckoutData) {
                return;
            }
            $controllerAction = $event->getControllerAction();
            $this->sendShippingMethodIndex(
                $controllerAction->getOnepage()->getQuote()->getShippingAddress()->getShippingMethod()
            );
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('checkout/session')->setBoldCheckoutData(null);
        }
    }

    /**
     * Send selected shipping method index to Bold.
     *
     * @param string $shippingMethod
     * @return void
     * @throws Mage_Core_Exception
     */
    private function sendShippingMethodIndex($shippingMethod)
    {
        $shippingLines = $this->getShippingLines();
        foreach ($shippingLines as $result) {
            if ($result->code === $shippingMethod) {
                $result = Bold_Checkout_StorefrontService::call(
                    'POST',
                    'shipping_lines',
                    ['index' => $result->id]
                );
                if (isset($result->errors)) {
                    Mage::throwException(Mage::helper('checkout')->__('Cannot save shipping method'));
                }
                return;
            }
        }
        throw new Mage_Core_Exception(Mage::helper('checkout')->__('Cannot save shipping method'));
    }

    /**
     * Retrieve shipping lines from Bold.
     *
     * @return array
     */
    private function getShippingLines()
    {
        try {
            $lines = Bold_Checkout_StorefrontService::call('GET', 'shipping_lines');
            if (isset($lines->errors)) {
                return [];
            }
        } catch (Mage_Core_Exception $e) {
            return [];
        }
        return $lines->data->shipping_lines;
    }
}
