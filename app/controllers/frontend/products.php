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
use Tygh\BlockManager\ProductTabs;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

//
// Search products
//
if ($mode == 'search') {

    $params = $_REQUEST;
    fn_add_breadcrumb(__('search_results'));

    if (!empty($params['search_performed']) || !empty($params['features_hash'])) {

        $params = $_REQUEST;
        $params['extend'] = array('description');

        if ($items_per_page = fn_change_session_param(Tygh::$app['session']['search_params'], $_REQUEST, 'items_per_page')) {
            $params['items_per_page'] = $items_per_page;
        }
        if ($sort_by = fn_change_session_param(Tygh::$app['session']['search_params'], $_REQUEST, 'sort_by')) {
            $params['sort_by'] = $sort_by;
        }
        if ($sort_order = fn_change_session_param(Tygh::$app['session']['search_params'], $_REQUEST, 'sort_order')) {
            $params['sort_order'] = $sort_order;
        }

        list($products, $search) = fn_get_products($params, Registry::get('settings.Appearance.products_per_page'));

        if (defined('AJAX_REQUEST') && (!empty($params['features_hash']) && !$products)) {
            fn_filters_not_found_notification();
            exit;
        }

        fn_gather_additional_products_data($products, array(
            'get_icon' => true,
            'get_detailed' => true,
            'get_additional' => true,
            'get_options'=> true
        ));

        if (!empty($products)) {
            Tygh::$app['session']['continue_url'] = Registry::get('config.current_url');
        }

        $selected_layout = fn_get_products_layout($params);

        Tygh::$app['view']->assign('products', $products);
        Tygh::$app['view']->assign('search', $search);
        Tygh::$app['view']->assign('selected_layout', $selected_layout);
    }

//
// View product details
//
} elseif ($mode == 'view' || $mode == 'quick_view') {

    $_REQUEST['product_id'] = empty($_REQUEST['product_id']) ? 0 : $_REQUEST['product_id'];

    if (!empty($_REQUEST['product_id']) && empty($auth['user_id'])) {

        $uids = explode(',', db_get_field("SElECT usergroup_ids FROM ?:products WHERE product_id = ?i", $_REQUEST['product_id']));

        if (!in_array(USERGROUP_ALL, $uids) && !in_array(USERGROUP_GUEST, $uids)) {
            return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url')));
        }
    }

    $product = fn_get_product_data(
        $_REQUEST['product_id'],
        $auth,
        CART_LANGUAGE,
        '',
        true,
        true,
        true,
        true,
        fn_is_preview_action($auth, $_REQUEST),
        true,
        false,
        true
    );

    if (empty($product)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    if ((empty(Tygh::$app['session']['current_category_id']) || empty($product['category_ids'][Tygh::$app['session']['current_category_id']])) && !empty($product['main_category'])) {
        if (!empty(Tygh::$app['session']['breadcrumb_category_id']) && in_array(Tygh::$app['session']['breadcrumb_category_id'], $product['category_ids'])) {
            Tygh::$app['session']['current_category_id'] = Tygh::$app['session']['breadcrumb_category_id'];
        } else {
            Tygh::$app['session']['current_category_id'] = $product['main_category'];
        }
    }

    if (!empty($product['meta_description']) || !empty($product['meta_keywords'])) {
        Tygh::$app['view']->assign('meta_description', $product['meta_description']);
        Tygh::$app['view']->assign('meta_keywords', $product['meta_keywords']);

    } else {
        $meta_tags = db_get_row(
            "SELECT meta_description, meta_keywords"
            . " FROM ?:category_descriptions"
            . " WHERE category_id = ?i AND lang_code = ?s",
            Tygh::$app['session']['current_category_id'],
            CART_LANGUAGE
        );
        if (!empty($meta_tags)) {
            Tygh::$app['view']->assign('meta_description', $meta_tags['meta_description']);
            Tygh::$app['view']->assign('meta_keywords', $meta_tags['meta_keywords']);
        }
    }
    if (!empty(Tygh::$app['session']['current_category_id'])) {
        Tygh::$app['session']['continue_url'] = "categories.view?category_id=".Tygh::$app['session']['current_category_id'];

        $parent_ids = fn_explode(
            '/',
            db_get_field(
                "SELECT id_path FROM ?:categories WHERE category_id = ?i",
                Tygh::$app['session']['current_category_id']
            )
        );

        if (!empty($parent_ids)) {
            Registry::set('runtime.active_category_ids', $parent_ids);
            $cats = fn_get_category_name($parent_ids);
            foreach ($parent_ids as $c_id) {
                fn_add_breadcrumb($cats[$c_id], "categories.view?category_id=$c_id");
            }
        }
    }
    fn_add_breadcrumb($product['product']);

    if (!empty($_REQUEST['combination'])) {
        $product['combination'] = $_REQUEST['combination'];
    }

    fn_gather_additional_product_data($product, true, true);
    Tygh::$app['view']->assign('product', $product);

    // If page title for this product is exist than assign it to template
    if (!empty($product['page_title'])) {
        Tygh::$app['view']->assign('page_title', $product['page_title']);
    }

    $params = array (
        'product_id' => $_REQUEST['product_id'],
        'preview_check' => true
    );
    list($files) = fn_get_product_files($params);

    if (!empty($files)) {
        Tygh::$app['view']->assign('files', $files);
    }

    /* [Product tabs] */
    $tabs = ProductTabs::instance()->getList(
        '',
        $product['product_id'],
        DESCR_SL
    );
    foreach ($tabs as $tab_id => $tab) {
        if ($tab['status'] == 'D') {
            continue;
        }
        if (!empty($tab['template'])) {
            $tabs[$tab_id]['html_id'] = fn_basename($tab['template'], ".tpl");
        } else {
            $tabs[$tab_id]['html_id'] = 'product_tab_' . $tab_id;
        }

        if ($tab['show_in_popup'] != "Y") {
            Registry::set('navigation.tabs.' . $tabs[$tab_id]['html_id'], array (
                'title' => $tab['name'],
                'js' => true
            ));
        }
    }
    Tygh::$app['view']->assign('tabs', $tabs);
    /* [/Product tabs] */

    // Set recently viewed products history
    fn_add_product_to_recently_viewed($_REQUEST['product_id']);

    // Increase product popularity
    fn_set_product_popularity($_REQUEST['product_id']);

    $product_notification_enabled = (isset(Tygh::$app['session']['product_notifications']) ? (isset(Tygh::$app['session']['product_notifications']['product_ids']) && in_array($_REQUEST['product_id'], Tygh::$app['session']['product_notifications']['product_ids']) ? 'Y' : 'N') : 'N');
    if ($product_notification_enabled) {
        if ((Tygh::$app['session']['auth']['user_id'] == 0) && !empty(Tygh::$app['session']['product_notifications']['email'])) {
            if (!db_get_field("SELECT subscription_id FROM ?:product_subscriptions WHERE product_id = ?i AND email = ?s", $_REQUEST['product_id'], Tygh::$app['session']['product_notifications']['email'])) {
                $product_notification_enabled = 'N';
            }
        } elseif (!db_get_field("SELECT subscription_id FROM ?:product_subscriptions WHERE product_id = ?i AND user_id = ?i", $_REQUEST['product_id'], Tygh::$app['session']['auth']['user_id'])) {
            $product_notification_enabled = 'N';
        }
    }

    Tygh::$app['view']->assign('show_qty', true);
    Tygh::$app['view']->assign('product_notification_enabled', $product_notification_enabled);
    Tygh::$app['view']->assign('product_notification_email', (isset(Tygh::$app['session']['product_notifications']) ? Tygh::$app['session']['product_notifications']['email'] : ''));

    if ($mode == 'quick_view') {
        if (defined('AJAX_REQUEST')) {
            fn_prepare_product_quick_view($_REQUEST);
            Registry::set('runtime.root_template', 'views/products/quick_view.tpl');
        } else {
            return array(CONTROLLER_STATUS_REDIRECT, 'products.view?product_id=' . $_REQUEST['product_id']);
        }
    }

} elseif ($mode == 'options') {

    if (!defined('AJAX_REQUEST') && !empty($_REQUEST['product_data'])) {
        list($product_id, $_data) = each($_REQUEST['product_data']);
        $product_id = isset($_data['product_id']) ? $_data['product_id'] : $product_id;

        return array(CONTROLLER_STATUS_REDIRECT, 'products.view?product_id=' . $product_id);
    }
} elseif ($mode == 'product_notifications') {
    fn_update_product_notifications(array(
        'product_id' => $_REQUEST['product_id'],
        'user_id' => Tygh::$app['session']['auth']['user_id'],
        'email' => (!empty(Tygh::$app['session']['cart']['user_data']['email']) ? Tygh::$app['session']['cart']['user_data']['email'] : (!empty($_REQUEST['email']) ? $_REQUEST['email'] : '')),
        'enable' => $_REQUEST['enable']
    ));
    exit;
}

function fn_add_product_to_recently_viewed($product_id, $max_list_size = MAX_RECENTLY_VIEWED)
{
    $added = false;

    if (!empty(Tygh::$app['session']['recently_viewed_products'])) {
        $is_exist = array_search($product_id, Tygh::$app['session']['recently_viewed_products']);
        // Existing product will be moved on the top of the list
        if ($is_exist !== false) {
            // Remove the existing product to put it on the top later
            unset(Tygh::$app['session']['recently_viewed_products'][$is_exist]);
            // Re-sort the array
            Tygh::$app['session']['recently_viewed_products'] = array_values(Tygh::$app['session']['recently_viewed_products']);
        }

        array_unshift(Tygh::$app['session']['recently_viewed_products'], $product_id);
        $added = true;
    } else {
        Tygh::$app['session']['recently_viewed_products'] = array($product_id);
    }

    if (count(Tygh::$app['session']['recently_viewed_products']) > $max_list_size) {
        array_pop(Tygh::$app['session']['recently_viewed_products']);
    }

    return $added;
}

function fn_set_product_popularity($product_id, $popularity_view = POPULARITY_VIEW)
{
    if (empty(Tygh::$app['session']['products_popularity']['viewed'][$product_id])) {
        $_data = array (
            'product_id' => $product_id,
            'viewed' => 1,
            'total' => $popularity_view
        );

        db_query("INSERT INTO ?:product_popularity ?e ON DUPLICATE KEY UPDATE viewed = viewed + 1, total = total + ?i", $_data, $popularity_view);

        Tygh::$app['session']['products_popularity']['viewed'][$product_id] = true;

        return true;
    }

    return false;
}

function fn_update_product_notifications($data)
{
    if (!empty($data['email']) && fn_validate_email($data['email'])) {
        Tygh::$app['session']['product_notifications']['email'] = $data['email'];
        if ($data['enable'] == 'Y') {
            db_query("REPLACE INTO ?:product_subscriptions ?e", $data);
            if (!isset(Tygh::$app['session']['product_notifications']['product_ids']) || (is_array(Tygh::$app['session']['product_notifications']['product_ids']) && !in_array($data['product_id'], Tygh::$app['session']['product_notifications']['product_ids']))) {
                Tygh::$app['session']['product_notifications']['product_ids'][] = $data['product_id'];
            }

            fn_set_notification('N', __('notice'), __('product_notification_subscribed'));
        } else {
            $deleted = db_query("DELETE FROM ?:product_subscriptions WHERE product_id = ?i AND user_id = ?i AND email = ?s", $data['product_id'], $data['user_id'], $data['email']);

            if (isset(Tygh::$app['session']['product_notifications']) && isset(Tygh::$app['session']['product_notifications']['product_ids']) && in_array($data['product_id'], Tygh::$app['session']['product_notifications']['product_ids'])) {
                Tygh::$app['session']['product_notifications']['product_ids'] = array_diff(Tygh::$app['session']['product_notifications']['product_ids'], array($data['product_id']));
            }

            if (!empty($deleted)) {
                fn_set_notification('N', __('notice'), __('product_notification_unsubscribed'));
            }
        }
    }
}
