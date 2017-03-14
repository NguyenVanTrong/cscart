<?php
namespace Tygh\Tests\Unit\UpgradeCenter;

use Tygh\UpgradeCenter\Connectors\MarketplaceConnector;

class MarketplaceConnectorTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConnectionData()
    {
        $product_environment_mock = $this->getMockBuilder('\Tygh\SoftwareProductEnvironment')
            ->disableOriginalConstructor()
            ->getMock();

        $product_environment_mock->method('getProductVersion')->willReturn('5.0.1');
        $product_environment_mock->method('getProductName')->willReturn('CS-Cart');
        $product_environment_mock->method('getProductBuild')->willReturn('');
        $product_environment_mock->method('getProductEdition')->willReturn('ULTIMATE');

        $connector = new MarketplaceConnector(
            'http://marketplace.cs-cart.com',
            $product_environment_mock,
            'en',
            'foo_bar_addon', 'Foo Bar', '1.0.1', 100500, 'AAAA-BBBB-CCCC-DDDD-EEEE'
        );

        $this->assertEquals(array(
            'method' => 'get',
            'url' => 'http://marketplace.cs-cart.com/?dispatch=product_packages.get_upgrades',
            'data' => array(
                'product_id' => 100500,
                'ver' => '1.0.1',
                'product_version' => '5.0.1',
                'edition' => 'ULTIMATE',
                'lang' => 'en',
                'license_number' => 'AAAA-BBBB-CCCC-DDDD-EEEE',
                'product_build' => '',
            )
        ), $connector->getConnectionData());
    }
}