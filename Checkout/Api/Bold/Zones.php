<?php

/**
 * Create default warehouse, shipping, and tax zones in bold checkout.
 */
class Bold_Checkout_Api_Bold_Zones
{
    const GET = 'GET';
    const POST = 'POST';
    const PATCH = 'PATCH';
    const MAP = 'MAP';
    const ALL = 'ALL';
    const ZONE_URL = '/checkout/shop/{shop_identifier}/zones';
    const ZONE_NAME = 'Bold generated zone';
    const ZONE_TYPES = ['warehouse', 'shipping', 'tax'];

    /**
     * @param int $websiteId
     * @return void
     * @throws Mage_Core_Exception
     */
    public static function configure($websiteId)
    {
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$boldConfig->isCheckoutEnabled($websiteId)) {
            return;
        }
        $sharedSecret = $boldConfig->getSharedSecret($websiteId);
        if (empty($sharedSecret)) {
            Mage::throwException(Mage::helper('core')->__('Shared key should be configured.'));
        }

        try {
            $existingZones = json_decode(
                Bold_Checkout_Service::call(self::GET, self::ZONE_URL, $websiteId),
                true
            );
        } catch (\Exception $e) {
            Mage::throwException(Mage::helper('core')->__('Could not fetch checkout zones.'));
        }

        if (!isset($existingZones['data']['zones'])) {
            Mage::throwException(Mage::helper('core')->__('Could not retrieve checkout zones data.'));
        }

