<?php

/**
 * Verify Platform Connector Destination Service.
 */
class Bold_Checkout_Api_Platform_Destination
{
    /**
     * Confirms that you own a given Platform Connector destination.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function verify(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        $websiteId = Mage::app()->getWebsite()->getId();
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $hmac = base64_encode(
            hash_hmac(
                'sha256',
                $request->getHeader('X-HMAC-Timestamp'),
                $boldConfig->getSharedSecret($websiteId),
                true
            )
        );
        $response->setHeader('X-HMAC', $hmac);

        return Bold_Checkout_Rest::buildResponse($response, json_encode(new stdClass()));
    }
}
