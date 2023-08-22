<?php

/**
 * Directory region service.
 */
class Bold_Checkout_Service_DirectoryRegion
{
    private static $cache = [];

    /**
     * Retrieve region id by country and region codes.
     *
     * @param string $countryCode
     * @param string $provinceCode
     * @return string|null
     */
    public static function getRegionId($countryCode, $provinceCode)
    {
        $regionId = isset(self::$cache[$countryCode][$provinceCode]) ? self::$cache[$countryCode][$provinceCode] : null;
        if ($regionId) {
            return $regionId;
        }
        $region = Mage::getModel('directory/region')->loadByCode($provinceCode, $countryCode);;

        return $region->getId() ?: null;
    }
}
