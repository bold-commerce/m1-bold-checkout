<?php

/**
 * Wrapper for Mage class.
 */
class Bold_Checkout_Mage
{
    /**
     * Log message.
     *
     * @param string $message
     * @param int $websiteId
     * @return void
     */
    public function log($message, $websiteId)
    {
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = $this->getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if ($boldConfig->isLogEnabled($websiteId)) {
            Mage::log($message, Zend_Log::DEBUG, 'bold_checkout.log', true);
        }
    }

    /**
     * Retrieve config value for store by path.
     *
     * @param string $key
     * @param mixed $storeId
     * @return mixed
     */
    public function getStoreConfig($key, $storeId)
    {
        return Mage::getStoreConfig($key, $storeId);
    }

    /**
     * Retrieve config model.
     *
     * @return Mage_Core_Model_Config
     */
    public function getConfig()
    {
        return Mage::getConfig();
    }

    /**
     * Retrieve model object singleton
     *
     * @param string $modelClass
     * @param array $arguments
     * @return  Mage_Core_Model_Abstract
     */
    public function getSingleton($modelClass, array $arguments = [])
    {
        return Mage::getSingleton($modelClass, $arguments);
    }

    /**
     * Retrieve model object.
     *
     * @param string $modelClass
     * @param array $arguments
     * @return  Mage_Core_Model_Abstract|false
     */
    public function getModel($modelClass, array $arguments = [])
    {
        return Mage::getModel($modelClass, $arguments);
    }

    /**
     * Retrieve enabled developer mode.
     *
     * @return bool
     */
    public function getIsDeveloperMode()
    {
        return Mage::getIsDeveloperMode();
    }

    /**
     * Retrieve app object.
     *
     * @return Mage_Core_Model_App
     */
    public function getApp()
    {
        return Mage::app();
    }
}
