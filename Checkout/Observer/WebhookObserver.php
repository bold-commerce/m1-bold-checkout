<?php

/**
 * Register webhooks.
 */
class Bold_Checkout_Observer_WebhookObserver
{
    /**
     * Create|update destination. Register overrides.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception In case webhook cannot be registered.
     */
    public function register(Varien_Event_Observer $event)
    {
        $websiteId = Mage::app()->getWebsite($event->getWebsite())->getId()
            ?: Mage::app()->getDefaultStoreView()->getWebsiteId();
        Bold_Checkout_Service_ShopIdentifier::updateShopIdentifier($websiteId);
        Bold_Checkout_Api_Bold_Webhooks::register((int)$websiteId);
    }
}
