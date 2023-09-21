<?php

/**
 * Manage tax-exempt document controller.
 */
class Bold_CheckoutIntegration_Adminhtml_IntegrationController extends Mage_Adminhtml_Controller_Action
{
    const ID = 'integration_id';

    /**
     * Exchanges a token with platform connector..
     *
     * @return void
     * @throws Mage_Core_Exception
     */
    public function authorizeAction()
    {
        $integrationId = $this->getRequest()->getParam(self::ID);
        $integration = Bold_CheckoutIntegration_Model_IntegrationService::get($integrationId);
        $result = Bold_CheckoutIntegration_Model_TokenExchangeService::exchange(
            $integration->getId(),
            (bool)$integration->getStatus()
        );
        if ($result) {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('adminhtml')->__('Integration authorized successfully.')
            );
            $this->_redirectReferer();
            return;
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('adminhtml')->__('Integration authorization failed.')
        );
        $this->_redirectReferer();
    }

    /**
     * Deletes an integration.
     *
     * @return void
     */
    public function deleteAction()
    {
        $integrationId = $this->getRequest()->getParam(self::ID);
        try {
            Bold_CheckoutIntegration_Model_IntegrationService::delete($integrationId);
        } catch (\Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirectReferer();
            return;
        }
        Mage::getSingleton('adminhtml/session')->addSuccess(
            Mage::helper('adminhtml')->__('Integration deleted successfully.')
        );
        $this->_redirectReferer();
    }
}
