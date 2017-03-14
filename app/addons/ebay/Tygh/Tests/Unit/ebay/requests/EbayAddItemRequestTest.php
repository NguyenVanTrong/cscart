<?php
namespace Tygh\Tests\Unit\Addons\ebay\requests;

use Tygh\Tests\Unit\ATestCase;

class AddItemRequest extends \Ebay\requests\AddItemRequest
{
    protected function getCompany($company_id)
    {
        return array(
            'company_country' => '',
            'company_zipcode' => 'zip'
        );
    }

    protected function getLocation($country)
    {
        return 'Canada';
    }

    protected function getCurrency()
    {
        return 'USD';
    }
}

class Template extends \Ebay\Template
{
    public $enabled_identifier_codes = array();

    public function getIdentifierUnavailableText()
    {
        return "Not apply";
    }

    public function getEnabledIdentifierCodes($external_category_id)
    {
        return isset($this->enabled_identifier_codes[$external_category_id])
            ? $this->enabled_identifier_codes[$external_category_id]
            : array();
    }
}

class Product extends \Ebay\Product
{
    public $template_data = array();
    public $category = array();

    public function getTemplate()
    {
        return new Template($this->template_data);
    }

    protected function getCategory()
    {
        return $this->category;
    }

    public function getUUID()
    {
        return 'UUID';
    }
}

