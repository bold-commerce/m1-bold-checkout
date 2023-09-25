<?php

/**
 * Integration request processing service.
 */
class Bold_CheckoutIntegration_Model_RequestService
{
    /**
     * Get request URL.
     *
     * @param Zend_Controller_Request_Http $httpRequest
     * @return string
     */
    public static function getRequestUrl(Zend_Controller_Request_Http $httpRequest)
    {
        $scheme = $httpRequest->getScheme() === 'https' || $httpRequest->getServer('HTTP_X_FORWARDED_PROTO') === 'https'
            ? 'https' : $httpRequest->getScheme();
        return $scheme . '://' . $httpRequest->getHttpHost(false) . $httpRequest->getRequestUri();
    }

    /**
     * Extract data from request.
     *
     * @param Zend_Controller_Request_Http $httpRequest
     * @param string $headerType
     * @return array
     * @throws Zend_Controller_Request_Exception
     */
    public static function prepareRequest(Zend_Controller_Request_Http $httpRequest, $headerType = 'oauth')
    {
        return self::processRequest(
            $httpRequest->getHeader('Authorization'),
            $headerType,
            $httpRequest->getHeader(\Zend_Http_Client::CONTENT_TYPE),
            $httpRequest->getRawBody(),
            self::getRequestUrl($httpRequest)
        );
    }

    /**
     * Extract data from request.
     *
     * @param string $authHeaderValue
     * @param string $contentTypeHeader
     * @param string $requestBodyString
     * @param string $requestUrl
     * @return array
     */
    private static function processRequest(
        $authHeaderValue,
        $authTypeHeader,
        $contentTypeHeader,
        $requestBodyString,
        $requestUrl
    ) {
        $protocolParams = [];
        if (!self::processHeader($authHeaderValue, $protocolParams, $authTypeHeader)) {
            return [];
        }
        if ($requestBodyString !== null && $contentTypeHeader
            && 0 === strpos($contentTypeHeader, \Zend_Http_Client::ENC_URLENCODED)
        ) {
            $protocolParamsNotSet = !$protocolParams;
            parse_str($requestBodyString, $protocolBodyParams);
            foreach ($protocolBodyParams as $bodyParamName => $bodyParamValue) {
                if (!self::isProtocolParameter($bodyParamName)) {
                    $protocolParams[$bodyParamName] = $bodyParamValue;
                } elseif ($protocolParamsNotSet) {
                    $protocolParams[$bodyParamName] = $bodyParamValue;
                }
            }
        }
        $protocolParamsNotSet = !$protocolParams;
        $url = Mage::getSingleton('core/url')->parseUrl($requestUrl);
        $queryString = $url->getQuery();
        self::extractQueryStringParams($protocolParams, $queryString);
        if ($protocolParamsNotSet) {
            self::fetchProtocolParamsFromQuery($protocolParams, $queryString);
        }
        return $protocolParams;
    }

    /**
     * Process header data.
     *
     * @param string $authHeaderValue
     * @param array $protocolParams
     * @param string $authTypeHeader
     * @return bool
     */
    private static function processHeader($authHeaderValue, &$protocolParams, $authTypeHeader)
    {
        if ($authTypeHeader === 'Bearer') {
            $protocolParams['oauth_token'] = str_replace('Bearer ', '', $authHeaderValue);
            return true;
        }
        $oauthValuePosition = stripos(($authHeaderValue ?: ''), 'oauth ');
        if ($authHeaderValue && $oauthValuePosition !== false) {
            // Ignore anything before and including 'OAuth ' (trailing values validated later)
            $authHeaderValue = substr($authHeaderValue, $oauthValuePosition + 6);
            foreach (explode(',', $authHeaderValue) as $paramStr) {
                $nameAndValue = explode('=', trim($paramStr), 2);

                if (count($nameAndValue) < 2) {
                    continue;
                }
                if (self::isProtocolParameter($nameAndValue[0])) {
                    $protocolParams[rawurldecode($nameAndValue[0])] = rawurldecode(trim($nameAndValue[1], '"'));
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Validate protocol parameters.
     *
     * @param string $attrName
     * @return bool
     */
    private static function isProtocolParameter($attrName)
    {
        return (bool)preg_match('/oauth_[a-z_-]+/', $attrName);
    }

    /**
     * Extract query string parameters.
     *
     * @param array $protocolParams
     * @param string $queryString
     * @return void
     */
    private static function extractQueryStringParams(&$protocolParams, $queryString)
    {
        if ($queryString) {
            foreach (explode('&', $queryString) as $paramToValue) {
                $paramData = explode('=', $paramToValue);

                if (2 === count($paramData) && !self::isProtocolParameter($paramData[0])) {
                    $protocolParams[rawurldecode($paramData[0])] = rawurldecode($paramData[1]);
                }
            }
        }
    }

    /**
     * Fetch protocol parameters from query.
     *
     * @param array $protocolParams
     * @param string $queryString
     * @return void
     */
    private static function fetchProtocolParamsFromQuery(&$protocolParams, $queryString)
    {
        if (is_array($queryString)) {
            foreach ($queryString as $queryParamName => $queryParamValue) {
                if (self::isProtocolParameter($queryParamName)) {
                    $protocolParams[$queryParamName] = $queryParamValue;
                }
            }
        }
    }
}
