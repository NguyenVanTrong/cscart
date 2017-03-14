<?php
namespace Tygh\Tests\Unit\Addons\ebay\responses;

use \Ebay\responses\EndItemResponse;

class EbayEndItemResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $xml
     * @param string $time
     * @dataProvider responseDataProvider
     */
    public function testExternalId($xml, $time)
    {
        $response = new EndItemResponse(simplexml_load_string($xml));

        $this->assertEquals($time, $response->getEndTime());
    }

    public function responseDataProvider()
    {
        return array(
            array(
                "<EndItemRequest>
                    <Timestamp>2008-08-08T19:21:18.423Z</Timestamp>
                    <Ack>Success</Ack>
                    <Version>577</Version>
                    <Build>e577_core_Bundled_7010443_R1</Build>
                    <EndTime>2008-08-08T19:21:18.000Z</EndTime>
                </EndItemRequest>",
                '2008-08-08T19:21:18.000Z'
            ),
            array(
                "<EndItemRequest>
                    <Timestamp>2008-08-08T19:21:18.423Z</Timestamp>
                    <Ack>Success</Ack>
                    <Version>577</Version>
                    <Build>e577_core_Bundled_7010443_R1</Build>
                    <EndTime>2009-08-08T19:21:18.000Z</EndTime>
                </EndItemRequest>",
                '2009-08-08T19:21:18.000Z'
            ),
            array(
                "<EndItemRequest>
                    <Timestamp>2008-08-08T19:21:18.423Z</Timestamp>
                    <Ack>Success</Ack>
                    <Version>577</Version>
                    <Build>e577_core_Bundled_7010443_R1</Build>
                </EndItemRequest>",
                null
            ),
        );
    }

}