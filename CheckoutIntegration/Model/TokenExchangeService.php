<?php

/**
 * Token exchange service.
 */
class Bold_CheckoutIntegration_Model_TokenExchangeService
{
    /**
     * Exchanges a token with platform connector.
     *
     * @param int $integrationId
     * @param bool $isReauthorize
     * @return bool
     * @throws Mage_Core_Exception
     */
    public static function exchange($integrationId, $isReauthorize)
    {
        $integration = Bold_CheckoutIntegration_Model_IntegrationService::get($integrationId);
        if ($isReauthorize) {
            Bold_CheckoutIntegration_Model_OauthService::deleteIntegrationToken($integration->getConsumerId());
            $integration->setStatus(Bold_CheckoutIntegration_Model_Integration::STATUS_INACTIVE)->save();
        }
        return Bold_CheckoutIntegration_Model_OauthService::postToConsumer(
            $integration->getConsumerId(),
            $integration->getEndpoint()
        );
    }
}
