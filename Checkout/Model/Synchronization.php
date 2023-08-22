<?php

/**
 * Entity synchronization model.
 */
class Bold_Checkout_Model_Synchronization extends Mage_Core_Model_Abstract
{
    const RESOURCE = 'bold_checkout/synchronization';

    /**
     * @inheritDoc
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Bold_Checkout_Model_Synchronization::RESOURCE);
    }

    /**
     *  Get id.
     *
     * @return int|null
     */
    public function getId()
    {
        return (int)$this->getData(Bold_Checkout_Model_Resource_Synchronization::ID) ?: null;
    }

    /**
     * Get entity type.
     *
     * @return string
     */
    public function getEntityTypeId()
    {
        return $this->getData(Bold_Checkout_Model_Resource_Synchronization::ENTITY_TYPE);
    }

    /**
     * Set entity type.
     *
     * @param string $entityType
     * @return void
     */
    public function setEntityTypeId($entityType)
    {
        $this->setData(Bold_Checkout_Model_Resource_Synchronization::ENTITY_TYPE, $entityType);
    }

    /**
     * Get entity id.
     *
     * @return int
     */
    public function getEntityId()
    {
        return (int)$this->getData(Bold_Checkout_Model_Resource_Synchronization::ENTITY_ID);
    }

    /**
     * Set entity id.
     *
     * @param int $entityId
     * @return void
     */
    public function setEntityId($entityId)
    {
        $this->setData(Bold_Checkout_Model_Resource_Synchronization::ENTITY_ID, $entityId);
    }

    /**
     * Get synchronized at.
     *
     * @return string
     */
    public function getSynchronizedAt()
    {
        return (string)$this->getData(Bold_Checkout_Model_Resource_Synchronization::SYNCHRONIZED_AT);
    }

    /**
     * Set synchronized at.
     *
     * @param string $synchronizedAt
     * @return void
     */
    public function setSynchronizedAt($synchronizedAt)
    {
        $this->setData(Bold_Checkout_Model_Resource_Synchronization::SYNCHRONIZED_AT, $synchronizedAt);
    }
}
