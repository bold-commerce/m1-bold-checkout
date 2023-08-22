<?php

/**
 * Trigger product and customer sync on Bold side.
 */
class Bold_Checkout_Api_Bold_TriggerSync
{
    const CUSTOMER_SYNC = 'customers/v2/shops/{{shopId}}/sync';
    const PRODUCT_SYNC = 'products/v2/shops/{{shopId}}/sync';
    const TYPE_CUSTOMER = 'Customer';
    const TYPE_PRODUCT = 'Product';

    /**
     * Trigger product and customer sync on Bold side.
     *
     * @param int $websiteId
     * @return void
     * @throws Mage_Core_Exception
     */
    public static function trigger($websiteId)
    {
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$boldConfig->isCheckoutEnabled($websiteId)) {
            return;
        }
        $body = new stdClass();
        $customerResult = json_decode(
            Bold_Checkout_Service::call(
                'POST',
                self::CUSTOMER_SYNC,
                $websiteId,
                json_encode($body)
            )
        );
        self::processResult($customerResult, self::TYPE_CUSTOMER);
        $productResult = json_decode(
            Bold_Checkout_Service::call(
                'POST',
                self::PRODUCT_SYNC,
                $websiteId,
                json_encode($body)
            )
        );
        self::processResult($productResult, self::TYPE_PRODUCT);
    }

    /**
     * Process result of sync trigger.
     *
     * @param stdClass $result
     * @param string $type
     * @return void
     * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
     */
    private static function processResult(stdClass $result, $type)
    {
        $statusCode = isset($result->status_code) ? $result->status_code : 0;
        $errors = isset($result->errors) ? $result->errors : [];
        /** @var Mage_Core_Model_Session $session */
        $session = Mage::getSingleton('core/session');
        if ($statusCode === 409) {
            $session->addNotice(
                Mage::helper('core')->__('%s synchronization already in progress.', $type)
            );
            return;
        }
        if ($statusCode === 501) {
            $session->addError(
                implode('. ', $errors)
            );
            return;
        }
        $session->addNotice(
            Mage::helper('core')->__('%s synchronization started.', $type)
        );
    }
}
