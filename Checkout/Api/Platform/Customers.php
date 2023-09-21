<?php

/**
 * Platform customer service.
 */
class Bold_Checkout_Api_Platform_Customers
{
    /**
     * Retrieve magento customers.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function search(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        return Bold_Checkout_Rest::buildResponse(
            $response,
            json_encode(
                Bold_Checkout_Model_CustomerListBuilder::build($request->getQuery())
            )
        );
    }
}
