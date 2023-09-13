<?php

/**
 * Create|update destinations.
 */
class Bold_Checkout_Observer_IntegrationObserver
{
    /**
     * Create|update destination.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception In case destination cannot be created|updated.
     */
    public function create(Varien_Event_Observer $event)
    {
        $websiteId = Mage::app()->getWebsite($event->getWebsite())->getId()
            ?: Mage::app()->getDefaultStoreView()->getWebsiteId();
        Bold_Checkout_Service_ShopIdentifier::updateShopIdentifier($websiteId);
        $integrationName = $this->getName($websiteId);
        $integration = Bold_CheckoutIntegration_Model_IntegrationService::findByName($integrationName);
        if ($integration->getId()) {
            return;
        }
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $shopId = $config->getShopIdentifier($websiteId);
        $callbackUrl = $config->getIntegrationCallbackUrl($websiteId);
        $identityUrl = $config->getIntegrationIdentityUrl($websiteId);
        $integrationData = [
            Bold_CheckoutIntegration_Model_Integration::NAME => $integrationName,
            Bold_CheckoutIntegration_Model_Integration::ENDPOINT => rtrim($callbackUrl, '/') . '/' . $shopId,
            Bold_CheckoutIntegration_Model_Integration::IDENTITY_LINK_URL => $identityUrl,
            Bold_CheckoutIntegration_Model_Integration::SETUP_TYPE => Bold_CheckoutIntegration_Model_Integration::TYPE_MANUAL,
            Bold_CheckoutIntegration_Model_Integration::STATUS => Bold_CheckoutIntegration_Model_Integration::STATUS_INACTIVE,
        ];
        Bold_CheckoutIntegration_Model_IntegrationService::create($integrationData);
    }

    /**
     * Get integration name.
     *
     * @param int $websiteId
     * @return string
     */
    private function getName($websiteId)
    {
        return str_replace(
            '{{websiteId}}',
            (string)$websiteId,
            Bold_CheckoutIntegration_Model_Integration::INTEGRATION_NAME_TEMPLATE
        );
    }
}
