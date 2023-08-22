<?php

/**
 * Entity synchronization status model.
 */
class Bold_Checkout_Model_Status extends Mage_Core_Model_Abstract
{
    const RESOURCE = 'bold_checkout/status';

    const STATUS_READY = 'ready';

    const STATUS_PROCESSING = 'processing';

    /**
     * @inheritDoc
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::RESOURCE);
    }

    /**
     *  Get id.
     *
     * @return int|null
     */
    public function getId()
    {
        return (int)$this->getData(Bold_Checkout_Model_Resource_Status::ID) ?: null;
    }

    /**
     * Get entity type.
     *
     * @return string
     */
    public function getEntityType()
    {
        return $this->getData(Bold_Checkout_Model_Resource_Status::ENTITY_TYPE);
    }

   /**
    * Set entity type.
    *
    * @param string $entityType
    * @return void
    */
    public function setEntityTypeId($entityType)
    {
        $this->setData(Bold_Checkout_Model_Resource_Status::ENTITY_TYPE, $entityType);
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->getData(Bold_Checkout_Model_Resource_Status::STATUS);
    }

    /**
     * Set status.
     *
     * @param string $status
     * @return void
     */
    public function setStatus($status)
    {
        $this->setData(Bold_Checkout_Model_Resource_Status::STATUS, $status);
    }

    /**
     * Is status field value 'processing'.
     *
     * @return bool
     */
    public function isProcessing()
    {
        return $this->getStatus() === self::STATUS_PROCESSING;
    }

    /**
     * Set 'processing' status and save.
     *
     * @return void
     * @throws Exception
     */
    public function lock()
    {
        $this->setStatus(self::STATUS_PROCESSING);
        $this->save();
    }

    /**
     * Set 'ready' status and save.
     *
     * @return void
     * @throws Exception
     */
    public function unlock()
    {
        $this->setStatus(self::STATUS_READY);
        $this->save();
    }
}
