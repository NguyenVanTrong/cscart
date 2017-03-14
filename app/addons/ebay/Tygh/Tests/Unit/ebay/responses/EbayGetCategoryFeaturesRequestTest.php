<?php
namespace Tygh\Tests\Unit\Addons\ebay\responses;

use \Ebay\responses\GetCategoryFeaturesResponse;

class EbayGetCategoryFeaturesRequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $xml
     * @param string $type
     * @param array $expected
     * @dataProvider responseDataProvider
     */
    public function testListingDurations($xml, $type, $expected)
    {
        $response = new GetCategoryFeaturesResponse(simplexml_load_string($xml));

        $result = $response->getListingDurations($type);
        $this->assertEquals($expected, $result);
    }

    public function responseDataProvider()
    {
        return array(
            array(
                '<GetCategoryFeaturesResponse xmlns="urn:ebay:apis:eBLBaseComponents">
                  <Category>
                    <CategoryID>177800</CategoryID>
                    <ListingDuration type="Chinese">1</ListingDuration>
                    <ListingDuration type="Dutch">1</ListingDuration>
                    <ListingDuration type="Live">1</ListingDuration>
                    <ListingDuration type="AdType">2</ListingDuration>
                    <ListingDuration type="StoresFixedPrice">31</ListingDuration>
                    <ListingDuration type="PersonalOffer">1</ListingDuration>
                    <ListingDuration type="FixedPriceItem">1</ListingDuration>
                    <ListingDuration type="LeadGeneration">41</ListingDuration>
                    <StoreOwnerExtendedListingDurations/>
                  </Category>
                  <SiteDefaults>
                    <ListingDuration type="Chinese">1</ListingDuration>
                    <ListingDuration type="Dutch">1</ListingDuration>
                    <ListingDuration type="Live">1</ListingDuration>
                    <ListingDuration type="AdType">2</ListingDuration>
                    <ListingDuration type="StoresFixedPrice">31</ListingDuration>
                    <ListingDuration type="PersonalOffer">1</ListingDuration>
                    <ListingDuration type="FixedPriceItem">1</ListingDuration>
                    <ListingDuration type="LeadGeneration">41</ListingDuration>
                    <StoreOwnerExtendedListingDurations>
                      <Duration>Days_30</Duration>
                      <Duration>GTC</Duration>
                    </StoreOwnerExtendedListingDurations>
                  </SiteDefaults>
                  <FeatureDefinitions>
                    <ListingDurations Version="3">
                      <ListingDuration durationSetID="1">
                        <Duration>Days_3</Duration>
                        <Duration>Days_5</Duration>
                        <Duration>Days_7</Duration>
                        <Duration>Days_10</Duration>
                      </ListingDuration>
                      <ListingDuration durationSetID="2">
                        <Duration>Days_30</Duration>
                        <Duration>Days_90</Duration>
                      </ListingDuration>
                      <ListingDuration durationSetID="41">
                        <Duration>Days_5</Duration>
                        <Duration>Days_10</Duration>
                        <Duration>Days_30</Duration>
                        <Duration>Days_60</Duration>
                        <Duration>GTC</Duration>
                      </ListingDuration>
                      <ListingDuration durationSetID="31">
                        <Duration>Days_3</Duration>
                        <Duration>Days_5</Duration>
                        <Duration>Days_7</Duration>
                        <Duration>Days_10</Duration>
                        <Duration>Days_30</Duration>
                        <Duration>GTC</Duration>
                      </ListingDuration>
                    </ListingDurations>
                    <StoreOwnerExtendedListingDurations />
                  </FeatureDefinitions>
                </GetCategoryFeaturesResponse>',
                'FixedPriceItem',
                array('Days_3', 'Days_5', 'Days_7', 'Days_10', 'Days_30', 'GTC')
            ),
            array(
                '<GetCategoryFeaturesResponse xmlns="urn:ebay:apis:eBLBaseComponents">
                  <Category>
                    <CategoryID>177800</CategoryID>
                    <ListingDuration type="Chinese">1</ListingDuration>
                    <ListingDuration type="Dutch">1</ListingDuration>
                    <ListingDuration type="Live">1</ListingDuration>
                    <ListingDuration type="AdType">2</ListingDuration>
                    <ListingDuration type="StoresFixedPrice">31</ListingDuration>
                    <ListingDuration type="PersonalOffer">1</ListingDuration>
                    <ListingDuration type="FixedPriceItem">1</ListingDuration>
                    <ListingDuration type="LeadGeneration">41</ListingDuration>
                    <StoreOwnerExtendedListingDurations>
                      <Duration>Days_30</Duration>
                      <Duration>GTC</Duration>
                      <Duration>GTC2</Duration>
                    </StoreOwnerExtendedListingDurations>
                  </Category>
                  <SiteDefaults>
                    <ListingDuration type="Chinese">1</ListingDuration>
                    <ListingDuration type="Dutch">1</ListingDuration>
                    <ListingDuration type="Live">1</ListingDuration>
                    <ListingDuration type="AdType">2</ListingDuration>
                    <ListingDuration type="StoresFixedPrice">31</ListingDuration>
                    <ListingDuration type="PersonalOffer">1</ListingDuration>
                    <ListingDuration type="FixedPriceItem">1</ListingDuration>
                    <ListingDuration type="LeadGeneration">41</ListingDuration>
                    <StoreOwnerExtendedListingDurations>
                      <Duration>Days_30</Duration>
                      <Duration>GTC</Duration>
                    </StoreOwnerExtendedListingDurations>
                  </SiteDefaults>
                  <FeatureDefinitions>
                    <ListingDurations Version="3">
                      <ListingDuration durationSetID="1">
                        <Duration>Days_3</Duration>
                        <Duration>Days_5</Duration>
                        <Duration>Days_7</Duration>
                        <Duration>Days_10</Duration>
                      </ListingDuration>
                      <ListingDuration durationSetID="2">
                        <Duration>Days_30</Duration>
                        <Duration>Days_90</Duration>
                      </ListingDuration>
                      <ListingDuration durationSetID="41">
                        <Duration>Days_5</Duration>
                        <Duration>Days_10</Duration>
                        <Duration>Days_30</Duration>
                        <Duration>Days_60</Duration>
                        <Duration>GTC</Duration>
                      </ListingDuration>
                      <ListingDuration durationSetID="31">
                        <Duration>Days_3</Duration>
                        <Duration>Days_5</Duration>
                        <Duration>Days_7</Duration>
                        <Duration>Days_10</Duration>
                        <Duration>Days_30</Duration>
                        <Duration>GTC</Duration>
                      </ListingDuration>
                    </ListingDurations>
                    <StoreOwnerExtendedListingDurations />
                  </FeatureDefinitions>
                </GetCategoryFeaturesResponse>',
                'FixedPriceItem',
                array('Days_3', 'Days_5', 'Days_7', 'Days_10', 'Days_30', 'GTC', 'GTC2')
            ),
            array(
                '<GetCategoryFeaturesResponse xmlns="urn:ebay:apis:eBLBaseComponents">
                  <Category>
                    <CategoryID>177800</CategoryID>
                    <ListingDuration type="Chinese">1</ListingDuration>
                    <ListingDuration type="Dutch">1</ListingDuration>
                    <ListingDuration type="Live">1</ListingDuration>
                    <ListingDuration type="AdType">2</ListingDuration>
                    <ListingDuration type="StoresFixedPrice">31</ListingDuration>
                    <ListingDuration type="PersonalOffer">1</ListingDuration>
                    <ListingDuration type="FixedPriceItem">1</ListingDuration>
                    <ListingDuration type="LeadGeneration">41</ListingDuration>
                  </Category>
                  <SiteDefaults>
                    <ListingDuration type="Chinese">1</ListingDuration>
                    <ListingDuration type="Dutch">1</ListingDuration>
                    <ListingDuration type="Live">1</ListingDuration>
                    <ListingDuration type="AdType">2</ListingDuration>
                    <ListingDuration type="StoresFixedPrice">31</ListingDuration>
                    <ListingDuration type="PersonalOffer">1</ListingDuration>
                    <ListingDuration type="FixedPriceItem">1</ListingDuration>
                    <ListingDuration type="LeadGeneration">41</ListingDuration>
                    <StoreOwnerExtendedListingDurations>
                      <Duration>Days_30</Duration>
                      <Duration>GTC</Duration>
                    </StoreOwnerExtendedListingDurations>
                  </SiteDefaults>
                  <FeatureDefinitions>
                    <ListingDurations Version="3">
                      <ListingDuration durationSetID="1">
                        <Duration>Days_3</Duration>
                        <Duration>Days_5</Duration>
                        <Duration>Days_7</Duration>
                        <Duration>Days_10</Duration>
                      </ListingDuration>
                      <ListingDuration durationSetID="2">
                        <Duration>Days_30</Duration>
                        <Duration>Days_90</Duration>
                      </ListingDuration>
                      <ListingDuration durationSetID="41">
                        <Duration>Days_5</Duration>
                        <Duration>Days_10</Duration>
                        <Duration>Days_30</Duration>
                        <Duration>Days_60</Duration>
                        <Duration>GTC</Duration>
                      </ListingDuration>
                      <ListingDuration durationSetID="31">
                        <Duration>Days_3</Duration>
                        <Duration>Days_5</Duration>
                        <Duration>Days_7</Duration>
                        <Duration>Days_10</Duration>
                        <Duration>Days_30</Duration>
                        <Duration>GTC</Duration>
                      </ListingDuration>
                    </ListingDurations>
                    <StoreOwnerExtendedListingDurations />
                  </FeatureDefinitions>
                </GetCategoryFeaturesResponse>',
                'LeadGeneration',
                array('Days_5', 'Days_10', 'Days_30', 'Days_60', 'GTC')
            )
        );
    }

}