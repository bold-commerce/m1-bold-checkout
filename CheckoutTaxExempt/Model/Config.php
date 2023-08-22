<?php

/**
 * Bold Checkout Tax-exempt config.
 */
class Bold_CheckoutTaxExempt_Model_Config
{
    const RESOURCE = 'bold_checkouttaxexempt/config';
    const PATH_EXEMPT_EMAIL_TEMPLATE = 'checkout/bold_tax_exempt/exempt_email_template';
    const PATH_EXEMPT_EMAIL_SENDER = 'checkout/bold_tax_exempt/exempt_email_sender';

    /**
     * Get template used to send to Customer an Exemption Certificate upload e-mail.
     *
     * @return string
     */
    public function getExemptEmailTemplate()
    {
        return Mage::getStoreConfig(self::PATH_EXEMPT_EMAIL_TEMPLATE);
    }

    /**
     * Get sender identity used to send to Customer an Exemption Certificate upload e-mail.
     *
     * @return string
     */
    public function getExemptEmailSender()
    {
        return Mage::getStoreConfig(self::PATH_EXEMPT_EMAIL_SENDER);
    }
}
