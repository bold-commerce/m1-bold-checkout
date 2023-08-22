<?php

/**
 * Register webhooks on bold side service.
 */
class Bold_Checkout_Api_Bold_Webhooks
{
    const WEBHOOK_LIST_URL = '/checkout/shop/{{shopId}}/webhooks/topics';
    const WEBHOOK_REGISTRATION_URL = '/checkout/shop/{{shopId}}/webhooks';
    const WEBHOOKS_TO_REGISTER = [
        'order/created' => 'bold/v1/shops/%s/webhooks/order/created',
    ];

    /**
     * Register predefined webhooks in bold app.
     *
     * @param int $websiteId
     * @return void
     * @throws Exception
     */
    public static function register($websiteId)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$config->isCheckoutEnabled($websiteId)) {
            return;
        }
        $topicIds = self::getWebhookTopicIds($websiteId);
        $baseUrl = Mage::app()->getWebsite($websiteId)->getDefaultStore()->getBaseUrl(
            Mage_Core_Model_Store::URL_TYPE_WEB
        );
        foreach (self::WEBHOOKS_TO_REGISTER as $type => $url) {
            if (!isset($topicIds[$type])) {
                Mage::throwException(Mage::helper('core')->__('Unknown webhook topic name: %s"', $type));
            }
            $body = [
                'webhook_topic_id' => $topicIds[$type],
                'callback_url' => $baseUrl
                    . sprintf($url, Bold_Checkout_Service_ShopIdentifier::getShopIdentifier($websiteId)),
            ];
            $result = json_decode(
                Bold_Checkout_Service::call(
                    'POST',
                    self::WEBHOOK_REGISTRATION_URL,
                    $websiteId,
                    json_encode($body)
                )
            );
            // phpcs:ignore Zend.NamingConventions.ValidVariableName.NotCamelCaps
            if (!isset($result->data->webhook_topic_id)) {
                Mage::throwException(Mage::helper('core')->__('Cannot register webhook: %s"', $type));
            }
        }
    }

    /**
     * Get webhook topic ids.
     *
     * @param int $websiteId
     * @return int[]
     * @throws Exception
     */
    private static function getWebhookTopicIds($websiteId)
    {
        $result = json_decode(Bold_Checkout_Service::call('GET', self::WEBHOOK_LIST_URL, $websiteId));
        $result = isset($result->data) ? $result->data : [];
        $topicIds = [];
        foreach ($result as $webhookData) {
            // phpcs:ignore Zend.NamingConventions.ValidVariableName.NotCamelCaps
            $topicIds[$webhookData->webhook_topic_name] = $webhookData->webhook_topic_id;
        }

        return $topicIds;
    }
}
