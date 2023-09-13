<?php

/**
 * Integration model.
 */
class Bold_CheckoutIntegration_Model_Integration extends Mage_Core_Model_Abstract
{
    const RESOURCE = 'bold_checkout_integration/integration';
    const INTEGRATION_NAME_TEMPLATE = 'BoldPlatformIntegration{{websiteId}}';

    /**
     * Integration Status values
     */
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const TYPE_MANUAL = 0;

    /**
     * Integration data key constants.
     */
    const ID = 'integration_id';
    const NAME = 'name';
    const ENDPOINT = 'endpoint';
    const IDENTITY_LINK_URL = 'identity_link_url';
    const SETUP_TYPE = 'setup_type';
    const CONSUMER_ID = 'consumer_id';
    const STATUS = 'status';

    /**
     * @inheirtDoc
     */
    protected function _construct()
    {
        $this->_init(self::RESOURCE);
    }

    /**
     * Load integration by oAuth consumer ID.
     *
     * @param int $consumerId
     * @return $this
     */
    public function loadByConsumerId($consumerId)
    {
        return $this->load($consumerId, self::CONSUMER_ID);
    }

    /**
     * Load active integration by oAuth consumer ID.
     *
     * @param int $consumerId
     * @return $this
     */
    public function loadActiveIntegrationByConsumerId($consumerId)
    {
        $integrationData = $this->getResource()->selectActiveIntegrationByConsumerId($consumerId);
        $this->setData($integrationData ?: []);
        return $this;
    }

    /**
     * Get integration status. Cast to the type of STATUS_* constants in order to make strict comparison valid.
     *
     * @return int
     */
    public function getStatus()
    {
        return (int)$this->getData(self::STATUS);
    }
}
