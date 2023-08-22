<?php

/**
 * Bold order tax exempt delete data service.
 */
class Bold_CheckoutTaxExempt_Model_TaxExempt_File_Delete
{
    /**
     * Delete tax-exempt file.
     *
     * @param int $orderId
     * @return void
     * @throws Exception
     */
    public static function delete($orderId)
    {
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $extOrderData->load($orderId, Bold_Checkout_Model_Resource_Order::ORDER_ID);
        if (!$extOrderData->getisTaxExempt()) {
            return;
        }
        /** @var Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload_Config $config */
        $config = Mage::getModel(Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload_Config::RESOURCE);
        $uploadPath = $config->getUploadPath();
        @unlink(
            $uploadPath . $extOrderData->getTaxExemptFile()
        );
        $extOrderData->setTaxExemptFile(null);
        $extOrderData->setTaxExemptComment(null);
        $extOrderData->save();
        Mage::dispatchEvent('bold_checkout_tax_exempt_delete_after', ['order_id' => $orderId]);
    }
}
