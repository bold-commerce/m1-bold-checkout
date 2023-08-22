<?php

/**
 * Retrieve order list created with bold checkout.
 */
class Bold_Checkout_Model_Resource_OrderListBuilder
{
    /**
     * Retrieve order list created with bold checkout.
     *
     * @param int $limit
     * @param int $cursor
     * @param int $websiteId
     * @return array
     * @throws Exception
     */
    public static function buildList($limit, $cursor, $websiteId)
    {
        /** @var Mage_Sales_Model_Resource_Order_Collection $list */
        $list = Mage::getModel('sales/order')->getCollection();
        $list->getSelect()->join(
            ['ext_data' => $list->getTable(Bold_Checkout_Model_Order::RESOURCE)],
            'main_table.entity_id = ext_data.order_id',
            []
        );
        $storeIds = Mage::app()->getWebsite($websiteId)->getStoreIds();
        $list->addFieldToFilter('store_id', ['in' => $storeIds]);
        $list->setPageSize($limit);
        $list->setCurPage($cursor);

        return Bold_Checkout_Service_Extractor_Order::extract($list->getItems());
    }
}
