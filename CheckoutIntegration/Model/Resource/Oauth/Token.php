<?php

/**
 * OAuth token resource model
 */
class Bold_CheckoutIntegration_Model_Resource_Oauth_Token extends Mage_Core_Model_Mysql4_Abstract
{
    const ENTITY_ID = 'entity_id';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Bold_CheckoutIntegration_Model_Oauth_Token::RESOURCE, self::ENTITY_ID);
    }

    /**
     * Select a single token of the specified type for the specified consumer.
     *
     * @param int $consumerId - The consumer id
     * @param string $type - The token type (e.g. 'verifier')
     * @return array|boolean - Row data (array) or false if there is no corresponding row
     */
    public function selectTokenByType($consumerId, $type)
    {
        $connection = $this->_getConnection('read');
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('consumer_id = ?', $consumerId)
            ->where('type = ?', $type);
        return $connection->fetchRow($select);
    }

    /**
     * Select token for a given consumer and user type.
     *
     * @param int $consumerId
     * @param int $userType
     * @return array|boolean - Row data (array) or false if there is no corresponding row
     */
    public function selectTokenByConsumerIdAndUserType($consumerId, $userType)
    {
        $connection = $this->_getConnection('read');
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('consumer_id = ?', (int)$consumerId)
            ->where('user_type = ?', (int)$userType);
        return $connection->fetchRow($select);
    }

    /**
     * @inheritdoc
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if ($object->getType() === Bold_CheckoutIntegration_Model_Oauth_Token::TYPE_ACCESS) {
            if (!empty($object->getSecret())) {
                $object->setSecret(Mage::helper('core')->encrypt($object->getSecret()));
            }
        }
        return parent::_beforeSave($object);
    }

    /**
     * @inheritdoc
     */
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        if ($object->getType() === Bold_CheckoutIntegration_Model_Oauth_Token::TYPE_ACCESS) {
            $object->setSecret(Mage::helper('core')->decrypt($object->getSecret()));
        }
        return parent::_afterLoad($object);
    }

    /**
     * @inheritdoc
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        if ($object->getType() === Bold_CheckoutIntegration_Model_Oauth_Token::TYPE_ACCESS) {
            $object->setSecret(Mage::helper('core')->decrypt($object->getSecret()));
        }
        return parent::_afterSave($object);
    }
}
