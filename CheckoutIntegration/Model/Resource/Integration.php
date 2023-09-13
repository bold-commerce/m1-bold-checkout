<?php
/**
 * Integration resource model
 */
class Bold_CheckoutIntegration_Model_Resource_Integration extends Mage_Core_Model_Mysql4_Abstract
{
    const ENTITY_ID = 'integration_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(\Bold_CheckoutIntegration_Model_Integration::RESOURCE, self::ENTITY_ID);
    }

    /**
     * Select token for a given customer.
     *
     * @param int $consumerId
     * @return array|boolean - Row data (array) or false if there is no corresponding row
     */
    public function selectActiveIntegrationByConsumerId($consumerId)
    {
        $connection = $this->_getConnection('read');
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('consumer_id = ?', $consumerId)
            ->where('status = ?', Bold_CheckoutIntegration_Model_Integration::STATUS_ACTIVE);
        return $connection->fetchRow($select);
    }
}
