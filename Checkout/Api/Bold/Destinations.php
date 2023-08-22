<?php

/**
 * Create destinations on bold side service.
 */
class Bold_Checkout_Api_Bold_Destinations
{
    const DESTINATION_URL = 'integrations/v1/shops/{{shopId}}/platform_connector_destinations';
    const TOPICS = [
        'customers',
        'products',
        'orders',
    ];
    const API_VERSION = 'v1';
    const TIMEOUT = 20000;

    /**
     * Create destinations on bold side.
     *
     * @param int $websiteId
     * @return void
     * @throws Exception
     */
    public static function create($websiteId)
    {
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$boldConfig->isCheckoutEnabled($websiteId)) {
            return;
        }
        $errors = [];
        $getDestinationsResult = json_decode(
            Bold_Checkout_Service::call('GET', self::DESTINATION_URL, $websiteId)
        );
        $destinations = isset($getDestinationsResult->data->destinations)
            ? $getDestinationsResult->data->destinations
            : [];
        foreach (self::TOPICS as $topic) {
            $destinationToUpdate = null;
            foreach ($destinations as $destination) {
                if ($destination->topic === $topic) {
                    $destinationToUpdate = $destination;
                    break;
                }
            }
            $createUpdateDestinationResult = $destinationToUpdate
                ? self::updateDestination($destinationToUpdate, $websiteId)
                : self::createDestination($topic, $websiteId);
            $errors = array_unique(
                array_merge(
                    $errors,
                    isset($createUpdateDestinationResult->errors) ? $createUpdateDestinationResult->errors : []
                )
            );
        }

        if ($errors) {
            Mage::throwException(sprintf('Cannot create|update destination: %s', implode('. ', $errors)));
        }
    }

    /**
     * Create destination for given topic on bold side.
     *
     * @param string $topic
     * @param int $websiteId
     * @return stdClass
     * @throws Mage_Core_Exception
     */
    private static function createDestination($topic, $websiteId)
    {
        $destination = Mage::app()->getWebsite($websiteId)->getDefaultStore()->getBaseUrl(
            Mage_Core_Model_Store::URL_TYPE_WEB
        );
        $body = [
            'data' => [
                'destination' => [
                    'shop_identifier' => Bold_Checkout_Service_ShopIdentifier::getShopIdentifier($websiteId),
                    'topic' => $topic,
                    'destination' => $destination . 'bold',
                    'version' => self::API_VERSION,
                    'timeout_ms' => self::TIMEOUT,
                ],
            ],
        ];
        return json_decode(
            Bold_Checkout_Service::call('POST', self::DESTINATION_URL, $websiteId, json_encode($body))
        );
    }

    /**
     * Update given destination on bold side.
     *
     * @param stdClass $destination
     * @param int $websiteId
     * @return stdClass
     * @throws Mage_Core_Exception
     * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
     */
    private static function updateDestination(stdClass $destination, $websiteId)
    {
        if ($destination->timeout_ms > 20000) {
            return self::createDestination($destination->topic, $websiteId);
        }
        $newDestination = Mage::app()
                ->getWebsite($websiteId)
                ->getDefaultStore()
                ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)
            . 'bold';
        if ($destination->destination === $newDestination) {
            return $destination;
        }
        $deleteResult = json_decode(
            Bold_Checkout_Service::call(
                'DELETE',
                self::DESTINATION_URL . '/' . $destination->id,
                $websiteId
            )
        );
        $errors = isset($deleteResult->errors) ? $deleteResult->errors : [];
        if ($errors) {
            Mage::throwException(sprintf('Cannot update destination: %s', $destination->topic));
        }
        return self::createDestination($destination->topic, $websiteId);
    }
}
