<?php

/**
 * Token model.
 */
class Bold_CheckoutIntegration_Model_Oauth_Token extends Mage_Core_Model_Abstract
{
    const RESOURCE = 'bold_checkout_integration/oauth_token';

    /**
     * Token types
     */
    const TYPE_REQUEST = 'request';
    const TYPE_ACCESS = 'access';
    const TYPE_VERIFIER = 'verifier';
    const LENGTH_SECRET = 32;
    const LENGTH_TOKEN = 32;
    const LENGTH_TOKEN_VERIFIER = 32;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::RESOURCE);
    }

    /**
     * Generate an oauth_verifier for a consumer, if the consumer doesn't already have one.
     *
     * @param int $consumerId
     * @return Bold_CheckoutIntegration_Model_Oauth_Token
     * @throws Mage_Core_Exception
     */
    public function createVerifierToken($consumerId)
    {
        $tokenData = $this->getResource()->selectTokenByType($consumerId, self::TYPE_VERIFIER);
        $this->setData($tokenData ?: []);
        if (!$this->getId()) {
            $this->setData(
                [
                    'consumer_id' => $consumerId,
                    'type' => self::TYPE_VERIFIER,
                    'token' => Bold_CheckoutIntegration_Model_TokenService::generateToken(),
                    'secret' => Bold_CheckoutIntegration_Model_TokenService::generateTokenSecret(),
                    'verifier' => Bold_CheckoutIntegration_Model_TokenService::generateVerifier(),
                    'callback_url' => Bold_CheckoutIntegration_Model_TokenService::CALLBACK_ESTABLISHED,
                    'user_type' => Bold_CheckoutIntegration_Model_Oauth_Token_Provider::USER_TYPE_INTEGRATION,
                    //As of now only integrations use Oauth
                ]
            );
            $this->validate();
            $this->save();
        }
        return $this;
    }

    /**
     * Convert token to access type
     *
     * @return Bold_CheckoutIntegration_Model_Oauth_Token
     * @throws OauthException
     */
    public function convertToAccess()
    {
        if (self::TYPE_REQUEST != $this->getType()) {
            throw new OauthException(
                'Cannot convert to access token due to token is not request type'
            );
        }
        return $this->saveAccessToken(Bold_CheckoutIntegration_Model_Oauth_Token_Provider::USER_TYPE_INTEGRATION);
    }

    /**
     * Generate and save request token
     *
     * @param int $entityId Token identifier
     * @param string $callbackUrl Callback URL
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function createRequestToken($entityId, $callbackUrl)
    {
        $callbackUrl = !empty($callbackUrl) ? $callbackUrl : Bold_CheckoutIntegration_Model_TokenService::CALLBACK_ESTABLISHED;
        $this->setData(
            [
                'entity_id' => $entityId,
                'type' => self::TYPE_REQUEST,
                'token' => Bold_CheckoutIntegration_Model_TokenService::generateToken(),
                'secret' => Bold_CheckoutIntegration_Model_TokenService::generateTokenSecret(),
                'callback_url' => $callbackUrl,
            ]
        );
        $this->validate();
        $this->save();

        return $this;
    }

    /**
     * Get string representation of token
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __toString()
    {
        return http_build_query(['oauth_token' => $this->getToken(), 'oauth_token_secret' => $this->getSecret()]);
    }

    /**
     * Validate data
     *
     * @return bool
     * @throws Mage_Core_Exception
     */
    private function validate()
    {
        /** @var Mage_Core_Model_Url_Validator $validatorUrl */
        $validatorUrl = Mage::getSingleton('core/url_validator');
        if (Bold_CheckoutIntegration_Model_TokenService::CALLBACK_ESTABLISHED != $this->getCallbackUrl()
            && !$validatorUrl->isValid($this->getCallbackUrl())
        ) {
            $messages = $validatorUrl->getMessages();
            Mage::throwException(array_shift($messages));
        }

        $validatorLength = Mage::getModel('oauth/consumer_validator_keyLength');
        $validatorLength->setLength(self::LENGTH_SECRET);
        $validatorLength->setName('Token Secret Key');
        if (!$validatorLength->isValid($this->getSecret())) {
            $messages = $validatorLength->getMessages();
            Mage::throwException(array_shift($messages));
        }

        $validatorLength->setLength(self::LENGTH_TOKEN);
        $validatorLength->setName('Token Key');
        if (!$validatorLength->isValid($this->getToken())) {
            $messages = $validatorLength->getMessages();
            Mage::throwException(array_shift($messages));
        }

        if (null !== ($verifier = $this->getVerifier())) {
            $validatorLength->setLength(self::LENGTH_TOKEN_VERIFIER);
            $validatorLength->setName('Verifier Key');
            if (!$validatorLength->isValid($verifier)) {
                $messages = $validatorLength->getMessages();
                Mage::throwException(array_shift($messages));
            }
        }
        return true;
    }

    /**
     * Return the token's verifier.
     *
     * @return string
     */
    public function getVerifier()
    {
        return $this->getData('verifier');
    }

    /**
     * Generate and save access token for a given user type
     *
     * @param int $userType
     * @return Bold_CheckoutIntegration_Model_Oauth_Token
     * @throws Exception
     */
    private function saveAccessToken($userType)
    {
        $this->setUserType($userType);
        $this->setType(self::TYPE_ACCESS);
        $this->setToken(Bold_CheckoutIntegration_Model_TokenService::generateToken());
        $this->setSecret(Bold_CheckoutIntegration_Model_TokenService::generateTokenSecret());
        return $this->save();
    }

    /**
     * Get token by consumer and user type
     *
     * @param int $consumerId
     * @param int $userType
     * @return $this
     */
    public function loadByConsumerIdAndUserType($consumerId, $userType)
    {
        $tokenData = $this->getResource()->selectTokenByConsumerIdAndUserType($consumerId, $userType);
        $this->setData($tokenData ?: []);
        $this->getResource()->afterLoad($this);
        return $this;
    }
}
