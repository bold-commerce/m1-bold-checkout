<?php

/**
 * Bold Checkout self-hosted index controller.
 */
class Bold_CheckoutSelfHosted_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * Index action.
     *
     * @return void
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
}
