<?php

/**
 * Products platform api service.
 */
class Bold_Checkout_Api_Platform_Products
{
    /**
     * Get product list.
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
            $list = Bold_Checkout_Model_Resource_ProductListBuilder::build($limit, $cursor, $websiteId);
            $tags = Bold_Checkout_Model_Resource_ProductTagData::getTags($list->load()->getLoadedIds());
            Bold_Checkout_Service_MediaGalleryData::addToProducts($list);
            foreach ($list->getItems() as $product) {
                isset($tags[$product->getId()]) && $product->setTags($tags[$product->getId()]);
            }
            $date = Mage::getModel('core/date')->gmtDate();
            Bold_Checkout_Model_Resource_SaveEntitySynchronizationTime::save(
                $list->getColumnValues('entity_id'),
                Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_PRODUCT,
                $websiteId,
                $date
            );
            return Bold_Checkout_Service_Extractor_Product::extract($list->getItems(), $websiteId);
        };
        try {
            return Bold_Checkout_Rest::buildListResponse($request, $response, 'products', $listBuilder);
        } catch (Exception $e) {
            return Bold_Checkout_Rest::buildErrorResponse($response, $e->getMessage());
        }
    }

    /**
     * Get product.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function get(Mage_Core_Controller_Request_Http $request, Mage_Core_Controller_Response_Http $response)
    {
        $websiteId = Mage::app()->getWebsite()->getId();
        $perPage = (int)$request->getParam('per_page', 100);
        $page = (int)$request->getParam('page', 1);
        $products = Mage::getModel('catalog/product')->getCollection();
        $products->setStoreId(Mage::app()->getWebsite($websiteId)->getDefaultStore()->getId());
        $products->addAttributeToSelect(
            [
                'name',
                'tax_class_id',
                'description',
                'short_description',
                'price',
                'special_price',
                'regular_price',
                'weight',
            ]
        );
        $products->setFlag('require_stock_items', true);
        $products->setPageSize($perPage);
        $products->setCurPage($page);
        try {
            $result = Bold_Checkout_Service_Extractor_Product::extract($products->getItems(), $websiteId);
        } catch (Zend_Locale_Exception $e) {
            return Bold_Checkout_Rest::buildErrorResponse($response, $e->getMessage());
        }
        return Bold_Checkout_Rest::buildResponse($response, json_encode($result));
    }
}
