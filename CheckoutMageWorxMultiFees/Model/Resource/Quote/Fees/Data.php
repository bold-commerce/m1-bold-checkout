<?php

/**
 * Multi-fees quote data resource model.
 */
class Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data extends Mage_Core_Model_Resource_Db_Abstract
{
    const ENTITY_ID = 'id';
    const QUOTE_ID = 'quote_id';
    const DATA = 'fees_data';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(Bold_CheckoutMageWorxMultiFees_Model_Quote_Fees_Data::RESOURCE, self::ENTITY_ID);
    }
}
