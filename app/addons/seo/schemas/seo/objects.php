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

$schema = array(
    'c' => array(
        'tree' => true,
        'path_function' => function ($object_id) {
            $path = db_get_field("SELECT id_path FROM ?:categories WHERE category_id = ?i", $object_id);
            $apath = explode('/', $path);
            array_pop($apath);

            return implode('/', $apath);
        },
        'parent_type' => 'c',

        'name' => 'category',
        'picker' => 'pickers/categories/picker.tpl',
        'picker_params' => array(
            'multiple' => false,
            'use_keys' => 'N'
        ),

        'table' => '?:category_descriptions',
        'description' => 'category',
        'dispatch' => 'categories.view',
        'item' => 'category_id',
        'condition' => '',
        'not_shared' => true,

        'tree_options' => array('category', 'category_nohtml'),
        'html_options' => array('file', 'category'),
        'pager' => true,
        'option' => 'seo_category_type',

        'exist_function' => function($category_id, $company_id) {
            $additional_condition = null;
            if ($company_id) {
                $additional_condition = fn_get_company_condition('?:categories.company_id', true, $company_id);
            }

            return fn_category_exists($category_id, $additional_condition);
        },

    ), // category (tree)
    'p' => array(
        'tree' => true,
        'path_function' => function ($object_id, $company_id = 0) {
            $path = db_get_hash_single_array(
                "SELECT c.id_path, p.link_type FROM ?:categories as c LEFT JOIN ?:products_categories as p ON p.category_id = c.category_id WHERE p.product_id = ?i ?p",
                array('link_type', 'id_path'),
                $object_id,
                fn_get_seo_company_condition('c.company_id', '', $company_id)
            );

            return !empty($path['M']) ? $path['M'] : $path['A'];
        },
        'parent_type' => 'c',

        'name' => 'product',
        'picker' => 'pickers/products/picker.tpl',
        'picker_params' => array(
            'type' => 'single',
            'view_mode' => 'button'
        ),

        'table' => '?:product_descriptions',
        'description' => 'product',
        'dispatch' => 'products.view',
        'item' => 'product_id',
        'condition' => '',
        'not_shared' => true,

        'tree_options' => array('product_category_nohtml', 'product_category'),
        'html_options' => array('product_category', 'product_file'),
        'option' => 'seo_product_type',

        'exist_function' => function ($product_id, $company_id) {
            $result = fn_product_exists($product_id);

            // Check whether product is shared for given company
            if ($result && fn_allowed_for('ULTIMATE')) {
                $result = $result && in_array($company_id, fn_ult_get_shared_product_companies($product_id));
            }

            return $result;
        },

    ), // product  (tree)
    'a' => array(
        'tree' => true,
        'path_function' => function ($object_id) {
            $path = db_get_field("SELECT id_path FROM ?:pages WHERE page_id = ?i", $object_id);
            $apath = explode('/', $path);
            array_pop($apath);

            return implode('/', $apath);
        },
        'parent_type' => 'a',

        'name' => 'page',
        'picker' => 'pickers/pages/picker.tpl',
        'picker_params' => array(
            'multiple' => false,
            'use_keys' => 'N',
        ),

        'table' => '?:page_descriptions',
        'description' => 'page',
        'dispatch' => 'pages.view',
        'item' => 'page_id',
        'condition' => '',

        'tree_options' => array('page', 'page_nohtml'),
        'html_options' => array('file', 'page'),
        'pager' => true,
        'option' => 'seo_page_type',

        'exist_function' => function($page_id) {
            return fn_page_exists($page_id);
        },

    ), // page     (tree)
    'e' => array(
        'table' => '?:product_feature_variant_descriptions',
        'description' => 'variant',
        'dispatch' => 'product_features.view',
        'item' => 'variant_id',
        'condition' => '',

        'name' => 'feature',

        'html_options' => array('file'),
        'option' => 'seo_other_type',

        'exist_function' => function($variant_id) {
            return db_get_field('SELECT 1 FROM ?:product_feature_variants WHERE variant_id = ?i', $variant_id);
        },
    ), // feature  (plain)
    's' => array(
        'table' => '?:seo_names',
        'description' => 'name',
        'dispatch' => '',
        'item' => 'object_id',
        'condition' => fn_get_seo_company_condition('?:seo_names.company_id'),
        'not_shared' => true,

        'name' => 'custom',

        'html_options' => array('file'),
        'option' => 'seo_other_type',
    ), // custom    (plain)
);

if (fn_allowed_for('MULTIVENDOR')) {
    $schema['m'] = array(
        'table' => '?:companies',
        'description' => 'company',
        'dispatch' => 'companies.products',
        'item' => 'company_id',
        'condition' => '',
        'skip_lang_condition' => true,

        'name' => 'company',
        'html_options' => array('file'),
        'option' => 'seo_other_type',

        'exist_function' => function($company_id) {
            return db_get_field('SELECT 1 FROM ?:companies WHERE company_id = ?i', $company_id);
        },
    );
}

return $schema;
