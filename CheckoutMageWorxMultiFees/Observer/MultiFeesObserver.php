<?php

/**
 * Adapt Mageworx multi fees for bold checkout observer.
 */
class Bold_CheckoutMageWorxMultiFees_Observer_MultiFeesObserver
{
    /**
     * Copy previously saved fees data to session to collect totals with correct fees data.
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function addFeesDataBeforeCollectTotals(Varien_Event_Observer $event)
    {
        /** @var Mage_Sales_Model_Quote_Address $address */
        $address = $event->getQuoteAddress();
        /** @var Bold_CheckoutMageWorxMultiFees_Model_Quote_Fees_Data $quoteFees */
        $quoteFees = Mage::getModel(Bold_CheckoutMageWorxMultiFees_Model_Quote_Fees_Data::RESOURCE);
        $quoteFees->load(
            $address->getQuote()->getId(),
            Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::QUOTE_ID
        );
        $feeData = $quoteFees->getFeesData() ? json_decode($quoteFees->getFeesData(), true) : null;
        $session = Mage::getSingleton('checkout/session');
        if (!$session->getDetailsMultifees()) {
            $session->setDetailsMultifees($feeData);
        }
    }
}
