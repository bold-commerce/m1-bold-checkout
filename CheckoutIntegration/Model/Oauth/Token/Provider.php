<?php

/**
 * Token provider service.
 */
class Bold_CheckoutIntegration_Model_Oauth_Token_Provider
{
    const USER_TYPE_INTEGRATION = 1;

    /**
     * Create request token for given consumer.
     *
     * @param Bold_CheckoutIntegration_Model_Oauth_Consumer $consumer
     * @return array
     * @throws Mage_Core_Exception
     */
    public static function createRequestToken(Bold_CheckoutIntegration_Model_Oauth_Consumer $consumer)
    {
        $token = self::getIntegrationTokenByConsumerId($consumer->getId());
        if ($token->getType() != Bold_CheckoutIntegration_Model_Oauth_Token::TYPE_VERIFIER) {
            Mage::throwException(
                Mage::helper('oauth')->__('Cannot create request token because consumer token is not a verifier token')
            );
        }
        $requestToken = $token->createRequestToken($token->getId(), $consumer->getCallbackUrl());
        return ['oauth_token' => $requestToken->getToken(), 'oauth_token_secret' => $requestToken->getSecret()];
    }

    /**
     * Create access token for consumer.
     *
     * @param Bold_CheckoutIntegration_Model_Oauth_Consumer $consumer
     * @return array
     * @throws Mage_Core_Exception
     * @throws OauthException
     */
    public static function getAccessToken(Bold_CheckoutIntegration_Model_Oauth_Consumer $consumer)
    {
        $consumerId = $consumer->getId();
        $token = self::getIntegrationTokenByConsumerId($consumerId);
        if (Bold_CheckoutIntegration_Model_Oauth_Token::TYPE_REQUEST != $token->getType()) {
            Mage::throwException(
                Mage::helper('oauth')->__('Cannot get access token because consumer token is not a request token')
            );
        }
        $accessToken = $token->convertToAccess();
        return ['oauth_token' => $accessToken->getToken(), 'oauth_token_secret' => $accessToken->getSecret()];
    }

    /**
     * Validate oauth token string format.
     *
     * @param string $oauthToken
     * @return bool
     */
    public static function validateOauthToken($oauthToken)
    {
        return $oauthToken && strlen($oauthToken) == Bold_CheckoutIntegration_Model_TokenService::LENGTH_TOKEN;
    }

    /**
     * Retrieve consumer by consumer key.
     *
     * @param string $consumerKey
     * @return Bold_CheckoutIntegration_Model_Oauth_Consumer
     * @throws Mage_Core_Exception
     */
    public static function getConsumerByKey($consumerKey)
    {
        if ($consumerKey && strlen($consumerKey) != Bold_CheckoutIntegration_Model_TokenService::LENGTH_CONSUMER_KEY) {
            Mage::throwException(
                Mage::helper('oauth')->__('Consumer key is not the correct length')
            );
        }
        $consumer = Mage::getModel(Bold_CheckoutIntegration_Model_Oauth_Consumer::RESOURCE)->loadByKey($consumerKey);
        if (!$consumer->getId()) {
            Mage::throwException(
                Mage::helper('oauth')->__('A consumer having the specified key does not exist')
            );
        }
        return $consumer;
    }

    /**
     * Load token object given a consumer Id.
     *
     * @param int $consumerId
     * @return Bold_CheckoutIntegration_Model_Oauth_Token
     */
    public static function getIntegrationTokenByConsumerId($consumerId)
    {
        /** @var Bold_CheckoutIntegration_Model_Oauth_Token $token */
        $token = Mage::getModel(Bold_CheckoutIntegration_Model_Oauth_Token::RESOURCE)->loadByConsumerIdAndUserType(
            $consumerId,
            self::USER_TYPE_INTEGRATION
        );
        if (!$token->getId()) {
            Mage::throwException(
                Mage::helper('core')->__(
                    'A token with consumer ID %s does not exist',
                    $consumerId
                )
            );
        }
        return $token;
    }

