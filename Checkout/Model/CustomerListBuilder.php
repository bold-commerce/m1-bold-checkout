<?php

/**
 * Build customer collection for customers export to bold.
 */
class Bold_Checkout_Model_CustomerListBuilder
{
    const LIMIT = 100;
    const CURSOR = 1;

    /**
     * Prepare customer collection.
     *
     * @param array $queryParameters
     * @return stdClass
     */
    public static function build(array $queryParameters = [])
    {
        $limit = isset($queryParameters['searchCriteria']['pageSize'])
            ? (int)$queryParameters['searchCriteria']['pageSize']
            : self::LIMIT;
        $cursor = isset($queryParameters['searchCriteria']['currentPage'])
            ? (int)$queryParameters['searchCriteria']['currentPage']
            : self::CURSOR;
        $filterGroups = isset($queryParameters['searchCriteria']['filterGroups'])
            ? $queryParameters['searchCriteria']['filterGroups']
            : [];
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        /** @var Mage_Customer_Model_Resource_Customer_Collection $list */
        $list = $customer->getCollection();
        foreach ($filterGroups as $filterGroup) {
            $filters = isset ($filterGroup['filters']) ? $filterGroup['filters'] : [];
            foreach ($filters as $filter) {
                $list->addAttributeToFilter(
                    $filter['field'],
                    [$filter['conditionType'] => $filter['value']]
                );
            }
        }
        $list->addAttributeToSelect(['*']);
        $list->setPageSize($limit);
        $list->setCurPage($cursor);
        $result = new stdClass();
        $result->items = Bold_Checkout_Service_Extractor_Customer::extract($list->getItems());
        $result->total_count = $list->getSize();
        $result->search_criteria = isset($queryParameters['searchCriteria']) ? $queryParameters['searchCriteria'] : [];
        return $result;
    }
}
