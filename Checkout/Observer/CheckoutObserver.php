<?php

/**
 * Replace magento checkout with bold checkout observer.
 */
class Bold_Checkout_Observer_CheckoutObserver
{
    const URL = 'https://api.boldcommerce.com/checkout/storefront/';

    /**
     * Initialize and navigate to bold checkout page.
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function beforeCheckout(Varien_Event_Observer $event)
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $websiteId = $quote->getStore()->getWebsiteId();
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $request = $event->getControllerAction()->getRequest();
        if (!Bold_Checkout_Service_IsBoldCheckoutAllowedForQuote::isAllowed($quote)
            || ($boldConfig->isCheckoutTypeParallel($websiteId)
                && !$request->getParam(Bold_Checkout_Block_Parallel::KEY_PARALLEL)
            )
        ) {
            return;
        }
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $websiteId = $quote->getStore()->getWebsiteId();
        $checkoutSession = Mage::getSingleton('checkout/session');
        try {
            $checkoutData = Bold_Checkout_Api_Bold_Orders_BoldOrder::init($quote);
            if ($boldConfig->isCheckoutTypeSelfHosted($websiteId)) {
                Mage::getSingleton('checkout/session')->setBoldCheckoutData($checkoutData);
                return;
            }
            $orderId = $checkoutData->data->public_order_id;
            $token = $checkoutData->data->jwt_token;
            $checkoutUrl = $boldConfig->getCheckoutUrl($websiteId);
            $checkoutUrl .= '/bold_platform/' . $checkoutData->data->initial_data->shop_name
                . '/experience/resume?public_order_id=' . $orderId . '&token=' . $token;
            Mage::app()->getResponse()->setRedirect($checkoutUrl);
        } catch (\Exception $exception) {
            if ($boldConfig->isCheckoutTypeSelfHosted($websiteId)) {
                $checkoutSession->setBoldCheckoutData(null);
                return;
            }
            $checkoutSession->addError(
                Mage::helper('core')->__(
                    'There was an error during checkout. Please contact us or try again later.'
                )
            );
            Mage::app()->getResponse()->setRedirect('/');
        } finally {
            if (!$boldConfig->isCheckoutTypeSelfHosted($websiteId)) {
                $event
                    ->getEvent()
                    ->getControllerAction()
                    ->setFlag('', Mage_Core_Controller_Front_Action::FLAG_NO_DISPATCH, true);
            }
        }
    }

    /**
     * Place order on Bold side.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Mage_Core_Exception
     */
    public function beforeSaveOrder(Varien_Event_Observer $event)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $event->getEvent()->getOrder();
        $paymentMethod = $order->getPayment()->getMethod();
        $session = Mage::getSingleton('checkout/session');
        $boldCheckoutData = $session->getBoldCheckoutData();
        if (!$boldCheckoutData || $paymentMethod !== Bold_Checkout_Service_PaymentMethod::CODE) {
            return;
        }
        $taxes = Bold_Checkout_StorefrontService::call('POST', 'taxes');
        if (isset($taxes->errors)) {
            $this->throwException();
        }
        if ($order->getDiscountAmount()) {
            $discounts = Bold_Checkout_StorefrontService::call(
                'POST',
                'discounts',
                ['code' => 'Discount']
            );
            if ($discounts->errors) {
                $this->throwException();
            }
        }
        $processOrder = Bold_Checkout_StorefrontService::call('POST', 'process_order');
        if (isset($processOrder->errors)) {
            $this->throwException();
        }
    }

    /**
     * Save bold order data to database.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception
     */
    public function afterCheckout(Varien_Event_Observer $event)
    {
        $session = Mage::getSingleton('checkout/session');
        $boldCheckoutData = $session->getBoldCheckoutData();
        $order = $event->getEvent()->getOrder();
        if (!$boldCheckoutData || $order->getPayment()->getMethod() !== Bold_Checkout_Service_PaymentMethod::CODE) {
            return;
        }
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $extOrderData->setOrderId($order->getEntityId());
        $extOrderData->setPublicId($boldCheckoutData->data->public_order_id);
        $extOrderData->setFinancialStatus('pending');
        $extOrderData->setFulfillmentStatus('pending');
        $extOrderData->save();
        $session->setBoldCheckoutData(null);
    }

    /**
     * @return void
     * @throws Mage_Core_Exception
     */
    public function throwException()
    {
        Mage::throwException(
            Mage::helper('checkout')
                ->__('There was an error processing your order. Please contact us or try again later.')
        );
    }
}
