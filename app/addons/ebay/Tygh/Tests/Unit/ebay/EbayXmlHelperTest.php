<?php
namespace Tygh\Tests\Unit\Addons\ebay;

use \Ebay\XmlHelper;

class EbayXmlHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $response
     * @param $key
     * @param $default
     * @param $expected
     * @dataProvider responseStringDataProvider
     */
    public function testGetAsString($response, $key, $default, $expected)
    {
        $response = simplexml_load_string($response);
        $result = XmlHelper::getAsString($response, $key, $default);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param $response
     * @param $key
     * @param $expected
     * @dataProvider responseArrayStringDataProvider
     */
    public function testGetArrayAsString($response, $key, $expected)
    {
        $response = simplexml_load_string($response);
        $result = XmlHelper::getArrayAsStrings($response, $key);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param $response
     * @param $key
     * @param $default
     * @param $expected
     * @dataProvider responseIntDataProvider
     */
    public function testGetAsInt($response, $key, $default, $expected)
    {
        $response = simplexml_load_string($response);
        $result = XmlHelper::getAsInt($response, $key, $default);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param $response
     * @param $key
     * @param $default
     * @param $expected
     * @dataProvider responseBoolDataProvider
     */
    public function testGetAsBoolean($response, $key, $default, $expected)
    {
        $response = simplexml_load_string($response);
        $result = XmlHelper::getAsBoolean($response, $key, $default);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param $response
     * @param $key
     * @param $default
     * @param $expected
     * @dataProvider responseDoubleDataProvider
     */
    public function testGetAsDouble($response, $key, $default, $expected)
    {
        $response = simplexml_load_string($response);
        $result = XmlHelper::getAsDouble($response, $key, $default);

        $this->assertEquals($expected, $result);
    }

    public function responseStringDataProvider()
    {
        $xml = <<<XML
<root>
    <test>string1</test>
    <test2>string2</test2>
</root>
XML;
        return array(
            array($xml, 'test', null, 'string1'),
            array($xml, 'test2', null, 'string2'),
            array($xml, 'test3', null, null),
            array($xml, 'test4', 'test4', 'test4')
        );
    }

    public function responseArrayStringDataProvider()
    {
        $xml1 = <<<XML
        <root>
            <PaymentMethod>PayPal</PaymentMethod>
            <PaymentMethod>MOCC</PaymentMethod>
            <PaymentMethod>PersonalCheck</PaymentMethod>
        </root>
XML;
        $xml2 = <<<XML
        <root>
            <PaymentMethod>AmEx</PaymentMethod>
            <PaymentMethod>MoneyXferAccepted</PaymentMethod>
            <PaymentMethod>COD</PaymentMethod>
        </root>
XML;
        return array(
            array($xml1, 'PaymentMethod', array('PayPal', 'MOCC', 'PersonalCheck')),
            array($xml2, 'PaymentMethod', array('AmEx', 'MoneyXferAccepted', 'COD'))
        );
    }

    public function responseIntDataProvider()
    {
        $xml = <<<XML
<root>
    <test>10</test>
    <test2>20</test2>
</root>
XML;
        return array(
            array($xml, 'test', null, 10),
            array($xml, 'test2', null, 20),
            array($xml, 'test3', null, null),
            array($xml, 'test4', 4, 4)
        );
    }

    public function responseBoolDataProvider()
    {
        $xml = <<<XML
<root>
    <test>true</test>
    <test2/>
    <test3>1</test3>
    <test4>Required</test4>
    <test5>false</test5>
</root>
XML;

        return array(
            array($xml, 'test', null, true),
            array($xml, 'test2', null, false),
            array($xml, 'test3', null, true),
            array($xml, 'test4', null, true),
            array($xml, 'test5', null, false),
            array($xml, 'test6', true, true)
        );
    }

    public function responseDoubleDataProvider()
    {
        $xml = <<<XML
<root>
    <test>10</test>
    <test2>20.6</test2>
    <test3>test</test3>
</root>
XML;

        return array(
            array($xml, 'test', null, 10.0),
            array($xml, 'test2', null, 20.6),
            array($xml, 'test3', null, 0),
            array($xml, 'test4', null, null),
            array($xml, 'test5', 4, 4)
        );
    }
}