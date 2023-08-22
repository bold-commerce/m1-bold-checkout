<?php

/**
 * Create|update destinations. Register overrides on bold side observer.
 */
class Bold_Checkout_Observer_OverrideObserver
{
    /**
     * Create|update destination. Register overrides.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception In case override could not be registered or destination cannot be created|updated.
     */
    public function register(Varien_Event_Observer $event)
    {
        $websiteId = Mage::app()->getWebsite($event->getWebsite())->getId()
            ?: Mage::app()->getDefaultStoreView()->getWebsiteId();
        Bold_Checkout_Service_ShopIdentifier::updateShopIdentifier($websiteId);
        Bold_Checkout_Api_Bold_Overrides::register((int)$websiteId);
    }
}
