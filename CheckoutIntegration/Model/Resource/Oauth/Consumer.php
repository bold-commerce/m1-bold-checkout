<?php

/**
 * OAuth consumer resource model
 */
class Bold_CheckoutIntegration_Model_Resource_Oauth_Consumer extends Mage_Core_Model_Mysql4_Abstract
{
    const ENTITY_ID = 'entity_id';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Bold_CheckoutIntegration_Model_Oauth_Consumer::RESOURCE, self::ENTITY_ID);
    }

    /**
     * Delete all Nonce entries associated with the consumer
     *
     * @param Bold_CheckoutIntegration_Model_Oauth_Consumer $object
     * @return $this
     */
    public function _afterDelete(Mage_Core_Model_Abstract $object)
    {
        $connection = $this->_getConnection('write');
        $connection->delete($this->getTable('oauth_nonce'), ['consumer_id = ?' => (int)$object->getId()]);
        $connection->delete($this->getTable('oauth_token'), ['consumer_id = ?' => (int)$object->getId()]);
        return parent::_afterDelete($object);
    }

    /**
     * @inheritdoc
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $consumer)
    {
        if ($consumer->getSecret()) {
            $consumer->setSecret(Mage::helper('core')->encrypt($consumer->getSecret()));
        }

        return parent::_beforeSave($consumer);
    }

    /**
     * @inheritdoc
     */
    protected function _afterLoad(Mage_Core_Model_Abstract $consumer)
    {
        if ($consumer->getSecret()) {
            $consumer->setSecret(Mage::helper('core')->decrypt($consumer->getSecret()));
        }

        return parent::_afterLoad($consumer);
    }

    /**
     * @inheritdoc
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        if ($object->getSecret()) {
            $object->setSecret(Mage::helper('core')->decrypt($object->getSecret()));
        }

        return parent::_afterSave($object);
    }
}
