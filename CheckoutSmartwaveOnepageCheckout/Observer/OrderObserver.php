<?php

/**
 * Fulfill order items on bold observer.
 */
class Bold_CheckoutSmartwaveOnepageCheckout_Observer_OrderObserver
{
    /**
     * Fulfill order items on bold side after order has been invoiced|shipped.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Mage_Payment_Exception|Mage_Core_Exception
     */
    public function orderSaved(Varien_Event_Observer $event)
    {
        /** @var Bold_CheckoutSmartwaveOnepageCheckout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $order = $event->getDataObject();
        $websiteId = $order->getStore()->getWebsiteId();
        if (!$config->isCheckoutEnabled($websiteId)) {
            return;
        }
        $config->isAdaptFractionalPriceEnabled($websiteId)
            ? Bold_CheckoutSmartwaveOnepageCheckout_Api_Bold_Orders::fulfilItems($order)
            : Bold_Checkout_Api_Bold_Orders_Items::fulfilItems($order);
    }
}
