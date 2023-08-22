<?php

/**
 * Replace magento checkout with bold checkout observer.
 */
class Bold_CheckoutSelfHosted_Observer_CheckoutObserver extends Bold_Checkout_Observer_CheckoutObserver
{
    /**
     * Initialize and navigate to bold self-hosted checkout page.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Mage_Core_Exception
     */
    public function beforeCheckout(Varien_Event_Observer $event)
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $websiteId = $quote->getStore()->getWebsiteId();
        /** @var Bold_Checkout_Model_Config $selfHostedConfig */
        $selfHostedConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$selfHostedConfig->isCheckoutTypeSelfHosted($websiteId)) {
            parent::beforeCheckout($event);
            return;
        }
        if (Bold_Checkout_Service_IsBoldCheckoutAllowedForQuote::isAllowed($quote)) {
            Mage::app()->getResponse()->setRedirect(Mage::getUrl('bold_checkout_self_hosted'));
            $event->getEvent()->getControllerAction()->setFlag(
                '',
                Mage_Core_Controller_Front_Action::FLAG_NO_DISPATCH,
                true
            );
        }
    }
}
