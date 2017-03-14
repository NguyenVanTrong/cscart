<?php
namespace Tygh\Tests\Unit\Addons\ebay\responses;

use Ebay\responses\UploadSiteHostedPicturesResponse;

class EbayUploadSiteHostedPicturesResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $xml
     * @param $url
     * @dataProvider responseDataProvider
     */
    public function testResponseSuccess($xml, $url)
    {
        $response = new UploadSiteHostedPicturesResponse(simplexml_load_string($xml));

        $this->assertEquals($url, $response->getUrl());
    }

    public function responseDataProvider()
    {
        return array(
            array(
                "<UploadSiteHostedPicturesResponse>
                    <Timestamp>2015-04-19T23:18:20.560Z</Timestamp>
                    <Ack>Success</Ack>
                    <Version>919</Version>
                    <Build>E919_CORE_MSA_17469444_R1</Build>
                    <PictureSystemVersion>2</PictureSystemVersion>
                    <SiteHostedPictureDetails>
                        <PictureName>Developer Page Banner</PictureName>
                        <PictureSet>Standard</PictureSet>
                        <PictureFormat>JPG</PictureFormat>
                        <FullURL>http://i.ebayimg.com/00/s/NDAwWDE2MDA=/z/egUAAOSwBahVNDe8/_1.JPG?set_id=8800005007</FullURL>
                        <BaseURL>http://i.ebayimg.com/00/s/NDAwWDE2MDA=/z/egUAAOSwBahVNDe8/_</BaseURL>
                    </SiteHostedPictureDetails>
                </UploadSiteHostedPicturesResponse>",
                'http://i.ebayimg.com/00/s/NDAwWDE2MDA=/z/egUAAOSwBahVNDe8/_1.JPG?set_id=8800005007'
            ),
            array(
                "<UploadSiteHostedPicturesResponse>
                    <Timestamp>2015-04-19T23:18:20.560Z</Timestamp>
                    <Ack>Success</Ack>
                    <Version>919</Version>
                    <Build>E919_CORE_MSA_17469444_R1</Build>
                    <PictureSystemVersion>2</PictureSystemVersion>
                    <SiteHostedPictureDetails>
                        <PictureName>Developer Page Banner</PictureName>
                        <PictureSet>Standard</PictureSet>
                        <PictureFormat>JPG</PictureFormat>
                        <FullURL>http://i.ebayimg.com/00/s/NDAwWDE2MDA=/z/egUAAOSwBahVNDe8/_1.JPG?set_id=8800005008</FullURL>
                        <BaseURL>http://i.ebayimg.com/00/s/NDAwWDE2MDA=/z/egUAAOSwBahVNDe8/_</BaseURL>
                    </SiteHostedPictureDetails>
                </UploadSiteHostedPicturesResponse>",
                'http://i.ebayimg.com/00/s/NDAwWDE2MDA=/z/egUAAOSwBahVNDe8/_1.JPG?set_id=8800005008'
            ),
            array(
                "<UploadSiteHostedPicturesResponse>
                </UploadSiteHostedPicturesResponse>",
                null
            )
        );
    }
}