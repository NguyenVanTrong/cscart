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

$schema = array (
    'object_name' => 'product',
    'fields' => array (
        'product_id' => array (
            'db_field' => 'product_id'
        ),
        'status' => array (
            'db_field' => 'status'
        ),
        'list_price' => array (
            'db_field' => 'list_price'
        ),
        'amount' => array (
            'db_field' => 'amount'
        ),
        'product' => array (
            'db_field' => 'product'
        ),
        'price' => array (
            'db_field' => 'price'
        ),
        'base_price' => array (
            'db_field' => 'base_price'
        ),
        'zero_price_action' => array (
            'db_field' => 'zero_price_action'
        ),
        'track_inventory' => array (
            'table' => 'products',
            'db_field' => 'tracking'
        ),
        'discounts' => array (
            'db_field' => 'discounts'
        ),
        'icon' => array (
            'schema' => array (
                'is_single' => true,
                'type' => 'images',
                'name' => 'icon',
                'filter' => array (
                    'image_id' => array (
                        'table' => 'image_links',
                        'db_field' => 'image_id'
                    )
                )
            )
        ),
        'taxes' => array(
            'process_get' => array (
                'func' => 'ExTwigmo\\Core\\Api::getAsList',
                'params' => array (
                    'schema' => array (
                        'value' => 'product_taxes'
                    ),
                    'taxes' => array (
                        'db_field' => 'taxes'
                    )
                )
            )
        ),
    )
);
return $schema;
