<?php
use Tygh\Tests\Unit\ATestCase;
use Tygh\Tests\Unit\Tools;
use Tygh\Tools\Url;

class UrlTest extends ATestCase
{
    /**
     * @dataProvider dpUrlEncoding
     */
    public function testUrlEncoding($input_url, $expected_output, $encode = null)
    {
        $url = new Url($input_url);

        if ($encode === null) {
            $encode = $url->getIsEncoded();
        }

        $this->assertEquals($expected_output, $url->build($encode));
    }

    public function dpUrlEncoding()
    {
        return array(
            array(
                'http://static12.insales.ru/images/products/1/5609/30471657/EXEQ_FreeStyle__фиолетовый.jpg',
                'http://static12.insales.ru/images/products/1/5609/30471657/EXEQ_FreeStyle__%D1%84%D0%B8%D0%BE%D0%BB%D0%B5%D1%82%D0%BE%D0%B2%D1%8B%D0%B9.jpg',
            ),
            array(
                'http://static12.insales.ru/images/products/1/5609/30471657/EXEQ_FreeStyle__%D1%84%D0%B8%D0%BE%D0%BB%D0%B5%D1%82%D0%BE%D0%B2%D1%8B%D0%B9.jpg',
                'http://static12.insales.ru/images/products/1/5609/30471657/EXEQ_FreeStyle__%D1%84%D0%B8%D0%BE%D0%BB%D0%B5%D1%82%D0%BE%D0%B2%D1%8B%D0%B9.jpg',
            ),
            array(
                'http://static12.insales.ru/images/products/1/4893/3789597/case4205_60х115.jpg',
                'http://static12.insales.ru/images/products/1/4893/3789597/case4205_60%D1%85115.jpg',
            ),
            array(
                'http://static12.insales.ru/images/products/1/4893/3789597/case4205_60%D1%85115.jpg',
                'http://static12.insales.ru/images/products/1/4893/3789597/case4205_60%D1%85115.jpg',
            ),
            array(
                'http://cscart.dev/index.php?dispatch=categories.view&category_id=166&features_hash=1-1128-5400-USD',
                'http://cscart.dev/index.php?dispatch=categories.view&category_id=166&features_hash=1-1128-5400-USD',
            ),
            array(
                'http://cscart.dev/api/ foo/bar /test/',
                'http://cscart.dev/api/%20foo/bar%20/test/',
            ),
            array(
                'http://cscart.dev/api/ foo/bar /test',
                'http://cscart.dev/api/%20foo/bar%20/test',
            ),
            array(
                'http://cscart.dev/api/ foo/bar /test/index.php?arg=value&argv[asd]=val',
                'http://cscart.dev/api/%20foo/bar%20/test/index.php?arg=value&argv%5Basd%5D=val',
            ),
            array(
                'https://demo.cs-cart.ru/stores/55077/index.php?result_ids=cart_status%2A%2Cwish_list%2A%2Ccheckout%2A%2Caccount_info%2A&redirect_url=index.php%3Fdispatch%3Dproducts.view%26product_id%3D169&product_data%5B169%5D%5Bproduct_id%5D=169&product_data%5B169%5D%5Bamount%5D=1&appearance%5Bshow_price_values%5D=1&appearance%5Bshow_price%5D=1&appearance%5Bshow_list_discount%5D=1&appearance%5Bshow_product_options%5D=1&appearance%5Bdetails_page%5D=1&appearance%5Bshow_sku%5D=1&appearance%5Bshow_product_amount%5D=1&appearance%5Bshow_qty%5D=1&appearance%5Bcapture_options_vs_qty%5D=&appearance%5Bshow_add_to_cart%5D=1&appearance%5Bshow_list_buttons%5D=1&appearance%5Bbut_role%5D=big&appearance%5Bquick_view%5D=&additional_info%5Binfo_type%5D=D&additional_info%5Bget_icon%5D=1&additional_info%5Bget_detailed%5D=1&additional_info%5Bget_additional%5D=&additional_info%5Bget_options%5D=1&additional_info%5Bget_discounts%5D=1&additional_info%5Bget_features%5D=&additional_info%5Bget_extra%5D=&additional_info%5Bget_taxed_prices%5D=1&additional_info%5Bget_for_one_product%5D=1&additional_info%5Bdetailed_params%5D=1&additional_info%5Bfeatures_display_on%5D=C&full_render=Y&is_ajax=1&dispatch%5Bcheckout.add..169%5D=&security_hash=ce7d5287e6badca19c2b365c3ff2524f',
                'https://demo.cs-cart.ru/stores/55077/index.php?result_ids=cart_status%2A%2Cwish_list%2A%2Ccheckout%2A%2Caccount_info%2A&redirect_url=index.php%3Fdispatch%3Dproducts.view%26product_id%3D169&product_data%5B169%5D%5Bproduct_id%5D=169&product_data%5B169%5D%5Bamount%5D=1&appearance%5Bshow_price_values%5D=1&appearance%5Bshow_price%5D=1&appearance%5Bshow_list_discount%5D=1&appearance%5Bshow_product_options%5D=1&appearance%5Bdetails_page%5D=1&appearance%5Bshow_sku%5D=1&appearance%5Bshow_product_amount%5D=1&appearance%5Bshow_qty%5D=1&appearance%5Bcapture_options_vs_qty%5D=&appearance%5Bshow_add_to_cart%5D=1&appearance%5Bshow_list_buttons%5D=1&appearance%5Bbut_role%5D=big&appearance%5Bquick_view%5D=&additional_info%5Binfo_type%5D=D&additional_info%5Bget_icon%5D=1&additional_info%5Bget_detailed%5D=1&additional_info%5Bget_additional%5D=&additional_info%5Bget_options%5D=1&additional_info%5Bget_discounts%5D=1&additional_info%5Bget_features%5D=&additional_info%5Bget_extra%5D=&additional_info%5Bget_taxed_prices%5D=1&additional_info%5Bget_for_one_product%5D=1&additional_info%5Bdetailed_params%5D=1&additional_info%5Bfeatures_display_on%5D=C&full_render=Y&is_ajax=1&dispatch%5Bcheckout.add..169%5D=&security_hash=ce7d5287e6badca19c2b365c3ff2524f',
            ),
            array(
                'https://demo.cs-cart.ru/stores/55077/index.php?result_ids=cart_status*,wish_list*,checkout*,account_info*&redirect_url=index.php?dispatch=products.view&product_id=169&product_data[169][product_id]=169&appearance[show_price_values]=1&appearance[show_price]=1&appearance[show_list_discount]=1&appearance[show_product_options]=1&appearance[details_page]=1&additional_info[info_type]=D&additional_info[get_icon]=1&additional_info[get_detailed]=1&additional_info[get_additional]=&additional_info[get_options]=1&additional_info[get_discounts]=1&additional_info[get_features]=&additional_info[get_extra]=&additional_info[get_taxed_prices]=1&additional_info[get_for_one_product]=1&additional_info[detailed_params]=1&additional_info[features_display_on]=C&appearance[show_sku]=1&appearance[show_product_amount]=1&appearance[show_qty]=1&appearance[capture_options_vs_qty]=&product_data[169][amount]=1&appearance[show_add_to_cart]=1&appearance[show_list_buttons]=1&appearance[but_role]=big&appearance[quick_view]=&full_render=Y&is_ajax=1&dispatch[checkout.add..169]=&security_hash=ce7d5287e6badca19c2b365c3ff2524f',
                'https://demo.cs-cart.ru/stores/55077/index.php?result_ids=cart_status%2A%2Cwish_list%2A%2Ccheckout%2A%2Caccount_info%2A&redirect_url=index.php%3Fdispatch%3Dproducts.view&product_id=169&product_data%5B169%5D%5Bproduct_id%5D=169&product_data%5B169%5D%5Bamount%5D=1&appearance%5Bshow_price_values%5D=1&appearance%5Bshow_price%5D=1&appearance%5Bshow_list_discount%5D=1&appearance%5Bshow_product_options%5D=1&appearance%5Bdetails_page%5D=1&appearance%5Bshow_sku%5D=1&appearance%5Bshow_product_amount%5D=1&appearance%5Bshow_qty%5D=1&appearance%5Bcapture_options_vs_qty%5D=&appearance%5Bshow_add_to_cart%5D=1&appearance%5Bshow_list_buttons%5D=1&appearance%5Bbut_role%5D=big&appearance%5Bquick_view%5D=&additional_info%5Binfo_type%5D=D&additional_info%5Bget_icon%5D=1&additional_info%5Bget_detailed%5D=1&additional_info%5Bget_additional%5D=&additional_info%5Bget_options%5D=1&additional_info%5Bget_discounts%5D=1&additional_info%5Bget_features%5D=&additional_info%5Bget_extra%5D=&additional_info%5Bget_taxed_prices%5D=1&additional_info%5Bget_for_one_product%5D=1&additional_info%5Bdetailed_params%5D=1&additional_info%5Bfeatures_display_on%5D=C&full_render=Y&is_ajax=1&dispatch%5Bcheckout.add..169%5D=&security_hash=ce7d5287e6badca19c2b365c3ff2524f',
            ),
            array(
                'http://cscart.dev/images/thumbnails/150/150/detailed/0/51g+lhvGGQL.jpg',
                'http://cscart.dev/images/thumbnails/150/150/detailed/0/51g%2BlhvGGQL.jpg',
            )
        );
    }

