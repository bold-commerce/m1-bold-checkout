<?php

/**
 * Remove order tax-exempt observer.
 */
class Bold_CheckoutTmOrderAttachment_Observer_OrderAttachmentDeleteAfterObserver
{
    /**
     * Delete order tax exempt after order attachments has been deleted.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception
     */
    public function deleteTaxExempt(Varien_Event_Observer $event)
    {
        $orderId = $event->getObject()->getOrderId();
        if (!$orderId) {
            return;
        }
        Bold_CheckoutTaxExempt_Model_TaxExempt_File_Delete::delete($orderId);
    }
}
