<?php

/**
 * Bold Checkout order tax-exempt attachments tab rewrite.
 */
class Bold_CheckoutTmOrderAttachment_Block_Adminhtml_Sales_Order_View_Tab_Attachments
    extends Bold_CheckoutTaxExempt_Block_Adminhtml_Sales_Order_View_Tab_Attachments
{
    /**
     * @inheritDoc
     */
    public function canShowTab()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isHidden()
    {
        return true;
    }
}
