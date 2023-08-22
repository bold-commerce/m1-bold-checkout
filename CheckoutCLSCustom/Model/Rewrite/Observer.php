<?php

/**
 * CLS Custom observer rewrite.
 */
class Bold_CheckoutCLSCustom_Model_Rewrite_Observer extends CLS_Custom_Model_Observer
{
    /**
     * Disable browser verification on bold api calls.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function browserUpdateMessage(Varien_Event_Observer $observer)
    {
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = Mage::app()->getRequest();
        if (strpos($request->getRequestUri(), '/bold/') !== false) {
            return;
        }
        parent::browserUpdateMessage($observer);
    }
}
