<?php

/**
 * Checkout order additional data resource model.
 */
class Bold_Checkout_Model_Resource_Order extends Mage_Core_Model_Mysql4_Abstract
{
    const ENTITY_ID = 'id';
    const ORDER_ID = 'order_id';
    const PUBLIC_ID = 'public_id';
    const FINANCIAL_STATUS = 'financial_status';
    const FULFILLMENT_STATUS = 'fulfillment_status';
    const IS_DELAYED_CAPTURE = 'is_delayed_capture';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(Bold_Checkout_Model_Order::RESOURCE, self::ENTITY_ID);
    }
}
