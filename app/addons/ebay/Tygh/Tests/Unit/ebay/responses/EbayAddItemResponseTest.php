<?php
namespace Tygh\Tests\Unit\Addons\ebay\responses;

use \Ebay\responses\AddItemResponse;

class EbayAddItemResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $xml
     * @param $id
     * @dataProvider responseDataProvider
     */
    public function testExternalId($xml, $id)
    {
        $response = new AddItemResponse(simplexml_load_string($xml));

        $this->assertEquals($id, $response->getExternalId());
    }

    public function responseDataProvider()
    {
        return array(
            array(
                "<AddItemResponse>
                      <Timestamp>2010-07-07T23:26:12.811Z</Timestamp>
                      <Ack>Success</Ack>
                      <Version>675</Version>
                      <Build>E675_CORE_BUNDLED_11481347_R1</Build>
                      <ItemID>110048942431</ItemID>
                      <StartTime>2010-07-10T08:00:11.000Z</StartTime>
                      <EndTime>2010-07-20T08:00:11.000Z</EndTime>
                </AddItemResponse>",
                '110048942431'
            ),
            array(
                "<AddItemResponse>
                      <Timestamp>2010-07-07T23:26:12.811Z</Timestamp>
                      <Ack>Success</Ack>
                      <Version>675</Version>
                      <Build>E675_CORE_BUNDLED_11481347_R1</Build>
                      <ItemID>110048942432</ItemID>
                      <StartTime>2010-07-10T08:00:11.000Z</StartTime>
                      <EndTime>2010-07-20T08:00:11.000Z</EndTime>
                </AddItemResponse>",
                '110048942432'
            ),
            array(
                "<AddItemResponse>
                      <Timestamp>2010-07-07T23:26:12.811Z</Timestamp>
                      <Ack>Success</Ack>
                      <Version>675</Version>
                      <Build>E675_CORE_BUNDLED_11481347_R1</Build>
                      <StartTime>2010-07-10T08:00:11.000Z</StartTime>
                      <EndTime>2010-07-20T08:00:11.000Z</EndTime>
                </AddItemResponse>",
                null
            ),
        );
    }

}