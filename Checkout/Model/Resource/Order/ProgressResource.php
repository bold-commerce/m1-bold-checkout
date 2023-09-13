<?php

/**
 * Bold Checkout Quote Progress Resource Model.
 */
class Bold_Checkout_Model_Resource_Order_ProgressResource
{
    const TABLE = 'bold_checkout_quote_progress';

    /**
     * Verify if order creation is in progress.
     *
     * @param int $quoteId
     * @return bool
     */
    public static function getIsInProgress($quoteId)
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $table = $resource->getTableName(self::TABLE);
        $sql = $readConnection->select()->from($table)->where('quote_id = (?)', $quoteId);
        return $readConnection->fetchOne($sql) !== false;
    }

    /**
     * Create order creation progress record.
     *
     * @param int $quoteId
     * @return void
     */
    public static function create($quoteId)
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $writeConnection->insertOnDuplicate(
            $resource->getTableName(self::TABLE),
            ['quote_id' => $quoteId],
            ['quote_id']
        );
    }

    /**
     * Delete order creation progress record.
     *
     * @param int $quoteId
     * @return void
     */
    public static function delete($quoteId)
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $writeConnection->delete(
            $resource->getTableName(self::TABLE),
            ['quote_id = (?)' => $quoteId]
        );
    }
}
