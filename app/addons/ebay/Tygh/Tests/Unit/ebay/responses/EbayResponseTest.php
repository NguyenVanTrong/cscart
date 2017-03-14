<?php
namespace Tygh\Tests\Unit\Addons\ebay\responses;

use \Ebay\responses\Response;

class EbayResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $xml
     * @param $success
     * @param $warningCount
     * @param $errorCount
     * @dataProvider responseDataProvider
     */
    public function testResponseSuccess($xml, $success, $errorCount, $warningCount)
    {
        $response = new Response(simplexml_load_string($xml));

        $this->assertEquals($success, $response->isSuccess());
        $this->assertCount($errorCount, $response->getErrors());
        $this->assertCount($warningCount, $response->getWarnings());
    }

    public function responseDataProvider()
    {
        return array(
            array(
                "<Response>
                    <Errors>
                        <ErrorCode>1</ErrorCode>
                        <LongMessage>LongMessage</LongMessage>
                        <SeverityCode>Error</SeverityCode>
                        <ShortMessage>ShortMessage</ShortMessage>
                    </Errors>
                    <Errors>
                        <ErrorCode>2</ErrorCode>
                        <LongMessage>LongMessage</LongMessage>
                        <SeverityCode>Error</SeverityCode>
                        <ShortMessage>ShortMessage</ShortMessage>
                    </Errors>
                    <Errors>
                        <ErrorCode>3</ErrorCode>
                        <LongMessage>LongMessage</LongMessage>
                        <SeverityCode>Warning</SeverityCode>
                        <ShortMessage>ShortMessage</ShortMessage>
                    </Errors>
                </Response>",
                false,
                2,
                1
            ),
            array(
                "<Response>
                    <Errors>
                        <ErrorCode>1</ErrorCode>
                        <LongMessage>LongMessage</LongMessage>
                        <SeverityCode>Error</SeverityCode>
                        <ShortMessage>ShortMessage</ShortMessage>
                    </Errors>
                    <Errors>
                        <ErrorCode>2</ErrorCode>
                        <LongMessage>LongMessage</LongMessage>
                        <SeverityCode>Warning</SeverityCode>
                        <ShortMessage>ShortMessage</ShortMessage>
                    </Errors>
                    <Errors>
                        <ErrorCode>3</ErrorCode>
                        <LongMessage>LongMessage</LongMessage>
                        <SeverityCode>Warning</SeverityCode>
                        <ShortMessage>ShortMessage</ShortMessage>
                    </Errors>
                </Response>",
                false,
                1,
                2
            ),
            array(
                "<Response>
                </Response>",
                true,
                0,
                0
            )
        );
    }

}