    /**
     * @dataProvider dpIsSubDomainOf
     */
    public function testIsSubDomainOf($potential_subdomain, $domain, $expected_result)
    {
        $potential_subdomain_url = new Url($potential_subdomain);
        $domain_url = new Url($domain);

        $assert = $potential_subdomain_url->isSubDomainOf($domain_url);

        $expected_result ? $this->assertTrue($assert) : $this->assertFalse($assert);
    }

    public function dpIsSubDomainOf()
    {
        return array(
            array('http://test.cscart.dev', 'http://cscart.dev', true),
            array('http://a.cscart.dev', 'http://a.cscart.dev', false),
            array('http://cscart.dev', 'http://cscart.dev', false),
            array('http://a.cscart.dev', 'http://b.cscart.dev', false),
            array('http://b.a.cscart.dev', 'http://b.cscart.dev', false),
            array('http://b.a.cscart.dev', 'http://a.cscart.dev', true),
        );
    }

    /**
     * @param $url
     * @param $expected
     * @dataProvider dpPunyDecode
     */
    public function testPunyDecode($url, $expected)
    {
        $url = new Url($url);
        $url->punyDecode();

        $this->assertEquals($expected, $url->build());
    }

    public function dpPunyDecode()
    {
        return array(
            array(
                'http://xn----htbbclaabjeejavqnxx6i.xn--p1ai/index.php?dispatch=product.view&product_id=12',
                'http://кириллический-домен.рф/index.php?dispatch=product.view&product_id=12'
            ),
            array(
                'http://cs-cart.com/index.php?dispatch=product.view&product_id=12',
                'http://cs-cart.com/index.php?dispatch=product.view&product_id=12'
            )
        );
    }

