<?php

/**
 * Get website id for given shop Id.
 */
class Bold_Checkout_Service_GetWebsiteIdByShopId
{
    /**
     * Retrieve website id for given shop Id.
     *
     * @param string $shopId
     * @return int
     * @throws Mage_Core_Exception
     */
    public static function getWebsiteId($shopId)
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
        $select = $connection->select()
            ->from($resource->getTableName('core/config_data'), ['scope_id'])
            ->where('path = ?', Bold_Checkout_Model_Config::PATH_SHOP_IDENTIFIER)
            ->where('value = ?', $shopId);
        $websiteId = $connection->fetchOne($select);
        if ($websiteId === false) {
            Mage::throwException(
                Mage::helper('core')->__(
                    'No website found for "%1" shop Id.',
                    $shopId
                )
            );
        }

        return (int)$websiteId;
    }
}
