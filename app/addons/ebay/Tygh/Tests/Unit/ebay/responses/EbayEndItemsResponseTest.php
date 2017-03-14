<?php
namespace Tygh\Tests\Unit\Addons\ebay\responses;

use \Ebay\responses\EndItemsResponse;

class EbayEndItemsResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $xml
     * @param int $count Count of items
     * @param array $times
     * @dataProvider responseDataProvider
     */
    public function testExternalIds($xml, $count, $times)
    {
        $response = new EndItemsResponse(simplexml_load_string($xml));

        $this->assertCount($count, $response->getItems());

        foreach ($times as $key => $time) {
            $this->assertEquals($time, $response->getItem($key)->getEndTime());
        }
    }

    public function responseDataProvider()
    {
        return array(
            array(
                "<EndItemsResponse>
                    <EndItemResponseContainer>
                        <CorrelationID>10</CorrelationID>
                        <EndTime>2008-10-01T21:57:01.000Z</EndTime>
                    </EndItemResponseContainer>
                    <EndItemResponseContainer>
                        <CorrelationID>20</CorrelationID>
                        <EndTime>2009-10-01T21:57:01.000Z</EndTime>
                    </EndItemResponseContainer>
                    <EndItemResponseContainer>
                        <CorrelationID>30</CorrelationID>
                        <EndTime>2010-10-01T21:57:01.000Z</EndTime>
                    </EndItemResponseContainer>
                </EndItemsResponse>",
                3,
                array(
                    10 => '2008-10-01T21:57:01.000Z',
                    20 => '2009-10-01T21:57:01.000Z',
                    30 => '2010-10-01T21:57:01.000Z'
                )
            ),
            array(
                "<EndItemsResponse>
                    <EndItemResponseContainer>
                        <CorrelationID>10</CorrelationID>
                        <EndTime>2009-10-01T21:57:01.000Z</EndTime>
                    </EndItemResponseContainer>
                    <EndItemResponseContainer>
                        <CorrelationID>20</CorrelationID>
                        <EndTime>2010-10-01T21:57:01.000Z</EndTime>
                    </EndItemResponseContainer>
                    <EndItemResponseContainer>
                        <CorrelationID>30</CorrelationID>
                    </EndItemResponseContainer>
                </EndItemsResponse>",
                3,
                array(10 => '2009-10-01T21:57:01.000Z', 20 => '2010-10-01T21:57:01.000Z', 30 => null)
            ),
        );
    }
}