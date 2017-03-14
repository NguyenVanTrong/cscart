<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

use Tygh\Registry;

include_once(Registry::get('config.dir.addons') . 'google_export/schemas/exim_data_feeds/products.functions.php');

$schema = fn_get_schema('exim', 'products');

$google_export_options = fn_get_schema('exim_data_feeds', 'google_export_options');

$schema['options']['skip_zero_prices'] = array(
    'title' => 'addons.google_export.skip_zero_prices',
    'description' => 'addons.google_export.skip_zero_prices_description',
    'type' => 'checkbox',
    'export_only' => true
);

$schema['pre_export_process']['google_export_filter_products'] = array(
    'function' => 'fn_google_export_filter_products',
    'args' => array('$options', '$conditions'),
    'export_only' => true
);

$schema['pre_export_process']['google_export_field_lang'] = array(
    'function' => 'fn_google_export_field_lang_products',
    'args' => array('$options', '$table_fields'),
    'export_only' => true
);

$schema['export_fields']['Google price'] = array(
    'table' => 'product_prices',
    'db_field' => 'price',
    'process_get' => array('fn_exim_google_export_format_price', '#this', '#key', false, false),
    'export_only' => true,
);

$schema['export_fields']['Google price (with tax included)'] = array(
    'table' => 'product_prices',
    'db_field' => 'price',
    'process_get' => array('fn_exim_google_export_format_price', '#this', '#key', false, true),
    'export_only' => true,
);

$schema['export_fields']['Google description'] = array(
    'table' => 'product_descriptions',
    'db_field' => 'full_description',
    'multilang' => true,
    'process_get' => array('fn_exim_google_export_format_description', '#this'),
    'export_only' => true
);

$schema['export_fields']['Google shipping weight'] = array(
    'db_field' => 'weight',
    'process_get' => array('fn_exim_google_export_format_weight', '#this'),
    'export_only' => true,
);

$schema['export_fields']['Sale price'] = array(
    'table' => 'product_prices',
    'db_field' => 'price',
    'process_get' => array('fn_exim_google_export_format_price', '#this', '#key', true, false)
);

$schema['export_fields'] = array_merge($schema['export_fields'], $google_export_options);

$schema['export_processing'] = array(
    'google_export_product_options' => array(
        'function' => 'fn_export_get_options_product_google_export',
        'args' => array(
            'data' => '$data',
            'result' => '$result',
            'export_fields' => '$export_fields',
            'multi_lang' => '$multi_lang'
        ),
        'export_only' => true
    )
);

return $schema;
