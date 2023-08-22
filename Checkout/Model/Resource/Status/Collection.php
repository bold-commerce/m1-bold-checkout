<?php

/**
 * Entity synchronization status collection.
 */
class Bold_Checkout_Model_Resource_Status_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * @inheritDoc
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Bold_Checkout_Model_Status::RESOURCE);
    }
}
