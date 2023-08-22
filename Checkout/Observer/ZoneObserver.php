<?php

/**
 * Create|update destinations.
 */
class Bold_Checkout_Observer_ZoneObserver
{
    /**
     * Create|update destination.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception In case zone cannot be created|updated.
     */
    public function create(Varien_Event_Observer $event)
    {
        $websiteId = Mage::app()->getWebsite($event->getWebsite())->getId()
            ?: Mage::app()->getDefaultStoreView()->getWebsiteId();
        Bold_Checkout_Api_Bold_Zones::configure((int)$websiteId);
    }
}
