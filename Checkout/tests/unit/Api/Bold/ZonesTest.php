<?php

include __DIR__ . '/MockRegionModel.php';

/**
* Unit tests for Bold_Checkout_Api_Bold_Zones class.
*/
class Bold_Checkout_Api_Bold_ZonesTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->regions = [
            [
                'country_code' => 'CA',
                'province_code' => 'MB',
            ], [
                'country_code' => 'CA',
                'province_code' => 'AB',
            ], [
                'country_code' => 'CA',
                'province_code' => 'BC',
            ], [
                'country_code' => 'CA',
                'province_code' => 'ON',
            ],
        ];

        $this->boldCheckoutServiceMock = Mockery::mock('alias:Bold_Checkout_Service');
        $this->MageMock = Mockery::mock('alias:Mage');
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testCreateZone()
    {
        $options = [
            'name' => 'test-name',
            'type' => 'test',
            'regions' => $this->regions,
        ];

        $this->boldCheckoutServiceMock->shouldReceive('call')
            ->once()
            ->with('POST', '/checkout/shop/{shop_identifier}/zones', 1234, json_encode($options))
            ->andReturn('{}');

        $createZoneMethod = new ReflectionMethod("Bold_Checkout_Api_Bold_Zones", "createZone");
        $createZoneMethod->setAccessible(true);

        $boldZones = new Bold_Checkout_Api_Bold_Zones();
        $actual = $createZoneMethod->invoke($boldZones, 1234, 'test', $this->regions, 'test-name');

        self::assertEquals(json_decode('{}'), $actual);
    }

    public function testUpdateZone()
    {
        $options = [
            'name' => 'test-name',
            'enabled' => true,
            'regions' => $this->regions,
        ];

        $zone = [
            'id' => 1,
            'type' => 'test',
        ];

        $this->boldCheckoutServiceMock->shouldReceive('call')
            ->once()
            ->with('PATCH', '/checkout/shop/{shop_identifier}/zones/1', 1234, json_encode($options));

        $updateZoneMethod = new ReflectionMethod("Bold_Checkout_Api_Bold_Zones", "updateZone");
        $updateZoneMethod->setAccessible(true);

        $boldZones = new Bold_Checkout_Api_Bold_Zones();
        $updateZoneMethod->invoke($boldZones, 1234, $zone, $this->regions, 'test-name');
    }

    public function testConfigureWarehouse()
    {
        $shippingOrigin = [
            'country_id' => 'CA',
            'region_id' => '68',
            'street_line1' => '123 Test Street',
            'street_line2' => '',
            'city' => 'Winnipeg',
            'country_code' => 'CA',
            'postcode' => 'R3R 3R3'
        ];

        $address = [
            'address' => '123 Test Street',
            'address2' => '',
            'city' => 'Winnipeg',
            'province_code' => 'MB',
            'province' => 'Manitoba',
            'country_code' => 'CA',
            'country' => 'Canada',
            'postal_code' => 'R3R 3R3',
        ];

        $countryModelMock = $this->getMockBuilder('Mage_Directory_Model_Country')->setMethods(
            ['loadByCode', 'getName']
        )->getMock();

        $countryModelMock
            ->method('loadByCode')
            ->willReturn($countryModelMock);

        $countryModelMock
            ->method('getName')
            ->willReturn('Canada');

        $regionModelMock = $this->getMockBuilder('Mage_Directory_Model_Region')
            ->setMethods(['load', 'getCode', 'getName'])
            ->getMock();

        $regionModelMock
            ->method('load')
            ->willReturn($regionModelMock);

        $regionModelMock
            ->method('getCode')
            ->willReturn('MB');

        $regionModelMock
            ->method('getName')
            ->willReturn('Manitoba');

        $this->MageMock->shouldReceive('getModel')
            ->with('directory/country')
            ->andReturn($countryModelMock);

        $this->MageMock->shouldReceive('getModel')
            ->with('directory/region')
            ->andReturn($regionModelMock);

        $regionCodeMapperMock = $this->getMockBuilder('Bold_Checkout_Model_RegionCodeMapper')
            ->setMethods(['getIsoCode'])
            ->getMock();

        $regionCodeMapperMock
            ->method('getIsoCode')
            ->willReturn('MB');

        $this->MageMock->shouldReceive('getSingleton')
            ->once()
            ->with(Bold_Checkout_Model_RegionCodeMapper::RESOURCE)
            ->andReturn($regionCodeMapperMock);

        $this->boldCheckoutServiceMock->shouldReceive('call')
            ->once()
            ->with('POST', '/checkout/shop/{shop_identifier}/zones/1/warehouses', 1234, json_encode($address));

        $configureWarehouseMethod = new ReflectionMethod("Bold_Checkout_Api_Bold_Zones", "configureWarehouse");
        $configureWarehouseMethod->setAccessible(true);

        $boldZones = new Bold_Checkout_Api_Bold_Zones();
        $configureWarehouseMethod->invoke($boldZones, 1234, $shippingOrigin, 1);
    }

    public function testConfigureTaxZone()
    {
        $body = [
            'tax_provider' => 'override',
        ];

        $this->boldCheckoutServiceMock->shouldReceive('call')
            ->once()
            ->with('POST', '/checkout/shop/{shop_identifier}/zones/1/tax_zone_settings', 1234, json_encode($body));

        $configureTaxZoneMethod = new ReflectionMethod("Bold_Checkout_Api_Bold_Zones", "configureTaxZone");
        $configureTaxZoneMethod->setAccessible(true);

        $boldZones = new Bold_Checkout_Api_Bold_Zones();
        $configureTaxZoneMethod->invoke($boldZones, 1234, 1);

        $this->MageMock->shouldNotHaveReceived('throwException');
    }

    public function testGetRegions()
    {
        $optionsArray = [
            [
                "value" => "CA",
                "label" => "Canada",
            ],
        ];

        $countryModelMock = $this->getMockBuilder('Mage_Directory_Model_Country')->setMethods(
            ['getResourceCollection', 'loadByStore', 'toOptionArray']
        )->getMock();

        $countryModelMock
            ->method('getResourceCollection')
            ->willReturn($countryModelMock);

        $countryModelMock
            ->method('loadByStore')
            ->willReturn($countryModelMock);

        $countryModelMock
            ->method('toOptionArray')
            ->willReturn($optionsArray);

        $regionMock = $this->getMockBuilder('Mock_Region_Model')->setMethods(
            ['getCode']
        )->getMock();

        $regionMock
            ->method('getCode')
            ->willReturn("MB");

        $regionModelMock = new MockRegionModel([$regionMock]);

        $this->MageMock->shouldReceive('getModel')
            ->once()
            ->with('directory/country')
            ->andReturn($countryModelMock);

        $this->MageMock->shouldReceive('getModel')
            ->once()
            ->with('directory/region')
            ->andReturn($regionModelMock);

        $getRegionsMethod = new ReflectionMethod("Bold_Checkout_Api_Bold_Zones", "getRegions");
        $getRegionsMethod->setAccessible(true);

        $boldZones = new Bold_Checkout_Api_Bold_Zones();
        $actual = $getRegionsMethod->invoke($boldZones);

        self::assertEquals([['country_code' => "CA", 'province_code' => "MB"]], $actual);
    }
}
