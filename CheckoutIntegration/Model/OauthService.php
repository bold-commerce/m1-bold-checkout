<?php

/**
 * Integration oAuth service.
 *
 */
class Bold_CheckoutIntegration_Model_OauthService
{
    const SIGNATURE_SHA256 = 'HMAC-SHA256';
    const ERR_SIGNATURE_INVALID = 7;

    /**
     * Verify the signature of the request.
     *
     * @param array $request
     * @param string $requestUrl
     * @param string $httpMethod
     * @return int|null
     * @throws Mage_Core_Exception
     */
    public static function validateAccessTokenRequest(
        array $request,
        $requestUrl,
        $httpMethod = 'POST',
        $headerType = 'oauth'
    ) {
        if ($headerType === 'Bearer') {
            $tokenModel = Mage::getModel(Bold_CheckoutIntegration_Model_Oauth_Token::RESOURCE)->load(
                $request['oauth_token'],
                'token'
            );
            return $tokenModel->getConsumerId();
        }
        $required = [
            'oauth_consumer_key',
            'oauth_signature',
            'oauth_signature_method',
            'oauth_nonce',
            'oauth_timestamp',
            'oauth_token',
        ];
        self::validateProtocolParams($request, $required);
        $consumer = Bold_CheckoutIntegration_Model_Oauth_Token_Provider::getConsumerByKey($request['oauth_consumer_key']);
        $tokenSecret = Bold_CheckoutIntegration_Model_Oauth_Token_Provider::validateAccessTokenRequest(
            $request['oauth_token'],
            $consumer
        );
        self::validateSignature(
            $request,
            $consumer->getSecret(),
            $httpMethod,
            $requestUrl,
            $tokenSecret
        );
        return $consumer->getId();
    }

    /**
     * Get request token.
     *
     * @param array $request
     * @param string $requestUrl
     * @param string $httpMethod
     * @return array
     * @throws Mage_Core_Exception
     */
    public static function getRequestToken(array $request, $requestUrl, $httpMethod)
    {
        self::validateProtocolParams($request);
        $consumer = Bold_CheckoutIntegration_Model_Oauth_Token_Provider::getConsumerByKey($request['oauth_consumer_key']);
        self::validateSignature($request, $consumer->getSecret(), $httpMethod, $requestUrl);

        return Bold_CheckoutIntegration_Model_Oauth_Token_Provider::createRequestToken($consumer);
    }

    /**
     * Validate protocol parameters.
     *
     * @param array $request
     * @param array $requiredParams
     * @return void
     * @throws Mage_Core_Exception
     */
    private static function validateProtocolParams(array $request, array $requiredParams = [])
    {
        if (isset($request['oauth_version'])) {
            self::validateVersionParam($request['oauth_version']);
        }
        // Required parameters validation. Default to minimum required params if not provided.
        if (empty($requiredParams)) {
            $requiredParams = [
                'oauth_consumer_key',
                'oauth_signature',
                'oauth_signature_method',
                'oauth_nonce',
                'oauth_timestamp',
            ];
        }
        self::checkRequiredParams($request, $requiredParams);
        if (isset($request['oauth_token'])
            && !Bold_CheckoutIntegration_Model_Oauth_Token_Provider::validateOauthToken($request['oauth_token'])
        ) {
            Mage::throwException(
                Mage::helper('core')->__('The token length is invalid. Check the length and try again.')
            );
        }
        if (!in_array($request['oauth_signature_method'], self::getSupportedSignatureMethods())) {
            Mage::throwException(
                Mage::helper('core')->__(
                    'Signature method %1 is not supported',
                    [$request['oauth_signature_method']]
                )
            );
        }
    }

    /**
     * Get supported signature methods.
     *
     * @return string[]
     */
    private static function getSupportedSignatureMethods()
    {
        return [self::SIGNATURE_SHA256];
    }

    /**
     * Validate version parameter.
     *
     * @param string $version
     * @return void
     * @throws Mage_Core_Exception
     */
    private static function validateVersionParam($version)
    {
        if ('1.0' != $version) {
            Mage::throwException(
                Mage::helper('core')->__('The "%s" Oauth version isn\'t supported.', $version)
            );
        }
    }

    /**
     * Check required parameters.
     *
     * @param array $protocolParams
     * @param array $requiredParams
     * @return void
     * @throws Mage_Core_Exception
     */
    private static function checkRequiredParams($protocolParams, array $requiredParams)
    {
        foreach ($requiredParams as $param) {
            if (!isset($protocolParams[$param])) {
                Mage::throwException(
                    Mage::helper('core')->__('"%s" is required. Enter and try again.', $param)
                );
            }
        }
    }

    public static function generateAccessToken(array $request, $requestUrl, $httpMethod)
    {
        $required = [
            'oauth_consumer_key',
            'oauth_signature',
            'oauth_signature_method',
            'oauth_nonce',
            'oauth_timestamp',
            'oauth_token',
            'oauth_verifier',
        ];
        self::validateProtocolParams($request, $required);
        $consumer = Bold_CheckoutIntegration_Model_Oauth_Token_Provider::getConsumerByKey($request['oauth_consumer_key']);
        $tokenSecret = Bold_CheckoutIntegration_Model_Oauth_Token_Provider::validateRequestToken(
            $request['oauth_token'],
            $consumer,
            $request['oauth_verifier']
        );
        self::validateSignature(
            $request,
            $consumer->getSecret(),
            $httpMethod,
            $requestUrl,
            $tokenSecret
        );
        return Bold_CheckoutIntegration_Model_Oauth_Token_Provider::getAccessToken($consumer);
    }

