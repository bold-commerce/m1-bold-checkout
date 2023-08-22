<?php

/**
 * Bold Checkout Integration 'Excluded Customer Group List' source.
 */
class Bold_Checkout_Model_System_Config_Source_Groups
{
    /**
     * Options to show.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $groups = Mage::getModel('customer/group')
            ->getCollection()
            ->addFieldToFilter('customer_group_id', ['neq' => 0])
            ->load();
        $groupOptions = [];
        foreach ($groups as $group) {
            $groupOptions[] = [
                'value' => $group->getId(),
                'label' => $group->getCustomerGroupCode(),
            ];
        }
        return $groupOptions;
    }
}
