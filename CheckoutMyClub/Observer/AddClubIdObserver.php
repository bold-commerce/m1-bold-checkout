<?php

/**
 * Store club id for correct quote collect observer.
 *
 * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 */
class Bold_CheckoutMyClub_Observer_AddClubIdObserver
{
    /**
     * Store club id during request.
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function addClubId(Varien_Event_Observer $event)
    {
        $lineItems = $event->getRequest();
        $clubId = $this->getClubId($lineItems);
        if ($clubId) {
            Mage::helper('catalog/myclub')->addClubId($clubId);
        }
    }

    /**
     * Retrieve club id from line item properties.
     *
     * @param array $lineItems
     * @return string|null
     */
    private function getClubId(array $lineItems)
    {
        foreach ($lineItems as $lineItem) {
            if (isset($lineItem->line_item_properties->_club_id)) {
                return $lineItem->line_item_properties->_club_id;
            }
            if (isset($lineItem->properties->_club_id)) {
                return $lineItem->properties->_club_id;
            }
            if (isset($lineItem->custom_attributes->_club_id->value)) {
                return $lineItem->custom_attributes->_club_id->value;
            }
        }

        return null;
    }
}
