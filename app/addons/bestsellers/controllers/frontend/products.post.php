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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'final_sale' || $mode == 'on_sale' || $mode == 'bestsellers' || $mode == 'newest') {

    $params = $_REQUEST;

    $params['extend'] = array('description');

    if ($mode == 'final_sale') {
        $title = __("final_sale");
        $params['on_sale'] = true;

        if (empty($params['on_sale_from'])) {
            $params['on_sale_from'] = Registry::get('addons.bestsellers.final_sale_from');
        }

    } elseif ($mode == 'on_sale') {
        $title = __("on_sale");
        $params['on_sale'] = true;

    } elseif ($mode == 'bestsellers') {
        $title = __("bestsellers");
        
        $params['bestsellers'] = true;
        $params['sales_amount_from'] = Registry::get('addons.bestsellers.sales_amount_from');

    } elseif ($mode == 'newest') {
        $title = __("newest");

        $params['sort_by'] = empty($params['sort_by']) ? 'timestamp' : $params['sort_by'];
        $params['plain'] = true;
        $params['visible'] = true;

        $period = Registry::get('addons.bestsellers.period');
        $params['period'] = 'A';
        if ($period == 'today') {
            $params['period'] = 'D';

        } elseif ($period == 'last_days') {
            $params['period'] = 'HC';
            $params['last_days'] = Registry::get('addons.bestsellers.last_days');
        }

    } else {
        $title = __('products');
    }

    fn_add_breadcrumb($title);

    list($products, $search) = fn_get_products($params, Registry::get('settings.Appearance.products_per_page'));

    fn_gather_additional_products_data($products, array('get_icon' => true, 'get_detailed' => true, 'get_additional' => true, 'get_options'=> true));

    $selected_layout = fn_get_products_layout($params);

    Tygh::$app['view']->assign('products', $products);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('title', $title);
    Tygh::$app['view']->assign('selected_layout', $selected_layout);
}
