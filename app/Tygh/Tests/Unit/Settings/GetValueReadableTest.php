<?php

namespace Tygh\Tests\Unit\Settings;

use Tygh\Settings;
use Tygh\Tests\Unit\ATestCase;

class GetValueReadableTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    public function setUp()
    {
        $this->requireMockFunction('__');
    }

    /**
     * @dataProvider testGeneralProvider
     */
    public function testGeneral($setting_data, $value, $expected)
    {
        $actual = Settings::getValueReadable($setting_data, $value);

        $this->assertEquals($expected, $actual);
    }

    public function testGeneralProvider()
    {
        return array(
            array(
                array (
                    'object_id' => '10',
                    'name' => 'ftp_password',
                    'section_id' => '13',
                    'section_tab_id' => '0',
                    'type' => 'P',
                    'edition_type' => 'ROOT',
                    'position' => '50',
                    'is_global' => 'N',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => 'Password',
                    'tooltip' => NULL,
                    'object_type' => 'O',
                    'value' => '',
                    'section_name' => 'Upgrade_center',
                    'section_tab_name' => 'main',
                    'variants' =>
                        array (
                        ),
                ), null, '********'
            ),
            array(
                array (
                    'object_id' => '2',
                    'name' => 'allow_usergroup_signup',
                    'section_id' => '2',
                    'section_tab_id' => '0',
                    'type' => 'C',
                    'edition_type' => 'ROOT,ULT:VENDOR',
                    'position' => '249',
                    'is_global' => 'Y',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => 'Allow customer to signup for user group',
                    'tooltip' => NULL,
                    'object_type' => 'O',
                    'value' => 'Y',
                    'section_name' => 'General',
                    'section_tab_name' => 'main',
                    'variants' =>
                        array (
                        ),
                ), null, 'yes'
            ),
            array(
                array (
                    'object_id' => '5973',
                    'name' => 'main_store_mode',
                    'section_id' => '31',
                    'section_tab_id' => '32',
                    'type' => 'R',
                    'edition_type' => 'ROOT,ULT:VENDOR',
                    'position' => '0',
                    'is_global' => 'N',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => 'Store operation mode',
                    'tooltip' => '',
                    'object_type' => 'O',
                    'value' => 'catalog',
                    'section_name' => 'catalog_mode',
                    'section_tab_name' => 'general',
                    'variants' =>
                        array (
                            'catalog' => 'Catalog',
                            'store' => 'Store',
                        ),
                ), 'store', 'Store'
            ),
            array(
                array (
                    'object_id' => '3',
                    'name' => 'exception_style',
                    'section_id' => '2',
                    'section_tab_id' => '0',
                    'type' => 'S',
                    'edition_type' => 'ROOT,ULT:VENDOR',
                    'position' => '160',
                    'is_global' => 'Y',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => 'Exception style',
                    'tooltip' => NULL,
                    'object_type' => 'O',
                    'value' => 'hide',
                    'section_name' => 'General',
                    'section_tab_name' => 'main',
                    'variants' =>
                        array (
                            'hide' => 'Hide step completely',
                            'warning' => 'Show warning on exception',
                        ),
                ), 'warning', 'Show warning on exception'
            ),
            array(
                array (
                    'object_id' => '169',
                    'name' => 'default_products_view',
                    'section_id' => '4',
                    'section_tab_id' => '0',
                    'type' => 'K',
                    'edition_type' => 'ROOT,ULT:VENDOR',
                    'position' => '195',
                    'is_global' => 'Y',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => 'Product list default view',
                    'tooltip' => NULL,
                    'object_type' => 'O',
                    'value' => 'products_without_options',
                    'section_name' => 'Appearance',
                    'section_tab_name' => 'main',
                    'variants' =>
                        array (
                            'products_multicolumns' => 'Grid',
                            'products_without_options' => 'List without options',
                            'short_list' => 'Compact list',
                        ),
                ), null, 'List without options'
            ),
            array(
                array (
                    'object_id' => '171',
                    'name' => 'default_products_view_templates',
                    'section_id' => '4',
                    'section_tab_id' => '0',
                    'type' => 'G',
                    'edition_type' => 'ROOT,ULT:VENDOR',
                    'position' => '194',
                    'is_global' => 'Y',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => 'Available product list views',
                    'tooltip' => NULL,
                    'object_type' => 'O',
                    'value' =>
                        array (
                            'products_multicolumns' => 'Y',
                            'products_without_options' => 'Y',
                            'short_list' => 'Y',
                        ),
                    'section_name' => 'Appearance',
                    'section_tab_name' => 'main',
                    'variants' =>
                        array (
                            'products_multicolumns' => 'Grid',
                            'products_without_options' => 'List without options',
                            'short_list' => 'Compact list',
                        ),
                ), null, 'Grid, List without options, Compact list'
            ),
            array(
                array (
                    'object_id' => '171',
                    'name' => 'default_products_view_templates',
                    'section_id' => '4',
                    'section_tab_id' => '0',
                    'type' => 'G',
                    'edition_type' => 'ROOT,ULT:VENDOR',
                    'position' => '194',
                    'is_global' => 'Y',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => 'Available product list views',
                    'tooltip' => NULL,
                    'object_type' => 'O',
                    'value' =>
                        array (
                            'products_multicolumns' => 'Y',
                            'products_without_options' => 'Y',
                            'short_list' => 'Y',
                        ),
                    'section_name' => 'Appearance',
                    'section_tab_name' => 'main',
                    'variants' =>
                        array (
                            'products_multicolumns' => 'Grid',
                            'products_without_options' => 'List without options',
                            'short_list' => 'Compact list',
                        ),
                ), array('products_multicolumns', 'short_list'), 'Grid, Compact list'
            ),
            array(
                array (
                    'object_id' => '6097',
                    'name' => 'price_list_fields',
                    'section_id' => '76',
                    'section_tab_id' => '77',
                    'type' => 'B',
                    'edition_type' => 'ROOT,ULT:VENDOR',
                    'position' => '10',
                    'is_global' => 'N',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => 'Fields',
                    'tooltip' => '',
                    'object_type' => 'O',
                    'value' =>
                        array (
                            'product_code' => 'Y',
                            'product' => 'Y'
                        ),
                    'section_name' => 'price_list',
                    'section_tab_name' => 'general',
                    'variants' =>
                        array (
                            'product_id' => 'Product ID',
                            'product' => 'Product name',
                            'min_qty' => 'Minimum order quantity',
                            'max_qty' => 'Maximum order quantity',
                            'product_code' => 'CODE',
                            'amount' => 'Quantity',
                            'price' => 'Price',
                            'weight' => 'Weight',
                            'image' => 'Image',
                        ),
                ), null, 'Product name, CODE'
            ),
            array(
                array (
                    'object_id' => '5934',
                    'name' => 'elm_administrator_area_settings',
                    'section_id' => '18',
                    'section_tab_id' => '19',
                    'type' => 'H',
                    'edition_type' => 'ROOT',
                    'position' => '0',
                    'is_global' => 'N',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => 'Administrator area settings',
                    'tooltip' => '',
                    'object_type' => 'O',
                    'value' => '',
                    'section_name' => 'access_restrictions',
                    'section_tab_name' => 'general',
                    'variants' =>
                        array (
                        ),
                ), null, ''
            ),
            array(
                array (
                    'object_id' => '6067',
                    'name' => 'map_provider_settings',
                    'section_id' => '62',
                    'section_tab_id' => '63',
                    'type' => 'E',
                    'edition_type' => 'ROOT',
                    'position' => '30',
                    'is_global' => 'N',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => '',
                    'tooltip' => '',
                    'object_type' => 'O',
                    'value' => 'map_provider_settings.tpl',
                    'section_name' => 'maps_provider',
                    'section_tab_name' => 'general',
                    'variants' =>
                        array (
                        ),
                ), null, ''
            ),
            array(
                array (
                    'object_id' => '6294',
                    'name' => 'template',
                    'section_id' => '118',
                    'section_tab_id' => '119',
                    'type' => 'Z',
                    'edition_type' => 'ROOT,ULT:VENDOR',
                    'position' => '0',
                    'is_global' => 'N',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => '',
                    'tooltip' => '',
                    'object_type' => 'O',
                    'value' => 'admin_panel.tpl',
                    'section_name' => 'searchanise',
                    'section_tab_name' => 'configuration',
                    'variants' =>
                        array (
                        ),
                ), null, ''
            ),
            array(
                array (
                    'object_id' => '6398',
                    'name' => 'info_deprecated',
                    'section_id' => '144',
                    'section_tab_id' => '145',
                    'type' => 'O',
                    'edition_type' => 'ROOT,ULT:VENDOR',
                    'position' => '0',
                    'is_global' => 'N',
                    'handler' => 'fn_yandex_market_info_deprecated',
                    'parent_id' => '0',
                    'description' => '',
                    'tooltip' => '',
                    'object_type' => 'O',
                    'value' => '',
                    'info' => 'Use the YML Export add-on to export data to YML. The Yandex.Market add-on has an outdated mechanism of generating YML files.',
                    'section_name' => 'yandex_market',
                    'section_tab_name' => 'price_list',
                    'variants' =>
                        array (
                        ),
                ), null, 'Use the YML Export add-on to export data to YML. The Yandex.Market add-on has an outdated mechanism of generating YML files.'
            ),
            array(
                array (
                    'object_id' => '124',
                    'name' => 'background_image',
                    'section_id' => '11',
                    'section_tab_id' => '0',
                    'type' => 'F',
                    'edition_type' => 'ROOT,ULT:VENDOR',
                    'position' => '80',
                    'is_global' => 'N',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => 'Background image',
                    'tooltip' => NULL,
                    'object_type' => 'O',
                    'value' => 'pages/about_flag.png',
                    'section_name' => 'Image_verification',
                    'section_tab_name' => 'main',
                    'variants' =>
                        array (
                        ),
                ), null, 'pages/about_flag.png'
            ),
            array(
                array (
                    'object_id' => '5678',
                    'name' => 'current_timestamp',
                    'section_id' => '0',
                    'section_tab_id' => '0',
                    'type' => 'T',
                    'edition_type' => 'ROOT',
                    'position' => '10',
                    'is_global' => 'Y',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => NULL,
                    'tooltip' => NULL,
                    'object_type' => NULL,
                    'value' => '1484742441',
                    'section_tab_name' => '',
                    'section_name' => '',
                    'variants' =>
                        array (
                        ),
                ), null, '1484742441'
            ),
            array(
                array (
                    'object_id' => '5949',
                    'name' => 'antifraud_order_status',
                    'section_id' => '20',
                    'section_tab_id' => '21',
                    'type' => 'D',
                    'edition_type' => 'ROOT',
                    'position' => '40',
                    'is_global' => 'N',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => 'Order status',
                    'tooltip' => '',
                    'object_type' => 'O',
                    'value' => 'A',
                    'section_name' => 'anti_fraud',
                    'section_tab_name' => 'general',
                    'variants' =>
                        array (
                        ),
                ), null, 'A'
            ),
            array(
                array (
                    'object_id' => '17',
                    'name' => 'default_address',
                    'section_id' => '2',
                    'section_tab_id' => '0',
                    'type' => 'I',
                    'edition_type' => 'ROOT,ULT:VENDOR',
                    'position' => '70',
                    'is_global' => 'Y',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => 'Default address',
                    'tooltip' => NULL,
                    'object_type' => 'O',
                    'value' => 'Boston street',
                    'section_name' => 'General',
                    'section_tab_name' => 'main',
                    'variants' =>
                        array (
                        ),
                ), '5th avenue', '5th avenue'
            ),
            array(
                array (
                    'object_id' => '159',
                    'name' => 'log_type_users',
                    'section_id' => '12',
                    'section_tab_id' => '0',
                    'type' => 'N',
                    'edition_type' => 'ROOT,VENDOR',
                    'position' => '10',
                    'is_global' => 'Y',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => 'Users',
                    'tooltip' => NULL,
                    'object_type' => 'O',
                    'value' =>
                        array (
                            'create' => 'Y',
                            'delete' => 'Y',
                            'update' => 'Y',
                            'session' => 'Y',
                            'failed_login' => 'Y',
                        ),
                    'section_name' => 'Logging',
                    'section_tab_name' => 'main',
                    'variants' =>
                        array (
                            'create' => 'Create',
                            'delete' => 'Delete',
                            'update' => 'Update',
                            'session' => 'Session',
                            'failed_login' => 'Failed logins',
                        ),
                ), array('create', 'update'), 'Create, Update'
            ),
            array(
                array (
                    'object_id' => '22',
                    'name' => 'products_per_page',
                    'section_id' => '4',
                    'section_tab_id' => '0',
                    'type' => 'U',
                    'edition_type' => 'ROOT,ULT:VENDOR',
                    'position' => '100',
                    'is_global' => 'Y',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => 'Products per page',
                    'tooltip' => NULL,
                    'object_type' => 'O',
                    'value' => '12',
                    'section_name' => 'Appearance',
                    'section_tab_name' => 'main',
                    'variants' =>
                        array (
                        ),
                ), null, '12'
            ),
            array(
                array (
                    'object_id' => '6475',
                    'name' => 'multiple_select',
                    'section_id' => '155',
                    'section_tab_id' => '157',
                    'type' => 'M',
                    'edition_type' => 'ROOT,ULT:VENDOR',
                    'position' => '30',
                    'is_global' => 'N',
                    'handler' => '',
                    'parent_id' => '0',
                    'description' => 'Multiple select',
                    'tooltip' => '',
                    'object_type' => 'O',
                    'value' =>
                        array (
                            'select_box_1' => 'Y',
                            'select_box_3' => 'Y',
                        ),
                    'section_name' => 'sample_addon_3_0',
                    'section_tab_name' => 'section2',
                    'variants' =>
                        array (
                            'select_box_1' => 'Select box 1',
                            'select_box_2' => 'Select box 2',
                            'select_box_3' => 'Select box 3',
                        ),
                ), null, 'Select box 1, Select box 3'
            )
            // test cases for country list and state list are not implemented
        );
    }
}