<?php

/**
 * Entity synchronization lock resource model.
 */
class Bold_Checkout_Model_Resource_Status extends Mage_Core_Model_Mysql4_Abstract
{
    const ID = 'id';
    const ENTITY_TYPE = 'entity_type';
    const STATUS = 'status';

    /**
     * @inheritDoc
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Bold_Checkout_Model_Status::RESOURCE, self::ID);
    }
}
