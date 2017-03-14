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

include_once(Registry::get('config.dir.schemas') . 'clone/blocks.functions.php');

return array(
    'menu' => array(
        'function' => 'fn_ult_clone_layout_block_menu'
    ),
    'products' => array(
        'function' => 'fn_ult_clone_layout_block_configured_by_filling',
        'config' => array(
            'fillings_handlers' => array(
                'manually' => array('fn_ult_clone_layout_block_products_filling_by_manually'),
                'newest' => array('fn_ult_clone_layout_block_products_filling_by_category'),
                'most_popular' => array('fn_ult_clone_layout_block_products_filling_by_category'),
            )
        )
    ),
    'categories' => array(
        'function' => 'fn_ult_clone_layout_block_configured_by_filling',
        'config' => array(
            'fillings_handlers' => array(
                'full_tree_cat' => array('fn_ult_clone_layout_block_categories_filling_by_full_tree_cat'),
                'manually' => array('fn_ult_clone_layout_block_categories_filling_by_manually'),
            )
        )
    ),
    'pages' => array(
        'function' => 'fn_ult_clone_layout_block_configured_by_filling',
        'config' => array(
            'fillings_handlers' => array(
                'full_tree_pages' => array('fn_ult_clone_layout_block_pages_filling_by_tree'),
                'dynamic_tree_pages' => array('fn_ult_clone_layout_block_pages_filling_by_tree'),
                'manually' => array('fn_ult_clone_layout_block_pages_filling_by_manually'),
            )
        )
    ),
    'product_filters' => array(
        'function' => 'fn_ult_clone_layout_block_configured_by_filling',
        'config' => array(
            'fillings_handlers' => array(
                'manually' => array('fn_ult_clone_layout_block_product_filters_filling_by_manually')
            )
        )
    ),
    'product_filters_home' => array(
        'function' => 'fn_ult_clone_layout_block_configured_by_filling',
        'config' => array(
            'fillings_handlers' => array(
                'manually' => array('fn_ult_clone_layout_block_product_filters_filling_by_manually')
            )
        )
    ),
);