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

$schema['export_fields']['eBay template id'] = array (
    'db_field' => 'ebay_template_id',
);

$schema['export_fields']['eBay override price'] = array (
    'db_field' => 'ebay_override_price',
);

$schema['export_fields']['eBay price'] = array (
    'db_field' => 'ebay_price',
);

$schema['export_fields']['eBay override title and description'] = array (
    'table' => 'product_descriptions',
    'db_field' => 'override',
    'multilang' => true,
);

$schema['export_fields']['eBay title'] = array (
    'table' => 'product_descriptions',
    'db_field' => 'ebay_title',
    'multilang' => true,
);

$schema['export_fields']['eBay description'] = array (
    'table' => 'product_descriptions',
    'db_field' => 'ebay_description',
    'multilang' => true,
);

$schema['export_fields']['eBay package type'] = array (
    'db_field' => 'package_type'
);


return $schema;
