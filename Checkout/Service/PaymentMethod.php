<?php

/**
 * Capture and refund via bold service.
 */
class Bold_Checkout_Service_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    const CODE = 'bold';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var boolean
     */
    protected $_canAuthorize = true;

    /**
     * @var boolean
     */
    protected $_canCapture = true;

    /**
     * @var boolean
     */
    protected $_canCapturePartial = true;

    /**
     * @var boolean
     */
    protected $_canRefund = true;

    /**
     * @var boolean
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var boolean
     */
    protected $_canVoid = true;

    /**
     * @var boolean
     */
    protected $_canUseInternal = false;

    /**
     * @var boolean
     */
    protected $_canUseCheckout = true;

    /**
     * @var boolean
     */
    protected $_canUseForMultishipping = false;

    /**
     * @var boolean
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * @var string
     */
    protected $_formBlockType = 'bold_checkout/form_payments';

    /**
     * @inheritdoc
     */
    public function isAvailable($quote = null)
    {
        return Mage::getSingleton('checkout/session')->getBoldCheckoutData() !== null;
    }

    /**
     * Build title considering payment info to match Bold Checkout payment description.
     *
     * @return string
     */
    public function getTitle()
    {
        $title = null;
        $infoInstance = $this->getInfoInstance();
        if ($infoInstance && $infoInstance->getAdditionalInformation('payment_tender')) {
            $title = $infoInstance->getAdditionalInformation('payment_tender') . ': ';
            $title = str_replace('_', ' ', uc_words($title));
            $title .= strlen($infoInstance->getAdditionalInformation('transaction_card_last4')) === 4
                ? '••••• •••••• ' . $infoInstance->getAdditionalInformation('transaction_card_last4')
                : $infoInstance->getAdditionalInformation('transaction_card_last4');
        }
        return $title ?: parent::getTitle();
    }

    /**
     * Capture order payment.
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return Bold_Checkout_Service_PaymentMethod
     * @throws Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        $this->saveIsDelayedCapture($order);
        $websiteId = $order->getStore()->getWebsiteId();
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$boldConfig->isCheckoutEnabled($websiteId)) {
            Mage::throwException(
                'Cannot create Invoice.'
                . ' Please make sure Bold Checkout module output is enabled and Bold Checkout Integration is on.'
            );
        }
        if ((float)$order->getGrandTotal() === (float)$amount) {
            $payment->setTransactionId(Bold_Checkout_Api_Bold_Payment::captureFull($order))
                ->setShouldCloseParentTransaction(true);
            return $this;
        }
        $payment->setTransactionId(Bold_Checkout_Api_Bold_Payment::capturePartial($order, (float)$amount));
        if ((float)$payment->getBaseAmountAuthorized() === $payment->getBaseAmountPaid() + $amount) {
            $payment->setShouldCloseParentTransaction(true);
        }

        return $this;
    }

    /**
     * Cancel payment transaction.
     *
     * @param Varien_Object $payment
     * @return Bold_Checkout_Service_PaymentMethod
     * @throws Exception
     */
    public function cancel(Varien_Object $payment)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        $this->saveIsDelayedCapture($order);
        $websiteId = $order->getStore()->getWebsiteId();
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$boldConfig->isCheckoutEnabled($websiteId)) {
            Mage::throwException(
                'Cannot cancel the order.'
                . ' Please make sure Bold Checkout module output is enabled and Bold Checkout Integration is on.'
            );
        }
        Bold_Checkout_Api_Bold_Payment::cancel($order);
        return $this;
    }

    /**
     * Void payment transaction.
     *
     * @param Varien_Object $payment
     * @return Bold_Checkout_Service_PaymentMethod
     * @throws Exception
     */
    public function void(Varien_Object $payment)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        $this->saveIsDelayedCapture($order);
        $websiteId = $order->getStore()->getWebsiteId();
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$boldConfig->isCheckoutEnabled($websiteId)) {
            Mage::throwException(
                'Cannot void the order payment.'
                . ' Please make sure Bold Checkout module output is enabled and Bold Checkout Integration is on.'
            );
        }
        Bold_Checkout_Api_Bold_Payment::cancel($order, Bold_Checkout_Api_Bold_Payment::VOID);
        return $this;
    }

    /**
     * Refund payment via bold.
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return Bold_Checkout_Service_PaymentMethod
     * @throws Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        $this->saveIsDelayedCapture($order);
        $websiteId = $order->getStore()->getWebsiteId();
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$boldConfig->isCheckoutEnabled($websiteId)) {
            Mage::throwException(
                'Cannot create Credit Memo.'
                . ' Please make sure Bold Checkout module output is enabled and Bold Checkout Integration is on.'
            );
        }
        $orderGrandTotal = Mage::app()->getStore()->roundPrice($order->getGrandTotal());
        $amount = Mage::app()->getStore()->roundPrice($amount);
        if ($orderGrandTotal <= $amount) {
            $transactionId = Bold_Checkout_Api_Bold_Payment::refundFull($order);
            $payment->setTransactionId($transactionId)
                ->setIsTransactionClosed(1)
                ->setShouldCloseParentTransaction(true);
            return $this;
        }
        $transactionId = Bold_Checkout_Api_Bold_Payment::refundPartial($order, (float)$amount);
        $payment->setTransactionId($transactionId)->setIsTransactionClosed(1);
        if ((float)$payment->getBaseAmountPaid() === $payment->getBaseAmountRefunded() + $amount) {
            $payment->setShouldCloseParentTransaction(true);
        }

        return $this;
    }

    /**
     * Save order uses delayed payment capture.
     *
     * @param Mage_Sales_Model_Order $order
     * @return void
     * @throws Exception
     */
    private function saveIsDelayedCapture(Mage_Sales_Model_Order $order)
    {
        /** @var Bold_Checkout_Model_Order $extOrderData */
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $extOrderData->load($order->getEntityId(), Bold_Checkout_Model_Resource_Order::ORDER_ID);
        $extOrderData->setIsDelayedCapture(1);
        $extOrderData->save();
    }
}
