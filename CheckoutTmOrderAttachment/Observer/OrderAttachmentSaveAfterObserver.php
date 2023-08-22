<?php

/**
 * Save tax exempt file and comment.
 */
class Bold_CheckoutTmOrderAttachment_Observer_OrderAttachmentSaveAfterObserver
{
    /**
     * Save tax exempt file and comment.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception
     */
    public function saveTaxExempt(Varien_Event_Observer $event)
    {
        $attachment = $event->getObject();
        $orderId = $attachment->getOrderId();
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $extOrderData->load($orderId, Bold_Checkout_Model_Resource_Order::ORDER_ID);
        if (!$extOrderData->getisTaxExempt()) {
            return;
        }
        $extOrderData->setTaxExemptFile($attachment->getPath());
        $extOrderData->setTaxExemptComment($attachment->getComment());
        $extOrderData->save();
    }
}
