<?php

/**
 * Delete order attachment observer.
 */
class Bold_CheckoutTmOrderAttachment_Observer_TaxExemptDeleteAfterObserver
{
    /**
     * Delete order attachment.
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function deleteOrderAttachment(Varien_Event_Observer $event)
    {
        $orderId = $event->getOrderId();
        try {
            $collection = Mage::getResourceModel('orderattachment/attachment_collection');
            $collection->addFieldToFilter('order_id', $orderId);
            $attachment = $collection->getFirstItem();
            if (!$attachment->getId()) {
                return;
            }
            $attachment->delete();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}