        foreach (self::ZONE_TYPES as $type) {
            self::registerZones($type, $websiteId, $existingZones['data']['zones']);
        }
    }

    /**
     * @param string $type
     * @param int $websiteId
     * @param array $existingZones
     * @return void
     * @throws Mage_Core_Exception
     */
    private static function registerZones($type, $websiteId, array $existingZones)
    {
        $regions = self::getRegions();
        sort($regions);

        $matchingZones = array_filter(
            $existingZones, function ($zone) use ($type) {
                return $zone['type'] === $type && $zone['name'] === self::ZONE_NAME;
            }
        );

        $zonesToUpdate = array_filter(
            $matchingZones, function ($zone) use ($regions) {
                sort($zone['regions']);
                return $zone['regions'] !== $regions;
            }
        );

        foreach ($zonesToUpdate as $zone) {
            self::updateZone($websiteId, $zone, $regions);
        }

        if (empty($matchingZones)) {
            $zone = self::createZone($websiteId, $type, $regions);

            if (!isset($zone->data->id)) {
                Mage::throwException(Mage::helper('core')->__('Cannot create ' . $type . ' zone.'));
            }

            if ($type === 'warehouse') {
                $shippingOrigin = Mage::getStoreConfig('shipping/origin');
                self::configureWarehouse($websiteId, $shippingOrigin, $zone->data->id);
            } elseif ($type === 'tax') {
                self::configureTaxZone($websiteId, $zone->data->id);
            }
        }
    }

    /**
     * @param int $websiteId
     * @param string $type
     * @param array $regions
     * @param string $name
     * @return mixed
     * @throws Mage_Core_Exception
     */
    private static function createZone($websiteId, $type, array $regions, $name = self::ZONE_NAME)
    {
        $options = [
            'name' => $name,
            'type' => $type,
            'regions' => $regions,
        ];

        return json_decode(Bold_Checkout_Service::call(self::POST, self::ZONE_URL, $websiteId, json_encode($options)));
    }

    /**
     * @param int $websiteId
     * @param array $zone
     * @param array $regions
     * @param string $name
     * @return void
     * @throws Mage_Core_Exception
     */
    private static function updateZone($websiteId, array $zone, array $regions, $name = self::ZONE_NAME)
    {
        $options = [
            'name' => $name,
            'enabled' => true,
            'regions' => $regions,
        ];

        try {
            Bold_Checkout_Service::call(
                self::PATCH,
                self::ZONE_URL . '/' . $zone['id'],
                $websiteId,
                json_encode($options)
            );
        } catch (\Exception $e) {
            Mage::throwException(Mage::helper('core')->__('Cannot update ' . $zone['type'] . ' zone.'));
        }
    }

    /**
     * @param int $websiteId
     * @param array $shippingOrigin
     * @param int $zoneId
     * @return void
     * @throws Mage_Core_Exception
     */
    private static function configureWarehouse($websiteId, array $shippingOrigin, $zoneId)
    {
        self::validateShippingOrigin($shippingOrigin);
        $countryName = Mage::getModel('directory/country')
            ->loadByCode($shippingOrigin['country_id'])
            ->getName();
        $regionName = Mage::getModel('directory/region')
            ->load($shippingOrigin['region_id'])
            ->getName();
        $regionCode = Mage::getModel('directory/region')
            ->load($shippingOrigin['region_id'])
            ->getCode();
        /** @var Bold_Checkout_Model_RegionCodeMapper $regionCodeMapper */
        $regionCodeMapper = Mage::getSingleton(Bold_Checkout_Model_RegionCodeMapper::RESOURCE);
        $regionIsoCode = $regionCodeMapper->getIsoCode($shippingOrigin['country_id'], $regionCode);

        $address = [
            'address' => $shippingOrigin['street_line1'],
            'address2' => $shippingOrigin['street_line2'],
            'city' => $shippingOrigin['city'],
            'province_code' => $regionIsoCode,
            'province' => $regionName,
            'country_code' => $shippingOrigin['country_id'],
            'country' => $countryName,
            'postal_code' => $shippingOrigin['postcode'],
        ];

        $response = json_decode(
            Bold_Checkout_Service::call(
                self::POST,
                self::ZONE_URL . '/' . $zoneId . '/warehouses',
                $websiteId,
                json_encode($address)
            )
        );

        if (isset($response->errors)) {
            Mage::throwException(Mage::helper('core')->__('Cannot configure warehouse zone settings.'));
        }
    }

    /**
     * @param int $websiteId
     * @param int $zoneId
     * @return void
     * @throws Mage_Core_Exception
     */
    private static function configureTaxZone($websiteId, $zoneId)
    {
        $body = [
            'tax_provider' => 'override',
        ];

        $response = json_decode(
            Bold_Checkout_Service::call(
                self::POST,
                self::ZONE_URL . '/' . $zoneId . '/tax_zone_settings',
                $websiteId,
                json_encode($body)
            )
        );

        if (isset($response->errors)) {
            Mage::throwException(Mage::helper('core')->__('Cannot configure tax zone settings.'));
        }
    }

    /**
     * @return array
     */
    private static function getRegions()
    {
        $allCountries = Mage::getModel('directory/country')->getResourceCollection()
            ->loadByStore()
            ->toOptionArray(true);
        $supportedCountries = array_filter(
            $allCountries, function ($country) {
               // Antarctica is not supported in Bold Checkout.
                return $country['value'] !== 'AQ' && $country['value'] !== '';
            }
        );
        $regions = [];

        foreach ($supportedCountries as $country) {
            $regions = array_merge($regions, self::getRegionsForCountry($country['value']));
        }

        return $regions;
    }

    /**
     * @param string $countryCode
     * @return array
     */
    private static function getRegionsForCountry($countryCode)
    {
        $countriesWithRegions = Bold_Checkout_data_ProvinceCodes::getCountriesWithRegions();
        $usaTerritories = Bold_Checkout_data_ProvinceCodes::getUsaTerritories();
        $regions = [];

        if (!array_key_exists($countryCode, $countriesWithRegions)) {
            return [
                in_array($countryCode, $usaTerritories)
                    ? ['country_code' => 'US', 'province_code' => $countryCode]
                    : ['country_code' => $countryCode, 'province_code' => null],
            ];
        }

        $regionData = $countriesWithRegions[$countryCode];
        $availableRegions = Mage::getModel('directory/region')->getCollection()
            ->addCountryFilter($countryCode);

        if ($availableRegions->getSize()) {
            foreach ($availableRegions as $region) {
                $regions[] = [
                    'country_code' => $countryCode,
                    'province_code' => $regionData['province_alter'] === self::MAP
                        ? self::mapRegionCodeIfExists($regionData['province_codes'], $region->getCode())
                        : $region->getCode(),
                ];
            }
        } elseif ($regionData['province_alter'] === self::ALL) {
            foreach ($regionData['province_codes'] as $provinceCode) {
                $regions[] = [
                    'country_code' => $countryCode,
                    'province_code' => $provinceCode,
                ];
            }
        }

        return $regions;
    }

    /**
     * @param array $haystack
     * @param string $needle
     * @return string
     */
    private static function mapRegionCodeIfExists(array $haystack, $needle)
    {
        return array_key_exists($needle, $haystack) ? $haystack[$needle] : $needle;
    }

    /**
     * Check that shipping origin has all necessary data.
     *
     * @param array $shippingOrigin
     * @return void
     * @throws Mage_Core_Exception
     */
    private static function validateShippingOrigin(array $shippingOrigin)
    {
        $requiredFields = [
              'street_line1',
              'city',
              'country_id',
              'postcode',
        ];
        foreach ($requiredFields as $field) {
            if (!isset($shippingOrigin[$field])) {
                Mage::throwException(
                    Mage::helper('core')->__('Please fill the "%s" for store shipping origin.', $field)
                );
            }
        }
    }
}
