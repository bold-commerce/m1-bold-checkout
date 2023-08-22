<?php

/**
 * Create order attachment observer.
 */
class Bold_CheckoutTmOrderAttachment_Observer_TaxExemptDocumentUploadAfterObserver
{
    /**
     * Create order attachment.
     *
     * @param Varien_Event_Observer $event
     */
    public function createOrderAttachment(Varien_Event_Observer $event)
    {
        $orderId = $event->getOrderId();
        $file = $event->getFile();
        $comment = $event->getComment();
        if (!$orderId || !$file) {
            return;
        }
        try {
            $collection = Mage::getResourceModel('orderattachment/attachment_collection');
            $collection->addFieldToFilter('order_id', $orderId);
            $attachment = $collection->getFirstItem();
            $order = Mage::getModel('sales/order')->load($orderId);
            $attachment->addData(
                [
                    'quote_id' => $order->getQuoteId(),
                    'order_id' => $orderId,
                    'comment' => $comment,
                    'path' => $file,
                    'hash' => Mage::helper('orderattachment/url')->generateHash(),
                ]
            );
            $attachment->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}
