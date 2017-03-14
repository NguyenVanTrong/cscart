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

$schema['categories']['view'] = array(
    'base_url' => 'categories.view?category_id=[category_id]',
    'request_handlers' => array(
        'category_id' => true
    ),
    'search' => true
);
$schema['companies']['products'] = array(
    'base_url' => array('fn_seo_filter_current_url' => array("result_ids", "full_render", "filter_id", "view_all", "req_range_id", "features_hash", "subcats", "page", "total", "hint_q")),
    'search' => true
);
$schema['companies']['catalog'] = array(
    'base_url' => 'companies.catalog',
    'search' => true
);
$schema['index']['index'] = array(
    'base_url' => array('fn_url' => array('')),
    'search' => array()
);
$schema['product_features']['view'] = array(
    'base_url' => 'product_features.view?variant_id=[variant_id]',
    'request_handlers' => array(
        'variant_id' => true
    ),
    'search' => true
);
$schema['products']['view'] = array(
    'base_url' => 'products.view?product_id=[product_id]',
    'request_handlers' => array(
        'product_id' => true
    ),
    'search' => array()
);
$schema['products']['search'] = array(
    'base_url' => array('fn_seo_filter_current_url' => array("result_ids", "full_render", "filter_id", "view_all", "req_range_id", "features_hash", "subcats", "page", "total", "hint_q")),
    'request_handlers' => array(
        'search_performed' => true
    ),
    'search' => true
);

return $schema;