    /**
     * Validate the signature.
     *
     * @param array $request
     * @param string $consumerSecret
     * @param string $httpMethod
     * @param string $requestUrl
     * @param string $secret
     * @return void
     * @throws Mage_Core_Exception
     */
    private static function validateSignature($request, $consumerSecret, $httpMethod, $requestUrl, $secret = null)
    {
        if (!in_array($request['oauth_signature_method'], self::getSupportedSignatureMethods())) {
            Mage::throwException(
                Mage::helper('core')->__(
                    'Signature method %1 is not supported',
                    [$request['oauth_signature_method']]
                )
            );
        }

        $allowedSignParams = $request;
        unset($allowedSignParams['oauth_signature']);
        $util = new Zend_Oauth_Http_Utility();
        $calculatedSign = $util->sign(
            $allowedSignParams,
            $request['oauth_signature_method'],
            $consumerSecret,
            $secret,
            $httpMethod,
            $requestUrl
        );

        if (!hash_equals($calculatedSign, $request['oauth_signature'])) {
            throw Mage::exception('Mage_Oauth', '', self::ERR_SIGNATURE_INVALID);
        }
    }

    /**
     * Create consumer.
     *
     * @param array $consumerData
     */
    public static function createConsumer(array $consumerData)
    {
        $consumerData['consumer_key'] = Bold_CheckoutIntegration_Model_TokenService::generateConsumerKey();
        $consumerData['secret'] = Bold_CheckoutIntegration_Model_TokenService::generateConsumerSecret();
        $consumer = Mage::getModel(Bold_CheckoutIntegration_Model_Oauth_Consumer::RESOURCE)->setData($consumerData);
        $consumer->save();
        return $consumer;
    }

    /**
     * Get access token by consumer ID.
     *
     * @param int $consumerId
     */
    public static function getAccessToken($consumerId)
    {
        try {
            $consumer = Mage::getModel(Bold_CheckoutIntegration_Model_Oauth_Consumer::RESOURCE)->load($consumerId);
            $token = Bold_CheckoutIntegration_Model_Oauth_Token_Provider::getIntegrationTokenByConsumerId($consumer->getId());
            if ($token->getType() !== Bold_CheckoutIntegration_Model_Oauth_Token::TYPE_ACCESS) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
        return $token;
    }

    /**
     * Load consumer by ID.
     *
     * @param int $consumerId
     * @return Bold_CheckoutIntegration_Model_Oauth_Consumer
     */
    public static function loadConsumer($consumerId)
    {
        /** @var Bold_CheckoutIntegration_Model_Oauth_Consumer $consumer */
        $consumer = Mage::getModel(Bold_CheckoutIntegration_Model_Oauth_Consumer::RESOURCE)->load($consumerId);
        return $consumer;
    }

    /**
     * Load consumer by key.
     *
     * @param string $key
     * @return Bold_CheckoutIntegration_Model_Oauth_Consumer
     */
    public static function loadConsumerByKey($key)
    {
        /** @var Bold_CheckoutIntegration_Model_Oauth_Consumer $consumer */
        $consumer = Mage::getModel(Bold_CheckoutIntegration_Model_Oauth_Consumer::RESOURCE)
            ->load($key, 'consumer_key');
        return $consumer;
    }

    /**
     * Send keys to platform connector.
     *
     * @param int $consumerId
     * @param string $endpointUrl
     * @return bool
     * @throws Mage_Core_Exception
     */
    public static function postToConsumer($consumerId, $endpointUrl)
    {
        $consumer = self::loadConsumer($consumerId);
        $consumer->setUpdatedAt(Mage::getModel('core/date')->gmtDate());
        $consumer->save();
        if (!$consumer->getId()) {
            Mage::throwException(
                Mage::helper('core')->__(
                    'A consumer with "%s" ID doesn\'t exist. Verify the ID and try again.',
                    $consumerId
                )
            );
        }
        $consumerData = $consumer->getData();
        $verifier = Mage::getModel(Bold_CheckoutIntegration_Model_Oauth_Token::RESOURCE)->createVerifierToken($consumerId);
        $storeBaseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $payload = [
            'oauth_consumer_key' => $consumerData['consumer_key'],
            'oauth_consumer_secret' => $consumerData['secret'],
            'store_base_url' => $storeBaseUrl,
            'oauth_verifier' => $verifier->getVerifier(),
        ];
        $result = Bold_CheckoutIntegration_HttpClient::call('POST', $endpointUrl, $payload);
        return $result === 'OK';
    }

    /**
     * Delete integration token.
     *
     * @param int $consumerId
     * @return void
     */
    public static function deleteIntegrationToken($consumerId)
    {
        try {
            /** @var Bold_CheckoutIntegration_Model_Oauth_Consumer $consumer */
            $consumer = Mage::getModel(Bold_CheckoutIntegration_Model_Oauth_Consumer::RESOURCE)->load($consumerId);
            $existingToken = Bold_CheckoutIntegration_Model_Oauth_Token_Provider::getIntegrationTokenByConsumerId($consumer->getId());
            $existingToken->delete();
        } catch (\Exception $e) {
            return;
        }
    }
}
