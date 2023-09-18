<?php

/**
 * Integration Service.
 *
 * This service is used to interact with integrations.
 */
class Bold_CheckoutIntegration_Model_IntegrationService
{
    /**
     * Create integration with given data.
     *
     * @param array $integrationData
     * @return Bold_CheckoutIntegration_Model_Integration
     * @throws Mage_Core_Exception
     */
    public static function create(array $integrationData)
    {
        self::checkIntegrationByName($integrationData['name']);
        /** @var Bold_CheckoutIntegration_Model_Integration $integration */
        $integration = Mage::getModel(Bold_CheckoutIntegration_Model_Integration::RESOURCE)->setData($integrationData);
        $integration->save();
        $consumerName = 'Integration' . $integration->getId();
        /** @var Bold_CheckoutIntegration_Model_Oauth_Consumer $consumer */
        $consumer = Bold_CheckoutIntegration_Model_OauthService::createConsumer(['name' => $consumerName]);
        $integration->setConsumerId($consumer->getId());
        $integration->save();
        return $integration;
    }

    /**
     * Update integration with given data.
     *
     * @param array $integrationData
     * @return Bold_CheckoutIntegration_Model_Integration
     * @throws Mage_Core_Exception
     */
    public static function update(array $integrationData)
    {
        $integration = self::loadIntegrationById($integrationData['integration_id']);
        if ($integration->getName() !== $integrationData['name']) {
            self::checkIntegrationByName($integrationData['name']);
        }
        $integration->addData($integrationData);
        $integration->save();
        return $integration;
    }

    /**
     * Delete integration by id.
     *
     * @param int $integrationId
     * @return void
     * @throws Exception
     */
    public static function delete($integrationId)
    {
        $integration = self::loadIntegrationById($integrationId);
        $consumer = Bold_CheckoutIntegration_Model_OauthService::loadConsumer($integration->getConsumerId());
        $integration->delete();
        $consumer->delete();
    }

    /**
     * Get integration by id.
     *
     * @param int $integrationId
     * @return Bold_CheckoutIntegration_Model_Integration
     * @throws Mage_Core_Exception
     */
    public static function get($integrationId)
    {
        $integration = self::loadIntegrationById($integrationId);
        self::addOauthConsumerData($integration);
        self::addOauthTokenData($integration);
        return $integration;
    }

    /**
     * Get integration by website id.
     *
     * @param int $websiteId
     * @return Bold_CheckoutIntegration_Model_Integration[]
     */
    public static function findByWebsiteId($websiteId)
    {
        /** @var Bold_CheckoutIntegration_Model_Resource_Integration_Collection $collection */
        $collection = Mage::getModel(Bold_CheckoutIntegration_Model_Integration::RESOURCE)->getCollection();
        return $collection->addFieldToFilter('website_id', $websiteId)->getItems();
    }

    /**
     * Get integration by name.
     *
     * @param string $name
     * @return Bold_CheckoutIntegration_Model_Integration
     */
    public static function findByName($name)
    {
        /** @var Bold_CheckoutIntegration_Model_Integration $integration */
        $integration = Mage::getModel(Bold_CheckoutIntegration_Model_Integration::RESOURCE)->load(
            $name,
            'name'
        );
        self::addOauthConsumerData($integration);
        return $integration;
    }

    /**
     * Get integration by consumer id.
     *
     * @param int $consumerId
     * @return Bold_CheckoutIntegration_Model_Integration
     */
    public static function findByConsumerId($consumerId)
    {
        /** @var Bold_CheckoutIntegration_Model_Integration $integration */
        $integration = Mage::getModel(Bold_CheckoutIntegration_Model_Integration::class)->load(
            $consumerId,
            'consumer_id'
        );
        return $integration;
    }

    /**
     * Check if an integration exists by the name
     *
     * @param string $name
     * @return void
     * @throws Mage_Core_Exception
     */
    private static function checkIntegrationByName($name)
    {
        $integration = Mage::getModel(Bold_CheckoutIntegration_Model_Integration::RESOURCE)->load($name, 'name');
        if ($integration->getId()) {
            Mage::throwException(
                Mage::helper('core')->__('The integration with name "%s" exists.', $name)
            );
        }
    }

    /**
     * Load integration by id.
     *
     * @param int $integrationId
     * @return Bold_CheckoutIntegration_Model_Integration
     * @throws Mage_Core_Exception
     */
    private static function loadIntegrationById($integrationId)
    {
        /** @var Bold_CheckoutIntegration_Model_Integration $integration */
        $integration = Mage::getModel(Bold_CheckoutIntegration_Model_Integration::RESOURCE)->load($integrationId);
        if (!$integration->getId()) {
            Mage::throwException(
                Mage::helper('core')->__('The integration with ID "%s" doesn\'t exist.', $integrationId)
            );
        }
        return $integration;
    }

    /**
     * Add oAuth consumer key and secret.
     *
     * @param Bold_CheckoutIntegration_Model_Integration $integration
     * @return void
     */
    private static function addOauthConsumerData(Bold_CheckoutIntegration_Model_Integration $integration)
    {
        if ($integration->getId()) {
            $consumer = Bold_CheckoutIntegration_Model_OauthService::loadConsumer($integration->getConsumerId());
            $integration->setData('consumer_key', $consumer->getConsumerKey());
            $integration->setData('consumer_secret', $consumer->getSecret());
        }
    }

    /**
     * Add oAuth token and token secret.
     *
     * @param Bold_CheckoutIntegration_Model_Integration $integration
     * @return void
     */
    private static function addOauthTokenData(Bold_CheckoutIntegration_Model_Integration $integration)
    {
        if ($integration->getId()) {
            $accessToken = Bold_CheckoutIntegration_Model_OauthService::getAccessToken($integration->getConsumerId());
            if ($accessToken) {
                $integration->setData('token', $accessToken->getToken());
                $integration->setData('token_secret', $accessToken->getSecret());
            }
        }
    }
}
