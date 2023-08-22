<?php

/**
 * Update order item fulfillment status on bold side observer.
 */
class Bold_Checkout_Observer_OrderObserver
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
        $order = $event->getDataObject();
        $websiteId = $order->getStore()->getWebsiteId();
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$boldConfig->isCheckoutEnabled($websiteId)) {
            return;
        }
        Bold_Checkout_Api_Bold_Orders_Items::fulfilItems($order);
    }
}
