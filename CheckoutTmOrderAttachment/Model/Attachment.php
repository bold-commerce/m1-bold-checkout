<?php

/**
 * Rewrite TM_OrderAttachment_Model_Attachment to be able to catch events.
 */
class Bold_CheckoutTmOrderAttachment_Model_Attachment extends TM_OrderAttachment_Model_Attachment
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'order_attachment';
}
