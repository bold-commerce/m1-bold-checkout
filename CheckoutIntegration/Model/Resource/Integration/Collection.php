<?php

class Bold_CheckoutIntegration_Model_Resource_Integration_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * @inheirtDoc
     */
    protected function _construct()
    {
        $this->_init(Bold_CheckoutIntegration_Model_Integration::RESOURCE);
    }

    /**
     * @inheirtDoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->joinLeft(
            ['oauth_consumer' => $this->getTable(Bold_CheckoutIntegration_Model_Oauth_Consumer::RESOURCE)],
            'main_table.consumer_id = oauth_consumer.entity_id',
            ['consumer_key', 'secret']
        );
        return $this;
    }
}
