<?php

/**
 * User agent header builder.
 */
class Bold_Checkout_Service_UserAgent
{
    const HEADER_PREFIX = 'Bold-Platform-Connector-M1:';

    /**
     * Build user-agent header value.
     *
     * @return string
     * @throws Mage_Core_Exception
     */
    public static function getUserAgent()
    {
        $moduleConfig = Mage::app()->getConfig()->getModuleConfig('Bold_Checkout')->asArray();
        if (!isset($moduleConfig['version'])) {
            Mage::throwException(Mage::helper('core')->__('Bold_Checkout module is not installed'));
        }
        return self::HEADER_PREFIX . $moduleConfig['version'];
    }
}
