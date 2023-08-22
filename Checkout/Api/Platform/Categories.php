<?php

/**
 * Platform categories service.
 */
class Bold_Checkout_Api_Platform_Categories
{
    /**
     * Get all magento categories list.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function getList(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        $listBuilder = function ($limit, $cursor, $websiteId) {
            $list = Bold_Checkout_Model_Resource_CategoryListBuilder::build($limit, $cursor, $websiteId);
            $date = Mage::getModel('core/date')->gmtDate();
            Bold_Checkout_Model_Resource_SaveEntitySynchronizationTime::save(
                $list->getColumnValues('entity_id'),
                Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CATEGORY,
                $websiteId,
                $date
            );
            return Bold_Checkout_Service_Extractor_Category::extract($list->getItems());
        };
        try {
            return Bold_Checkout_Rest::buildListResponse($request, $response, 'categories', $listBuilder);
        } catch (Exception $e) {
            return Bold_Checkout_Rest::buildErrorResponse($response, $e->getMessage());
        }
    }
}
