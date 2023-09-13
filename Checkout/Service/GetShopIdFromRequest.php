<?php

/**
 * Get shop Id from request.
 */
class Bold_Checkout_Service_GetShopIdFromRequest
{
    /**
     * Retrieve shop Id from request.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @return string|null
     */
    public static function getShopId(Mage_Core_Controller_Request_Http $request)
    {
        $requestUri = $request->getRequestUri();
        if (preg_match('#/rest/V1/shops/([a-fA-F0-9]{32})/#', $requestUri, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
