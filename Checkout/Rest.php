<?php

/**
 * Bold checkout api response builder.
 */
class Bold_Checkout_Rest
{
    /**
     * Execute list and return result.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @param string $resource
     * @param Closure $listBuilder
     * @return Mage_Core_Controller_Response_Http
     */
    public static function buildListResponse(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response,
        $resource,
        Closure $listBuilder
    ) {
        $limit = (int)$request->getParam('limit', 100);
        $cursor = (int)$request->getParam('cursor', 1);
        $websiteId = (int)Mage::app()->getWebsite()->getId();
        $list = $listBuilder($limit, $cursor, $websiteId, $request->getParams());
        $pagination = ['prev' => $cursor > 1 ? (string)($cursor - 1) : "1"];
        if (count($list) === $limit) {
            $pagination['next'] = (string)($cursor + 1);
        }
        $result = [
            'data' => [$resource => $list],
            'pagination' => $pagination,
        ];

        return Bold_Checkout_Rest::buildResponse($response, json_encode($result));
    }

    /**
     * Fill response with data.
     *
     * @param Mage_Core_Controller_Response_Http $response
     * @param string|null $body
     * @param int $code
     * @param string $contentType
     * @return Mage_Core_Controller_Response_Http
     */
    public static function buildResponse(
        Mage_Core_Controller_Response_Http $response,
        $body = null,
        $code = 200,
        $contentType = 'application/json'
    ) {
        try {
            $response->setHttpResponseCode($code);
        } catch (Zend_Controller_Response_Exception $e) {
            $response->setHttpResponseCode(200);
        }
        $response->setHeader('Content-Type', $contentType, true);
        $response->setBody($body);
        // phpcs:disable MEQP1.Security.DiscouragedFunction.Found
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        // phpcs:enable MEQP1.Security.DiscouragedFunction.Found

        return $response;
    }

    /**
     * Fill response with error data.
     *
     * @param Mage_Core_Controller_Response_Http $response
     * @param string $message
     * @param int $code
     * @param string $type
     * @return Mage_Core_Controller_Response_Http
     */
    public static function buildErrorResponse(
        Mage_Core_Controller_Response_Http $response,
        $message,
        $code = 500,
        $type = 'server.internal_error'
    ) {
        // At the moment bold only process errors with 500 error code.
        $response->setHttpResponseCode(500);
        $response->setHeader('Content-Type', 'application/json');
        $errors = [
            'errors' => [
                'code' => $code,
                'message' => $message,
                'type' => $type,
            ],
        ];
        $response->setBody(json_encode($errors));
        // phpcs:disable MEQP1.Security.DiscouragedFunction.Found
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        // phpcs:enable MEQP1.Security.DiscouragedFunction.Found

        return $response;
    }
}
