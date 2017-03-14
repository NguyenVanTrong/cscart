<?php
namespace Tygh\Tests\Unit\Addons\ebay\responses;

use \Ebay\responses\AddItemsResponse;


class EbayAddItemsResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $xml
     * @param int $count Count of items
     * @param array $ids
     * @dataProvider responseDataProvider
     */
    public function testExternalIds($xml, $count, $ids)
    {
        $response = new AddItemsResponse(simplexml_load_string($xml));

        $this->assertCount($count, $response->getItems());

        foreach ($ids as $key => $id) {
            $this->assertEquals($id, $response->getItem($key)->getExternalId());
        }
    }

    public function responseDataProvider()
    {
        return array(
            array(
                "<AddItemsResponse>
                    <AddItemResponseContainer>
                        <CorrelationID>10</CorrelationID>
                        <ItemID>190001224016</ItemID>
                    </AddItemResponseContainer>
                    <AddItemResponseContainer>
                        <CorrelationID>20</CorrelationID>
                        <ItemID>190001224017</ItemID>
                    </AddItemResponseContainer>
                    <AddItemResponseContainer>
                        <CorrelationID>30</CorrelationID>
                        <ItemID>190001224019</ItemID>
                    </AddItemResponseContainer>
                </AddItemsResponse>",
                3,
                array(10 => '190001224016', 20 => '190001224017', 30 => '190001224019')
            ),
            array(
                "<AddItemsResponse>
                    <AddItemResponseContainer>
                        <CorrelationID>10</CorrelationID>
                        <ItemID>190001224020</ItemID>
                    </AddItemResponseContainer>
                    <AddItemResponseContainer>
                        <CorrelationID>20</CorrelationID>
                        <ItemID>190001224021</ItemID>
                    </AddItemResponseContainer>
                    <AddItemResponseContainer>
                        <CorrelationID>30</CorrelationID>
                    </AddItemResponseContainer>
                </AddItemsResponse>",
                3,
                array(10 => '190001224020', 20 => '190001224021', 30 => null)
            ),
        );
    }
}