<?php

/**
 * Build customer collection for customers export to bold.
 */
class Bold_Checkout_Model_Resource_CustomerListBuilder
{
    /**
     * Prepare customer collection.
     *
     * @param int $limit
     * @param int $cursor
     * @param int $websiteId
     * @param array $params
     * @return Mage_Customer_Model_Resource_Customer_Collection
     * @throws Exception
     */
    public static function build($limit, $cursor, $websiteId, $params = [])
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        /** @var Mage_Customer_Model_Resource_Customer_Collection $list */
        $list = $customer->getCollection();
        if ($customer->getSharingConfig()->isWebsiteScope()) {
            $list->addAttributeToFilter(
                'website_id',
                $websiteId
            );
        }
        if (isset($params['email'])) {
            $list->addAttributeToFilter('email', $params['email']);
        }
        $list->addAttributeToSelect('firstname');
        $list->addAttributeToSelect('lastname');
        $list->addAttributeToSelect('default_billing');
        $list->addAttributeToSelect('default_shipping');
        $list->setPageSize($limit);
        $list->setCurPage($cursor);
        return $list;
    }
}
