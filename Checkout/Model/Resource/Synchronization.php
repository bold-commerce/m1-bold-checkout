<?php

/**
 * Entity synchronization resource model.
 *
 * @phpcs:disable
 */
class Bold_Checkout_Model_Resource_Synchronization extends Mage_Core_Model_Mysql4_Abstract
{
    const ID = 'id';
    const ENTITY_TYPE = 'entity_type';
    const ENTITY_ID = 'entity_id';
    const WEBSITE_ID = 'website_id';
    const SYNCHRONIZED_AT = 'synchronized_at';

    /**
     * @inheritDoc
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Bold_Checkout_Model_Synchronization::RESOURCE, self::ID);
    }
}
