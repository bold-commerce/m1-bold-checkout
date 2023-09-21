<?php

/**
 * OAuth consumer model.
 */
class Bold_CheckoutIntegration_Model_Oauth_Consumer extends Mage_Core_Model_Abstract
{
    const RESOURCE = 'bold_checkout_integration/oauth_consumer';

    /**
     * @inheirtDoc
     */
    protected function _construct()
    {
        $this->_init(self::RESOURCE);
    }

    /**
     * Load consumer data by consumer key.
     *
     * @param string $key
     * @return $this
     */
    public function loadByKey($key)
    {
        return $this->load($key, 'consumer_key');
    }

    /**
     * Get consumer key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->getData('consumer_key');
    }

    /**
     * Get consumer secret.
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->getData('secret');
    }

    /**
     * Get callback URL.
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->getData('callback_url');
    }

    /**
     * Get created at date.
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }
}
