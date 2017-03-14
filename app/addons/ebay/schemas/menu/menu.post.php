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

$schema['central']['marketing']['items']['ebay'] = array(
    'attrs' => array(
        'class'=>'is-addon'
    ),
    'href' => 'ebay.manage',
    'position' => 500,
    'subitems' => array(
        'ebay_templates' => array(
            'href' => 'ebay.manage',
            'position' => 100,
        ),
        'ebay_logs' => array(
            'href' => 'ebay.product_logs',
            'position' => 200,
        ),
        'ebay_products' => array(
            'href' => 'products.manage?ebay_template_id=any',
            'position' => 300,
        )
    ),
);

return $schema;
