<?php

/**
 * Add club id to line item observer.
 *
 * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 */
class Bold_CheckoutMyClub_Observer_AddClubIdToLineItemsObserver
{
    /**
     * Add club id to line item.
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function addClubId(Varien_Event_Observer $event)
    {
        $clubId = Mage::helper('catalog/myclub')->registry('Club_id');
        if (!$clubId) {
            return;
        }
        $lineItem = $event->getLineItem();
        $lineItem->line_item_properties->_club_id = $clubId;
    }
}
