<?php

/**
 * Replace magento checkout with bold checkout observer.
 */
class Bold_Checkout_Observer_CheckoutObserver
{
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
        if (!$quote->getCustomerId() && !$quote->isAllowedGuestCheckout()) {
            /** @var Mage_Customer_Model_Session $customerSession */
            $customerSession = Mage::getSingleton('customer/session');
            $customerSession->addNotice(
                Mage::helper('core')->__(
                    'Sorry, guest checkout is not available. Please log in or register.'
                )
            );
            Mage::app()->getResponse()->setRedirect(Mage::getUrl('customer/account/login'));
            $event
                ->getEvent()
                ->getControllerAction()
                ->setFlag('', Mage_Core_Controller_Front_Action::FLAG_NO_DISPATCH, true);
            return;
        }
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $websiteId = $quote->getStore()->getWebsiteId();
        $checkoutSession = Mage::getSingleton('checkout/session');
        try {
            $checkoutData = Bold_Checkout_Api_Bold_Orders_BoldOrder::init($quote);
            $checkoutSession->setBoldCheckoutData($checkoutData);
            if ($boldConfig->isCheckoutTypeSelfHosted($websiteId)) {
                return;
            }
            Bold_Checkout_StorefrontClient::call('GET', 'refresh');
            $orderId = $checkoutData->data->public_order_id;
            $token = $checkoutData->data->jwt_token;
            $checkoutUrl = $boldConfig->getCheckoutUrl($websiteId);
            $checkoutUrl .= '/bold_platform/' . $checkoutData->data->initial_data->shop_name
                . '/experience/resume?public_order_id=' . $orderId . '&token=' . $token;
            Mage::app()->getResponse()->setRedirect($checkoutUrl);
            if (!$boldConfig->isCheckoutTypeSelfHosted($websiteId)) {
                $event->getEvent()
                    ->getControllerAction()
                    ->setFlag('', Mage_Core_Controller_Front_Action::FLAG_NO_DISPATCH, true);
            }
        } catch (\Exception $exception) {
            if ($boldConfig->isCheckoutTypeSelfHosted($websiteId)) {
                $checkoutSession->setBoldCheckoutData(null);
            }
            Mage::log($exception->getMessage(), Zend_Log::CRIT);
        }
    }

    /**
     * Place order on Bold side before Magento order is placed.
     *
     * Before Magento order is placed Bold order should be processed.
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
        $processOrderResult = Bold_Checkout_StorefrontClient::call('POST', 'process_order');
        if (isset($processOrderResult->errors)) {
            $this->throwException();
        }
    }

    /**
     * Save Bold order data to database after order has been placed on Magento side.
     *
     * After Magento order has been placed, we have order id and can save Bold order data(public id) to database.
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