    /**
     * Validate the access token request.
     *
     * @param string $accessToken
     * @param Bold_CheckoutIntegration_Model_Oauth_Consumer $consumer
     * @return string
     * @throws Mage_Core_Exception
     */
    public static function validateAccessTokenRequest(
        $accessToken,
        Bold_CheckoutIntegration_Model_Oauth_Consumer $consumer
    ) {
        $token = self::getToken($accessToken);
        if (!self::isTokenAssociatedToConsumer($token, $consumer)) {
            Mage::throwException(
                Mage::helper('oauth')->__('Token is not associated with the specified consumer')
            );
        }
        if (Bold_CheckoutIntegration_Model_Oauth_Token::TYPE_ACCESS != $token->getType()) {
            Mage::throwException(
                Mage::helper('oauth')->__('Token is not an access token')
            );
        }
        if ($token->getRevoked()) {
            Mage::throwException(
                Mage::helper('oauth')->__('Access token has been revoked')
            );
        }
        return $token->getSecret();
    }

    /**
     * Load token model by token.
     *
     * @param string $token
     * @return Bold_CheckoutIntegration_Model_Oauth_Token
     * @throws Mage_Core_Exception
     */
    private static function getToken($token)
    {
        if (!self::validateOauthToken($token)) {
            Mage::throwException(
                Mage::helper('oauth')->__('The token length is invalid. Check the length and try again.')
            );
        }
        /** @var Bold_CheckoutIntegration_Model_Oauth_Token $tokenModel */
        $tokenModel = Mage::getModel(Bold_CheckoutIntegration_Model_Oauth_Token::RESOURCE)->load($token, 'token');
        if (!$tokenModel->getId()) {
            Mage::throwException(
                Mage::helper('oauth')->__('Specified token does not exist')
            );
        }
        return $tokenModel;
    }

    /**
     * Check if token is associated to consumer.
     *
     * @param Bold_CheckoutIntegration_Model_Oauth_Token $token
     * @param Bold_CheckoutIntegration_Model_Oauth_Consumer $consumer
     * @return bool
     */
    private static function isTokenAssociatedToConsumer(
        Bold_CheckoutIntegration_Model_Oauth_Token $token,
        Bold_CheckoutIntegration_Model_Oauth_Consumer $consumer
    ) {
        return $token->getConsumerId() == $consumer->getId();
    }

    /**
     * Validate the request token.
     *
     * @param string $requestToken
     * @param Bold_CheckoutIntegration_Model_Oauth_Consumer $consumer
     * @param string $oauthVerifier
     * @return string
     * @throws Mage_Core_Exception
     */
    public static function validateRequestToken($requestToken, $consumer, $oauthVerifier)
    {
        $token = self::getToken($requestToken);
        if (!self::isTokenAssociatedToConsumer($token, $consumer)) {
            Mage::throwException(
                Mage::helper('oauth')->__('Token is not associated with the specified consumer')
            );
        }
        if (Bold_CheckoutIntegration_Model_Oauth_Token::TYPE_REQUEST != $token->getType()) {
            Mage::throwException(
                Mage::helper('oauth')->__('Token is already being used')
            );
        }
        self::validateVerifierParam($oauthVerifier, $token->getVerifier());
        return $token->getSecret();
    }

    /**
     * Validate the verifier parameter.
     *
     * @param string $oauthVerifier
     * @param string $tokenVerifier
     * @return void
     * @throws Mage_Core_Exception
     */
    private static function validateVerifierParam($oauthVerifier, $tokenVerifier)
    {
        if (!is_string($oauthVerifier)) {
            Mage::throwException(
                Mage::helper('oauth')->__('Verifier is invalid')
            );
        }
        if (!self::validateOauthToken($oauthVerifier)) {
            Mage::throwException(
                Mage::helper('oauth')->__('Verifier is not the correct length')
            );
        }
        if (!hash_equals($tokenVerifier, $oauthVerifier)) {
            Mage::throwException(
                Mage::helper('oauth')->__('Token verifier and verifier token do not match')
            );
        }
    }
}
