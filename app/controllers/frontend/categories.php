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

$_REQUEST['category_id'] = empty($_REQUEST['category_id']) ? 0 : $_REQUEST['category_id'];

if ($mode == 'catalog') {
    fn_add_breadcrumb(__('catalog'));

    $root_categories = fn_get_subcategories(0);

    foreach ($root_categories as $k => $v) {
        $root_categories[$k]['main_pair'] = fn_get_image_pairs($v['category_id'], 'category', 'M');
    }

    Tygh::$app['view']->assign('root_categories', $root_categories);

} elseif ($mode == 'view') {

    $_statuses = array('A', 'H');
    $_condition = fn_get_localizations_condition('localization', true);
    $preview = fn_is_preview_action($auth, $_REQUEST);

    if (!$preview) {
        $_condition .= ' AND (' . fn_find_array_in_set($auth['usergroup_ids'], 'usergroup_ids', true) . ')';
        $_condition .= db_quote(' AND status IN (?a)', $_statuses);
    }

    if (fn_allowed_for('ULTIMATE')) {
        $_condition .= fn_get_company_condition('?:categories.company_id');
    }

    $category_exists = db_get_field(
        "SELECT category_id FROM ?:categories WHERE category_id = ?i ?p",
        $_REQUEST['category_id'],
        $_condition
    );

    if (!empty($category_exists)) {

        // Save current url to session for 'Continue shopping' button
        Tygh::$app['session']['continue_url'] = "categories.view?category_id=$_REQUEST[category_id]";

        // Save current category id to session
        Tygh::$app['session']['current_category_id'] = Tygh::$app['session']['breadcrumb_category_id'] = $_REQUEST['category_id'];

        // Get subcategories list for current category
        Tygh::$app['view']->assign('subcategories', fn_get_subcategories($_REQUEST['category_id']));

        // Get full data for current category
        $category_data = fn_get_category_data($_REQUEST['category_id'], CART_LANGUAGE, '*', true, false, $preview);

        $category_parent_ids = fn_explode('/', $category_data['id_path']);
        array_pop($category_parent_ids);

        if (!empty($category_data['meta_description']) || !empty($category_data['meta_keywords'])) {
            Tygh::$app['view']->assign('meta_description', $category_data['meta_description']);
            Tygh::$app['view']->assign('meta_keywords', $category_data['meta_keywords']);
        }

        $params = $_REQUEST;

        if ($items_per_page = fn_change_session_param(Tygh::$app['session'], $_REQUEST, 'items_per_page')) {
            $params['items_per_page'] = $items_per_page;
        }
        if ($sort_by = fn_change_session_param(Tygh::$app['session'], $_REQUEST, 'sort_by')) {
            $params['sort_by'] = $sort_by;
        }
        if ($sort_order = fn_change_session_param(Tygh::$app['session'], $_REQUEST, 'sort_order')) {
            $params['sort_order'] = $sort_order;
        }

        $params['cid'] = $_REQUEST['category_id'];
        $params['extend'] = array('categories', 'description');
        $params['subcats'] = '';
        if (Registry::get('settings.General.show_products_from_subcategories') == 'Y') {
            $params['subcats'] = 'Y';
        }

        list($products, $search) = fn_get_products($params, Registry::get('settings.Appearance.products_per_page'), CART_LANGUAGE);

        if (isset($search['page']) && ($search['page'] > 1) && empty($products)) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        fn_gather_additional_products_data($products, array(
            'get_icon' => true,
            'get_detailed' => true,
            'get_additional' => true,
            'get_options' => true,
            'get_discounts' => true,
            'get_features' => false
        ));

        $show_no_products_block = (!empty($params['features_hash']) && !$products);
        if ($show_no_products_block && defined('AJAX_REQUEST')) {
            fn_filters_not_found_notification();
            exit;
        }

        Tygh::$app['view']->assign('show_no_products_block', $show_no_products_block);

        $selected_layout = fn_get_products_layout($_REQUEST);
        Tygh::$app['view']->assign('show_qty', true);
        Tygh::$app['view']->assign('products', $products);
        Tygh::$app['view']->assign('search', $search);
        Tygh::$app['view']->assign('selected_layout', $selected_layout);

        Tygh::$app['view']->assign('category_data', $category_data);

        // If page title for this category is exist than assign it to template
        if (!empty($category_data['page_title'])) {
             Tygh::$app['view']->assign('page_title', $category_data['page_title']);
        }

        // [Breadcrumbs]
        if (!empty($category_parent_ids)) {
            Registry::set('runtime.active_category_ids', $category_parent_ids);
            $cats = fn_get_category_name($category_parent_ids);
            foreach ($category_parent_ids as $c_id) {
                fn_add_breadcrumb($cats[$c_id], "categories.view?category_id=$c_id");
            }
        }

        fn_add_breadcrumb($category_data['category'], (empty($_REQUEST['features_hash'])) ? '' : "categories.view?category_id=$_REQUEST[category_id]");
        // [/Breadcrumbs]

    } else {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

} elseif ($mode == 'picker') {

    $category_count = db_get_field("SELECT COUNT(*) FROM ?:categories");
    if ($category_count < CATEGORY_THRESHOLD) {
        $params = array (
            'simple' => false
        );
         list($categories_tree, ) = fn_get_categories($params);
         Tygh::$app['view']->assign('show_all', true);
    } else {
        $params = array (
            'category_id' => $_REQUEST['category_id'],
            'current_category_id' => $_REQUEST['category_id'],
            'visible' => true,
            'simple' => false
        );
        list($categories_tree, ) = fn_get_categories($params);
    }

    if (!empty($_REQUEST['root'])) {
        array_unshift($categories_tree, array('category_id' => 0, 'category' => $_REQUEST['root']));
    }
    Tygh::$app['view']->assign('categories_tree', $categories_tree);
    if ($category_count < CATEGORY_SHOW_ALL) {
        Tygh::$app['view']->assign('expand_all', true);
    }
    if (defined('AJAX_REQUEST')) {
        Tygh::$app['view']->assign('category_id', $_REQUEST['category_id']);
    }
    Tygh::$app['view']->display('pickers/categories/picker_contents.tpl');
    exit;
}
