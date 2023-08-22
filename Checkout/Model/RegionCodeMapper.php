<?php

/**
 * Map ISO 3166-1 alpha-2 region code to Magento region code and vice versa.
 *
 * phpcs:disable MEQP1.Classes.ResourceModel.OutsideOfResourceModel
 */
class Bold_Checkout_Model_RegionCodeMapper
{
    const RESOURCE = 'bold_checkout/regioncodemapper';
    const COLUMN_COUNTRY_CODE = 'country_code';
    const COLUMN_REGION_CODE = 'region_code';
    const COLUMN_ISO_CODE = 'iso_code';

    /**
     * @var array
     */
    private $isoLoadCache = [];

    /**
     * @var array
     */
    private $regionCodeLoadCache = [];

    /**
     * Get ISO 3166-1 alpha-2 region code by Magento region code.
     *
     * @param string $magentoCountryCode
     * @param string $magentoRegionCode
     * @return string
     */
    public function getIsoCode($magentoCountryCode, $magentoRegionCode)
    {
        if (!isset($this->isoLoadCache[$magentoCountryCode][$magentoRegionCode])) {
            $this->isoLoadCache[$magentoCountryCode][$magentoRegionCode] =
                $this->loadIsoCode($magentoCountryCode, $magentoRegionCode) ?: (string)$magentoRegionCode;
        }

        return $this->isoLoadCache[$magentoCountryCode][$magentoRegionCode];
    }

    /**
     * Get Magento region code by ISO 3166-1 alpha-2 region code.
     *
     * @param string $magentoCountryCode
     * @param string $isoCode
     * @return string
     */
    public function getRegionCode($magentoCountryCode, $isoCode)
    {
        if (!isset($this->regionCodeLoadCache[$magentoCountryCode][$isoCode])) {
            $this->regionCodeLoadCache[$magentoCountryCode][$isoCode] =
                $this->loadRegionCode($magentoCountryCode, $isoCode) ?: (string)$isoCode;
        }

        return $this->regionCodeLoadCache[$magentoCountryCode][$isoCode];
    }

    /**
     * Load ISO 3166-1 alpha-2 region code by Magento region code.
     *
     * @param string $magentoCountryCode
     * @param string $magentoRegionCode
     * @return string
     */
    private function loadIsoCode($magentoCountryCode, $magentoRegionCode)
    {
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
        $table = $resource->getTableName(self::RESOURCE);
        $select = $connection->select();
        $select->from(
            $table,
            [
                self::COLUMN_ISO_CODE,
            ]
        )->where(
            self::COLUMN_COUNTRY_CODE . ' = ?', $magentoCountryCode
        )->where(
            self::COLUMN_REGION_CODE . ' = ?', $magentoRegionCode
        );

        return $connection->fetchOne($select, self::COLUMN_ISO_CODE);
    }

    /**
     * Load Magento region code by ISO 3166-1 alpha-2 region code .
     *
     * @param string $magentoCountryCode
     * @param string $isoCode
     * @return string
     */
    private function loadRegionCode($magentoCountryCode, $isoCode)
    {
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
        $table = $resource->getTableName(self::RESOURCE);
        $select = $connection->select();
        $select->from(
            $table,
            [
                self::COLUMN_REGION_CODE,
            ]
        )->where(
            self::COLUMN_COUNTRY_CODE . ' = ?', $magentoCountryCode
        )->where(
            self::COLUMN_ISO_CODE . ' = ?', $isoCode
        );

        return $connection->fetchOne($select, self::COLUMN_REGION_CODE);
    }
}
