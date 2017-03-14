<?php

namespace Tygh\Tests\Unit\Core;


use Tygh\ExSimpleXmlElement;

class ExSimpleXmlElementTest extends \PHPUnit_Framework_TestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    /**
     * @param $data
     * @param $expected_xml
     * @dataProvider dpArrayXml
     */
    public function testAddFromArray($data, $expected_xml)
    {
        $xml_root = new ExSimpleXmlElement("<root></root>");
        $xml_root->addChildFromArray($data);

        $xml = $xml_root->asXML();

        $this->assertEquals($expected_xml, $xml);
    }

    /**
     * @param $expected_data
     * @param $xml
     * @dataProvider dpArrayXml
     */
    public function testToArray($expected_data, $xml)
    {
        /** @var ExSimpleXmlElement $xml */
        $xml = simplexml_load_string($xml, '\Tygh\ExSimpleXmlElement');

        $this->assertEquals($expected_data, $xml->toArray());
    }

    public function dpArrayXml()
    {
        return array(
            array(
                array(
                    'name' => '100g pants', 'code' => 'QWERTY109',
                    'options' => array(
                        array('option_id' => 10, 'value' => 100, 'name' => 'Color'),
                        array('option_id' => 20, 'value' => 200, 'name' => 'Size'),
                    )
                ),
                "<?xml version=\"1.0\"?>\n<root><name><![CDATA[100g pants]]></name><code><![CDATA[QWERTY109]]></code>"
                . '<options>'
                . '<item><option_id>10</option_id><value>100</value><name><![CDATA[Color]]></name></item>'
                . '<item><option_id>20</option_id><value>200</value><name><![CDATA[Size]]></name></item>'
                . '</options>'
                . "</root>\n"
            ),
            array(
                array(
                    'name' => array('en' => 'Page', 'ru' => 'Страница'),
                    'params' => array(
                        array(
                            'title' => 'Тип страницы',
                            'type' => 'selectbox',
                            'variants' => array(
                                array('name' => 'Обычная', 'value' => 'base'),
                                array('name' => 'Расширенная', 'value' => 'advanced'),
                                array('name' => 'Другая', 'value' => 'other'),
                            )
                        ),
                        array(
                            'title' => 'Размер баннера',
                            'type' => 'input',
                        )
                    )
                ),
                "<?xml version=\"1.0\"?>\n<root><name><en><![CDATA[Page]]></en><ru><![CDATA[Страница]]></ru></name>"
                . '<params>'
                . '<item><title><![CDATA[Тип страницы]]></title><type><![CDATA[selectbox]]></type><variants>'
                    . '<item><name><![CDATA[Обычная]]></name><value><![CDATA[base]]></value></item>'
                    . '<item><name><![CDATA[Расширенная]]></name><value><![CDATA[advanced]]></value></item>'
                    . '<item><name><![CDATA[Другая]]></name><value><![CDATA[other]]></value></item>'
                . '</variants></item>'
                . '<item><title><![CDATA[Размер баннера]]></title><type><![CDATA[input]]></type></item>'
                . '</params>'
                . "</root>\n"
            )
        );
    }
}