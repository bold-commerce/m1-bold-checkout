<?php

/**
 * Platform order webhooks api service.
 */
class Bold_Checkout_Api_Platform_OrdersWebhooks
{
    /**
     * Save Customer subscription status after Order creation.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return void
     * @throws Exception
     */
    public static function created(
        Mage_Core_Controller_Request_Http  $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        $requestBody = json_decode($request->getRawBody());
        // phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
        if (!isset($requestBody->application_state->customer->accepts_marketing)
            || !isset($requestBody->application_state->customer->platform_id)
        ) {
            return;
        }
        $customerId = $requestBody->application_state->customer->platform_id;
        $isSubscribed = $requestBody->application_state->customer->accepts_marketing;
        // phpcs:enable Zend.NamingConventions.ValidVariableName.NotCamelCaps
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer')->load($customerId);
        if ($isSubscribed && $customer->getId()) {
            $customer->setIsSubscribed(true);
            /** @var Mage_Newsletter_Model_Subscriber $subscriber */
            $subscriber = Mage::getModel('newsletter/subscriber');
            $subscriber->subscribeCustomer($customer);
        }
    }
}
