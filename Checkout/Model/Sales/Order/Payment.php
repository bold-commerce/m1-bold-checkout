<?php

/**
 * Order payment rewrite to fix earlier magento version already closed transaction issue.
 */
class Bold_Checkout_Model_Sales_Order_Payment extends Mage_Sales_Model_Order_Payment
{
    /**
     * @inheritDoc
     */
    protected function _addTransaction($type, $salesDocument = null, $failsafe = false)
    {
        if ($this->getSkipTransactionCreation()) {
            $this->unsTransactionId();
            return null;
        }
        $transactionId = $this->getTransactionId();
        if (null !== $transactionId) {
            // set transaction parameters
            $transaction = false;
            if ($this->getOrder()->getId()) {
                $transaction = $this->_lookupTransaction($transactionId);
            }
            if (!$transaction) {
                $transaction = Mage::getModel('sales/order_payment_transaction')->setTxnId($transactionId);
            }
            $transaction
                ->setOrderPaymentObject($this)
                ->setTxnType($type)
                ->isFailsafe($failsafe);

            if ($this->hasIsTransactionClosed()) {
                $transaction->setIsClosed((int)$this->getIsTransactionClosed());
            }

            //set transaction addition information
            if ($this->_transactionAdditionalInfo) {
                foreach ($this->_transactionAdditionalInfo as $key => $value) {
                    $transaction->setAdditionalInformation($key, $value);
                }
            }
            $this->setLastTransId($transactionId);
            $this->setCreatedTransaction($transaction);
            $this->getOrder()->addRelatedObject($transaction);
            if ($salesDocument instanceof Mage_Sales_Model_Abstract) {
                $salesDocument->setTransactionId($transactionId);
            }
            $parentTransactionId = $this->getParentTransactionId();
            if ($parentTransactionId) {
                $transaction->setParentTxnId($parentTransactionId);
                if ($this->getShouldCloseParentTransaction()) {
                    $parentTransaction = $this->_lookupTransaction($parentTransactionId);
                    if ($parentTransaction) {
                        if (!$parentTransaction->getIsClosed()) {
                            $parentTransaction->isFailsafe($failsafe)->close(false);
                        }
                        $this->getOrder()->addRelatedObject($parentTransaction);
                    }
                }
            }
            return $transaction;
        }

        return null;
    }
}