class EbayAddItemRequestTest extends ATestCase
{
    public function setUp()
    {
        $this->requireMockFunction('__');
        $this->requireMockFunction('fn_format_price');
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @param array $product
     * @param array $results
     * @dataProvider dataProvider
     */
    public function testRequestXml($product, $results)
    {
        $product = new Product($product);

        $request = new AddItemRequest($product);
        $request->setXmlNS(null);
        $xml = simplexml_load_string($request->getXml());

        foreach ($results as $path => $values) {
            $result = $xml->xpath($path);
            $values = (array) $values;

            $this->assertNotEmpty($result, $path);
            $this->assertCount(count($values), $result, json_encode($values));

            foreach ($values as $key => $value) {
                $this->assertEquals($value, (string) $result[$key], $path);
            }
        }
    }

    public function dataProvider()
    {
        return array(
            array(
                array(
                    'product_id' => 12,
                    'ebay_template_id' => 1,
                    'external_id' => 0,
                    'external_pictures' => array(),
                    'external_combinations' => array(
                        'deleted_variants' => array(
                            'price' => 100,
                            'sku' => 'deleted_variants'
                        )
                    ),
                    'template_data' => array(
                        'template_id' => 1,
                        'measure_type' => 'English',
                        'measure_weight' => '453.6',
                        'category' => '10001',
                        'sec_category' => '10021',
                        'ebay_duration' => 'Days_3',
                        'contact_time' => 'Days_14',
                        'dispatch_days' => '5',
                        'refund_method' => 'MoneyBack',
                        'cost_paid_by' => 'Seller',
                        'payment_methods' => array('PayPal'),
                        'paypal_email' => 'custom@example.com',
                        'shipping_type' => 'C',
                        'shippings' => 'FedEx',
                        'enabled_identifier_codes' => array(
                            '10001' => array(
                                'UPC'
                            )
                        ),
                        'identifiers' => array(
                            'product' => array(
                                'UPC' => 3
                            ),
                            'variation' => array(
                                'UPC' => 'code'
                            )
                        )
                    ),
                    'product' => 'Test product',
                    'full_description' => 'full_description',
                    'ebay_description' => 'ebay_description',
                    'ebay_title' => 'ebay_title',
                    'ebay_price' => 50,
                    'base_price' => 100,
                    'price' => 100,
                    'box_length' => 1,
                    'box_width' => 2,
                    'box_height' => 3,
                    'tracking' => 'B',
                    'amount' => 10,
                    'package_type' => 'package_type',
                    'override' => 'N',
                    'ebay_override_price' => 'N',
                    'weight' => 1.6,
                    'product_options' => array(
                        array(
                            'option_id' => 1,
                            'option_name' => 'Color',
                            'inventory' => 'Y',
                            'variants' => array(
                                array(
                                    'variant_id' => 1,
                                    'variant_name' => 'Color1',
                                    'modifier' => 100,
                                    'modifier_type' => 'A'
                                ),
                                array(
                                    'variant_id' => 2,
                                    'variant_name' => 'Color2',
                                    'modifier' => 50,
                                    'modifier_type' => 'P'
                                ),
                            )
                        ),
                        array(
                            'option_id' => 2,
                            'option_name' => 'Size',
                            'inventory' => 'Y',
                            'variants' => array(
                                array(
                                    'variant_id' => 1,
                                    'variant_name' => 'Size1',
                                    'modifier' => 10,
                                    'modifier_type' => 'A'
                                ),
                                array(
                                    'variant_id' => 2,
                                    'variant_name' => 'Size2',
                                    'modifier' => 50,
                                    'modifier_type' => 'P'
                                ),
                            )
                        )
                    ),
                    'combinations' => array(
                        array(
                            'product_code' => 'product_code1',
                            'amount' => 10,
                            'combination' => array(
                                1 => 1,
                                2 => 1
                            )
                        ),
                        array(
                            'product_code' => 'product_code2',
                            'amount' => 10,
                            'combination' => array(
                                1 => 1,
                                2 => 2
                            )
                        ),
                        array(
                            'product_code' => 'product_code3',
                            'amount' => 10,
                            'combination' => array(
                                1 => 2,
                                2 => 1
                            )
                        ),
                        array(
                            'product_code' => 'product_code4',
                            'amount' => 10,
                            'combination' => array(
                                1 => 2,
                                2 => 2
                            )
                        ),
                    ),
                    'product_features' => array(
                        1 => array(
                            'feature_id' => 1,
                            'feature_type' => \Tygh\Enum\ProductFeatures::SINGLE_CHECKBOX,
                            'description' => 'Test feature 1',
                            'value' => 'Y'
                        ),
                        2 => array(
                            'feature_id' => 2,
                            'feature_type' => \Tygh\Enum\ProductFeatures::TEXT_FIELD,
                            'description' => 'Test feature 2',
                            'value' => 'value'
                        ),
                        3 => array(
                            'feature_id' => 3,
                            'feature_type' => \Tygh\Enum\ProductFeatures::EXTENDED,
                            'description' => 'Brand',
                            'variant_id' => 1,
                            'variants' => array(
                                1 => array(
                                    'variant_id' => 1,
                                    'variant' => 'Adidas',
                                    'selected' => 1
                                ),
                                2 => array(
                                    'variant_id' => 2,
                                    'variant' => 'Nike',
                                    'selected' => null
                                ),
                            )
                        )
                    )
                ),
                array(
                    'Item/PrimaryCategory/CategoryID' => '10001',
                    'Item/SecondaryCategory/CategoryID' => '10021',
                    'Item/Title' => 'Test product',
                    'Item/Description' => 'full_description',
                    'Item/ListingDuration' => 'Days_3',
                    'Item/DispatchTimeMax' => '5',
                    'Item/ReturnPolicy/RefundOption' => 'MoneyBack',
                    'Item/ReturnPolicy/ReturnsWithinOption' => 'Days_14',
                    'Item/ReturnPolicy/ShippingCostPaidByOption' => 'Seller',
                    'Item/PaymentMethods' => 'PayPal',
                    'Item/PayPalEmailAddress' => 'custom@example.com',
                    'Item/ShippingDetails/ShippingType' => 'Calculated',
                    'Item/ShippingDetails/CalculatedShippingRate/PackageDepth' => '3',
                    'Item/ShippingDetails/CalculatedShippingRate/PackageLength' => '1',
                    'Item/ShippingDetails/CalculatedShippingRate/PackageWidth' => '2',
                    'Item/ShippingDetails/CalculatedShippingRate/ShippingPackage' => 'package_type',
                    'Item/ShippingDetails/CalculatedShippingRate/WeightMajor' => '1',
                    'Item/ShippingDetails/CalculatedShippingRate/WeightMinor' => '9.6',
                    'Item/ShippingDetails/ShippingServiceOptions/ShippingService' => 'FedEx',
                    'Item/ItemSpecifics/NameValueList/Name' => array(
                        'Test feature 1', 'Test feature 2', 'Brand'
                    ),
                    'Item/ItemSpecifics/NameValueList/Value' => array('yes', 'value', 'Adidas'),
                    'Item/Variations/VariationSpecificsSet/NameValueList/Name' => array('Color', 'Size'),
                    'Item/Variations/VariationSpecificsSet/NameValueList/Value' => array(
                        'Color1', 'Color2', 'Size1', 'Size2'
                    ),
                    'Item/Variations/Variation/SKU' => array(
                        'ColorColor1SizeSize1', 'ColorColor1SizeSize2',
                        'ColorColor2SizeSize1', 'ColorColor2SizeSize2',
                        'deleted_variants'
                    ),
                    'Item/Variations/Variation/StartPrice' => array(
                        210, 250, 160, 200
                    ),
                    'Item/Variations/Variation/Delete' => array('true'),
                    'Item/Variations/Variation/VariationProductListingDetails/UPC' => array(
                        'product_code1', 'product_code2', 'product_code3', 'product_code4'
                    )
                )
            ),
            array(
                array(
                    'product_id' => 12,
                    'ebay_template_id' => 1,
                    'main_category' => 255,
                    'product' => 'Test product',
                    'external_id' => 0,
                    'external_pictures' => array(),
                    'external_combinations' => array(),
                    'combinations' => array(),
                    'category' => array(
                        'ebay_category_id' => 20001,
                        'ebay_site_id' => 0,
                    ),
                    'template_data' => array(
                        'template_id' => 1,
                        'measure_type' => 'Metric',
                        'measure_weight' => '1000',
                        'category' => '10001',
                        'sec_category' => '10021',
                        'ebay_duration' => 'Days_3',
                        'contact_time' => 'Days_14',
                        'dispatch_days' => '5',
                        'refund_method' => 'MoneyBack',
                        'cost_paid_by' => 'Seller',
                        'payment_methods' => array('PayPal'),
                        'paypal_email' => 'custom@example.com',
                        'shipping_type' => 'C',
                        'shippings' => 'FedEx',
                        'enabled_identifier_codes' => array(
                            '10001' => array(
                                'UPC', 'BRAND', 'MPN'
                            ),
                            '20001' => array(
                                'ISBN', 'BRAND', 'MPN'
                            ),
                        ),
                        'identifiers' => array(
                            'product' => array(
                                'UPC' => 3,
                                'ISBN' => 2,
                                'BRAND' => 3,
                                'MPN' => 'not_apply',
                            ),
                            'variation' => array(
                                'UPC' => 'code'
                            )
                        )
                    ),
                    'full_description' => 'full_description',
                    'ebay_description' => 'ebay_description',
                    'ebay_title' => 'ebay_title',
                    'ebay_price' => 50,
                    'base_price' => 100,
                    'price' => 100,
                    'box_length' => 1,
                    'box_width' => 2,
                    'box_height' => 3,
                    'tracking' => 'B',
                    'amount' => 10,
                    'package_type' => 'package_type',
                    'override' => 'N',
                    'ebay_override_price' => 'N',
                    'weight' => 1.6,
                    'product_options' => array(),
                    'product_features' => array(
                        1 => array(
                            'feature_id' => 1,
                            'feature_type' => \Tygh\Enum\ProductFeatures::SINGLE_CHECKBOX,
                            'description' => 'Test feature 1',
                            'value' => 'Y'
                        ),
                        2 => array(
                            'feature_id' => 2,
                            'feature_type' => \Tygh\Enum\ProductFeatures::TEXT_FIELD,
                            'description' => 'Test feature 2',
                            'value' => 'value'
                        ),
                        3 => array(
                            'feature_id' => 3,
                            'feature_type' => \Tygh\Enum\ProductFeatures::EXTENDED,
                            'description' => 'Brand2',
                            'variant_id' => 1,
                            'variants' => array(
                                1 => array(
                                    'variant_id' => 1,
                                    'variant' => 'Adidas',
                                    'selected' => 1
                                ),
                                2 => array(
                                    'variant_id' => 2,
                                    'variant' => 'Nike',
                                    'selected' => null
                                ),
                            )
                        )
                    )
                ),
                array(
                    'Item/PrimaryCategory/CategoryID' => '20001',
                    'Item/ShippingDetails/CalculatedShippingRate/WeightMajor' => '1',
                    'Item/ShippingDetails/CalculatedShippingRate/WeightMinor' => '600',
                    'Item/ItemSpecifics/NameValueList/Name' => array(
                        'Brand', 'MPN', 'Test feature 1'
                    ),
                    'Item/ItemSpecifics/NameValueList/Value' => array('Adidas', 'Not apply', 'yes'),
                    'Item/ProductListingDetails/BrandMPN/Brand' => 'Adidas',
                    'Item/ProductListingDetails/BrandMPN/MPN' => 'Not apply',
                    'Item/ProductListingDetails/ISBN' => 'value'
                )
            ),
            array(
                array(
                    'product_id' => 7,
                    'main_category' => 256,
                    'ebay_template_id' => 2,
                    'external_id' => 0,
                    'external_pictures' => array(),
                    'external_combinations' => array(),
                    'template_data' => array(
                        'template_id' => 1,
                        'measure_type' => 'English',
                        'measure_weight' => '453.6',
                        'category' => '20001',
                        'sec_category' => '20021',
                        'ebay_duration' => 'Days_4',
                        'contact_time' => 'Days_13',
                        'dispatch_days' => '6',
                        'refund_method' => 'MoneyBack',
                        'cost_paid_by' => 'Seller',
                        'payment_methods' => array('PayPal'),
                        'paypal_email' => 'custom@example.com',
                        'shipping_type' => 'C',
                        'shippings' => 'FedEx'
                    ),
                    'product' => 'Test product',
                    'full_description' => 'full_description',
                    'ebay_description' => 'ebay_description',
                    'ebay_title' => 'ebay_title',
                    'ebay_price' => 50,
                    'base_price' => 100,
                    'price' => 100,
                    'box_length' => 1,
                    'box_width' => 2,
                    'box_height' => 3,
                    'tracking' => 'B',
                    'amount' => 10,
                    'package_type' => 'package_type',
                    'override' => 'Y',
                    'ebay_override_price' => 'Y',
                    'weight' => 1.6,
                    'product_options' => array(
                        array(
                            'option_id' => 1,
                            'option_name' => 'Color',
                            'inventory' => 'Y',
                            'variants' => array(
                                array(
                                    'variant_id' => 1,
                                    'variant_name' => 'GreyHeather/Core Energy',
                                    'modifier' => 100,
                                    'modifier_type' => 'A'
                                ),
                                array(
                                    'variant_id' => 2,
                                    'variant_name' => 'Cardinal/PRIME YELLOW',
                                    'modifier' => 10,
                                    'modifier_type' => 'P'
                                ),
                            )
                        ),
                        array(
                            'option_id' => 2,
                            'option_name' => 'Size',
                            'inventory' => 'Y',
                            'variants' => array(
                                array(
                                    'variant_id' => 1,
                                    'variant_name' => 'Small',
                                    'modifier' => 0,
                                    'modifier_type' => 'A'
                                ),
                                array(
                                    'variant_id' => 2,
                                    'variant_name' => 'Medium',
                                    'modifier' => 0,
                                    'modifier_type' => 'P'
                                ),
                            )
                        )
                    ),
                    'combinations' => array(
                        array(
                            'product_code' => 'product_code',
                            'amount' => 10,
                            'combination' => array(
                                1 => 1,
                                2 => 1
                            )
                        ),
                        array(
                            'product_code' => 'product_code',
                            'amount' => 10,
                            'combination' => array(
                                1 => 1,
                                2 => 2
                            )
                        ),
                        array(
                            'product_code' => 'product_code',
                            'amount' => 10,
                            'combination' => array(
                                1 => 2,
                                2 => 1
                            )
                        ),
                        array(
                            'product_code' => 'product_code',
                            'amount' => 10,
                            'combination' => array(
                                1 => 2,
                                2 => 2
                            )
                        ),
                    ),
                    'product_features' => array(
                        1 => array(
                            'feature_id' => 1,
                            'feature_type' => \Tygh\Enum\ProductFeatures::SINGLE_CHECKBOX,
                            'description' => 'Test feature 1',
                            'value' => 'Y'
                        ),
                        2 => array(
                            'feature_id' => 2,
                            'feature_type' => \Tygh\Enum\ProductFeatures::TEXT_FIELD,
                            'description' => 'Test feature 2',
                            'value' => 'value'
                        ),
                        3 => array(
                            'feature_type' => \Tygh\Enum\ProductFeatures::EXTENDED,
                            'description' => 'Brand',
                            'variant_id' => 1,
                            'variants' => array(
                                1 => array(
                                    'variant_id' => 1,
                                    'variant' => 'Adidas',
                                    'selected' => null
                                ),
                                2 => array(
                                    'variant_id' => 2,
                                    'variant' => 'Nike',
                                    'selected' => 2
                                ),
                            )
                        )
                    )
                ),
                array(
                    'Item/PrimaryCategory/CategoryID' => '20001',
                    'Item/SecondaryCategory/CategoryID' => '20021',
                    'Item/Title' => 'ebay_title',
                    'Item/Description' => 'ebay_description',
                    'Item/ListingDuration' => 'Days_4',
                    'Item/DispatchTimeMax' => '6',
                    'Item/ReturnPolicy/RefundOption' => 'MoneyBack',
                    'Item/ReturnPolicy/ReturnsWithinOption' => 'Days_13',
                    'Item/ReturnPolicy/ShippingCostPaidByOption' => 'Seller',
                    'Item/PaymentMethods' => 'PayPal',
                    'Item/PayPalEmailAddress' => 'custom@example.com',
                    'Item/ShippingDetails/ShippingServiceOptions/ShippingService' => 'FedEx',
                    'Item/ItemSpecifics/NameValueList/Name' => array(
                        'Test feature 1', 'Test feature 2', 'Brand'
                    ),
                    'Item/ItemSpecifics/NameValueList/Value' => array('yes', 'value', 'Nike'),
                    'Item/Variations/VariationSpecificsSet/NameValueList/Name' => array('Color', 'Size'),
                    'Item/Variations/VariationSpecificsSet/NameValueList/Value' => array(
                        'GreyHeather/Core Energy', 'Cardinal/PRIME YELLOW',
                        'Small', 'Medium',
                    ),
                    'Item/Variations/Variation/SKU' => array(
                        'ColorGreyHeather/Core EnergySizeSmall', 'ColorGreyHeather/Core EnergySizeMedium',
                        'ColorCardinal/PRIME YELLOWSizeSmall', 'ColorCardinal/PRIME YELLOWSizeMedium'
                    )
                )
            )
        );
    }
}