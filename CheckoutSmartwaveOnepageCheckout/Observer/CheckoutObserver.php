<?php

/**
 * Replace smartwave onepage checkout with bold checkout.
 */
class Bold_CheckoutSmartwaveOnepageCheckout_Observer_CheckoutObserver
{
    /**
     * Initialize and navigate to bold checkout page.
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function beforeCheckout(Varien_Event_Observer $event)
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        if (!Bold_Checkout_Service_IsBoldCheckoutAllowedForQuote::isAllowed($quote)) {
            return;
        }
        try {
            $checkoutData = Bold_CheckoutSmartwaveOnepageCheckout_Api_Bold_Orders::init($quote);
            $orderId = $checkoutData->data->public_order_id;
            $token = $checkoutData->data->jwt_token;
            $checkoutUrl = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE)->getCheckoutUrl();
            $checkoutUrl .= '/bold_platform/' . $checkoutData->data->initial_data->shop_name
                . '/experience/resume?public_order_id=' . $orderId . '&token=' . $token;
            Mage::app()->getResponse()->setRedirect($checkoutUrl);
        } catch (\Exception $exception) {
            Mage::getSingleton('checkout/session')->addError(
                Mage::helper('core')->__(
                    'There was an error during checkout. Please contact us or try again later.'
                )
            );
            Mage::log('Bold Checkout error: ' . $exception->getMessage());
            Mage::app()->getResponse()->setRedirect('/');
        } finally {
            $event->getEvent()->getControllerAction()->setFlag(
                '', Mage_Core_Controller_Front_Action::FLAG_NO_DISPATCH, true
            );
        }
    }
}