    /**
     * @param $url
     * @param $expected
     * @dataProvider dpPunyEncode
     */
    public function testPunyEncode($url, $expected)
    {
        $url = new Url($url);
        $url->punyEncode();

        $this->assertEquals($expected, $url->build());
    }

    public function dpPunyEncode()
    {
        return array(
            array(
                'http://кириллический-домен.рф/index.php?dispatch=product.view&product_id=12',
                'http://xn----htbbclaabjeejavqnxx6i.xn--p1ai/index.php?dispatch=product.view&product_id=12'
            ),
            array(
                'http://cs-cart.com/index.php?dispatch=product.view&product_id=12',
                'http://cs-cart.com/index.php?dispatch=product.view&product_id=12'
            )
        );
    }

    /**
     * @param $url
     * @param $expected
     * @param $full_result
     * @dataProvider dpDecode
     */
    public function testDecode($url, $expected, $full_result)
    {
        $url = Url::decode($url, $full_result);

        $this->assertEquals($expected, $url);
    }

    public function dpDecode()
    {
        return array(
            array(
                'http://cscart.dev/path/to',
                'cscart.dev/path/to',
                false,
            ),
            array(
                'http://xn----htbbclaabjeejavqnxx6i.xn--p1ai/path/to/index.php?dispatch=product.view&product_id=12',
                'кириллический-домен.рф/path/to/index.php',
                false,
            ),
            array(
                'http://cscart.dev:443/path/to',
                'cscart.dev:443/path/to',
                false,
            ),
            array(
                'http://xn----htbbclaabjeejavqnxx6i.xn--p1ai/path/to/index.php?dispatch=product.view&product_id=12',
                'http://кириллический-домен.рф/path/to/index.php?dispatch=product.view&product_id=12',
                true,
            ),
            array(
                'ftp://someone@example.com',
                'ftp://someone@example.com',
                true,
            ),
            array(
                'ftp://someone@xn----htbbclaabjeejavqnxx6i.xn--p1ai',
                'ftp://someone@кириллический-домен.рф',
                true,
            ),
            array(
                'xn----htbbclaabjeejavqnxx6i.xn--p1ai/path/to/index.php',
                'кириллический-домен.рф/path/to/index.php',
                true,
            ),
            array(
                'path/to/index.php',
                'path/to/index.php',
                true,
            ),
        );
    }
}