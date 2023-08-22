<?php

/**
 * Populate bold_checkout_region_iso_code table with german data.
 */

$data = [
    // Niedersachsen.
    ['DE', 'NDS', 'NI'],
    // Baden-Württemberg.
    ['DE', 'BAW', 'BW'],
    // Bayern.
    ['DE', 'BAY', 'BY'],
    // Berlin.
    ['DE', 'BER', 'BE'],
    // Brandenburg.
    ['DE', 'BRG', 'BB'],
    // Bremen.
    ['DE', 'BRE', 'HB'],
    // Hamburg.
    ['DE', 'HAM', 'HH'],
    // Hessen.
    ['DE', 'HES', 'HE'],
    // Mecklenburg-Vorpommern.
    ['DE', 'MEC', 'MV'],
    // Nordrhein-Westfalen.
    ['DE', 'NRW', 'NW'],
    // Rheinland-Pfalz.
    ['DE', 'RHE', 'RP'],
    // Saarland.
    ['DE', 'SAR', 'SL'],
    // Sachsen.
    ['DE', 'SAS', 'SN'],
    // Sachsen-Anhalt.
    ['DE', 'SAC', 'ST'],
    // Schleswig-Holstein.
    ['DE', 'SCN', 'SH'],
    // Thüringen.
    ['DE', 'THE', 'TH'],
];

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable(Bold_Checkout_Model_RegionCodeMapper::RESOURCE);
$values =
    implode(
        ', ',
        array_map(
            function (array $row) {
                list($countryCode, $regionCode, $isoCode) = $row;
                return "('{$countryCode}', '{$regionCode}', '{$isoCode}')";
            },
            $data
        )
    );

if (!empty($values)) {
    $query = "INSERT INTO {$tableName} ("
        . Bold_Checkout_Model_RegionCodeMapper::COLUMN_COUNTRY_CODE . ', '
        . Bold_Checkout_Model_RegionCodeMapper::COLUMN_REGION_CODE . ', '
        . Bold_Checkout_Model_RegionCodeMapper::COLUMN_ISO_CODE . ") 
        VALUES {$values} 
        ON DUPLICATE KEY UPDATE "
        . Bold_Checkout_Model_RegionCodeMapper::COLUMN_COUNTRY_CODE . ' = VALUES('
        . Bold_Checkout_Model_RegionCodeMapper::COLUMN_COUNTRY_CODE . '), '
        . Bold_Checkout_Model_RegionCodeMapper::COLUMN_REGION_CODE . ' = VALUES('
        . Bold_Checkout_Model_RegionCodeMapper::COLUMN_REGION_CODE . '), '
        . Bold_Checkout_Model_RegionCodeMapper::COLUMN_ISO_CODE . ' = VALUES('
        . Bold_Checkout_Model_RegionCodeMapper::COLUMN_ISO_CODE . ')';

    $installer->run($query);
}

$installer->endSetup();
