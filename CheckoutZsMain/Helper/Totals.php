<?php

/**
 * Adapt ZS_Main_Helper_Totals to non-controller workflow.
 */
class Bold_CheckoutZsMain_Helper_Totals extends ZS_Main_Helper_Totals
{
    /**
     * Adapt ZS_Main_Helper_Totals to non-controller workflow.
     *
     * @return bool
     */
    public function isShortShippingTotalApplicable()
    {
        return Mage::app()->getFrontController()->getAction()
            && parent::isShortShippingTotalApplicable();
    }
}
