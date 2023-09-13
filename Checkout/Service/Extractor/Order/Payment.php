<?php

/**
 * Order payment entity to array extract service.
 */
class Bold_Checkout_Service_Extractor_Order_Payment
{
    /**
     * Extract order payment entity data into array.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return array
     */
    public static function extract(
        Mage_Sales_Model_Order_Payment $payment
    ) {
        return [
            'account_status' => $payment->getAccountStatus(),
            'additional_information' => $payment->getAdditionalInformation(),
            'amount_authorized' => Mage::app()->getStore()->roundPrice($payment->getAmountAuthorized()),
            'amount_ordered' => Mage::app()->getStore()->roundPrice($payment->getAmountOrdered()),
            'amount_paid' => Mage::app()->getStore()->roundPrice($payment->getAmountPaid()),
            'base_amount_authorized' => Mage::app()->getStore()->roundPrice($payment->getBaseAmountAuthorized()),
            'base_amount_ordered' => Mage::app()->getStore()->roundPrice($payment->getBaseAmountOrdered()),
            'base_amount_paid' => Mage::app()->getStore()->roundPrice($payment->getBaseAmountPaid()),
            'base_shipping_amount' => Mage::app()->getStore()->roundPrice($payment->getBaseShippingAmount()),
            'cc_last4' => $payment->decrypt($payment->getCcLast4()),
            'cc_trans_id' => $payment->getCcTransId(),
            'cc_type' => $payment->getCcType(),
            'entity_id' => (int)$payment->getId(),
            'last_trans_id' => $payment->getLastTransId(),
            'method' => Bold_Checkout_Service_PaymentMethod::CODE,
            'parent_id' => (int)$payment->getParentId(),
            'shipping_amount' => Mage::app()->getStore()->roundPrice($payment->getShippingAmount()),
            'extension_attributes' => [
                'additional_information' => $payment->getAdditionalInformation(),
            ],
        ];
    }
}
