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
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * @var string $mode
 */

Tygh::$app['session']['cart'] = isset(Tygh::$app['session']['cart']) ? Tygh::$app['session']['cart'] : array();
$cart = & Tygh::$app['session']['cart'];
if (empty($cart)) {
    fn_clear_cart($cart, true);
}

Tygh::$app['session']['customer_auth'] = isset(Tygh::$app['session']['customer_auth']) ? Tygh::$app['session']['customer_auth'] : array();
$customer_auth = & Tygh::$app['session']['customer_auth'];
if (empty($customer_auth)) {
    $customer_auth = fn_fill_auth(array(), array(), false, 'C');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    switch ($mode) {
        case 'update':
            $data = $_REQUEST['template_data'];

            if (isset($data['product_ids'])) {
                $data['product_ids'] = explode(',', $data['product_ids']);
            } else {
                $data['product_ids'] = array();
            }

            if ($template_id = fn_update_ebay_template($data, $_REQUEST['template_id'])) {
                return array(CONTROLLER_STATUS_OK, 'ebay.update?template_id=' . $template_id);
            } else {
                fn_save_post_data('template_data');
                fn_delete_notification('changes_saved');
            }

            return array(CONTROLLER_STATUS_OK, 'ebay.add');

            break;
        case 'm_delete':
            foreach ($_REQUEST['template_ids'] as $template_id) {
                fn_delete_ebay_template($template_id);
            }

            break;
        case 'export':
            return \Ebay\Controller::actionExportProducts();

            break;
        case 'export_template':
            return \Ebay\Controller::actionExportTemplate();

            break;
        case 'end_products':
            return \Ebay\Controller::actionEndProducts();

            break;
        case 'end_template':
            return \Ebay\Controller::actionEndTemplate();

            break;
        case 'update_template_product_status':
            return \Ebay\Controller::actionUpdateTemplateProductStatus();

            break;
        case 'update_product_status':
            return \Ebay\Controller::actionUpdateProductStatus();

            break;
        case 'delete_template':
            if (!empty($_REQUEST['template_id'])) {
                fn_delete_ebay_template($_REQUEST['template_id']);
            }

            break;
        case 'synchronization':
            return \Ebay\Controller::actionSynchronizationObjects();

            break;

        case 'clean_product_logs':
            \Ebay\ProductLogger::clean();

            return array(CONTROLLER_STATUS_OK, 'ebay.product_logs');
            break;
    }


    return array(CONTROLLER_STATUS_OK, 'ebay.manage');
}

if ($mode == 'manage') {
    $params = $_REQUEST;

    list($templates, $search) = fn_get_ebay_templates($params, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);

    \Ebay\Template::loadProductCount($templates);

    Tygh::$app['view']->assign('templates', $templates);
    Tygh::$app['view']->assign('search', $search);

} elseif ($mode == 'add' || $mode == 'update') {
    $template_data = array();

    if ($mode == 'add') {
        $template_data = fn_restore_post_data('template_data');
    } elseif (!empty($_REQUEST['template_id'])) {
        $template_data = fn_get_ebay_template($_REQUEST['template_id']);

        if (empty($template_data)) {
            return array(CONTROLLER_STATUS_REDIRECT, 'ebay.manage');
        }

        $template_data['product_ids'] = \Ebay\Product::getTemplateProductIds($_REQUEST['template_id']);
    }

    if (!isset($template_data['site_id'])) {
        $template_data['site_id'] = Registry::get('addons.ebay.site_id');
    }

    if (isset($_REQUEST['site_id']) && $template_data['site_id'] != $_REQUEST['site_id']) {
        $template_data['site_id'] = $_REQUEST['site_id'];

        if (!defined('AJAX_REQUEST')) {
            fn_set_notification('N', __('information'), __('ebay_changed_region_message'), 'K');
        }
    }

    if (isset($_REQUEST['category_id'])) {
        $template_data['category'] = (int) $_REQUEST['category_id'];
    }

    if (isset($_REQUEST['shipping_type'])) {
        $template_data['shipping_type'] = $_REQUEST['shipping_type'];
    }

    // [Page sections]
    Registry::set('navigation.tabs', array (
        'detailed' => array (
            'title' => __('general'),
            'js' => true
        ),
        'shippings' => array (
            'title' => __('shippings'),
            'js' => true
        ),
        'payments' => array (
            'title' => __('payments'),
            'js' => true
        ),
        'returnPolicy' => array (
            'title' => __('return_policy'),
            'js' => true
        ),
        'productIdentifier' => array (
            'title' => __('ebay_product_identifiers'),
            'js' => true
        )
    ));

    if (empty($template_data['shipping_type'])) {
        $template_data['shipping_type'] = 'C';
    }
    if (!isset($template_data['category'])) {
        $template_data['category'] = null;
    }

    /** @var Smarty $view */
    $view = Tygh::$app['view'];

    $objects = fn_ebay_get_objects_needed_synchronization(
        $template_data['site_id'],
        $template_data['category']
    );

    $view->assign('need_synchronisation', !empty($objects));

    $view->assign(
        'ebay_domestic_shipping_services',
        fn_get_ebay_shippings($template_data['site_id'], $template_data['shipping_type'] == 'C' ? 'Calculated' : 'Flat', false)
    );
    $view->assign(
        'ebay_international_shipping_services',
        fn_get_ebay_shippings($template_data['site_id'], $template_data['shipping_type'] == 'C' ? 'Calculated' : 'Flat', true)
    );

    if (!empty($template_data['root_category'])) {
        $view->assign('ebay_child_categories', fn_get_ebay_categories(
            $template_data['site_id'],
            $template_data['root_category'],
            true
        ));
    }

    if (!empty($template_data['root_sec_category'])) {
        $view->assign('ebay_sec_child_categories', fn_get_ebay_categories(
            $template_data['site_id'],
            $template_data['root_sec_category'],
            true
        ));
    }

    if (!empty($template_data['category'])) {
        $view->assign(
            'current_category',
            \Ebay\objects\Category::getCategory($template_data['site_id'], $template_data['category'])
        );
    }

    $view
        ->assign('template_data', $template_data)
        ->assign('ebay_sites', fn_get_ebay_sites())
        ->assign('ebay_root_categories', fn_get_ebay_categories($template_data['site_id'], 0))
        ->assign('ebay_product_identifier_types', \Ebay\Template::getProductIdentifierCodeNames())
        ->assign('ebay_variation_identifier_types', \Ebay\Template::getVariationIdentifierCodeNames());

} elseif ($mode == 'get_category_features') {
    $site_id = (int) $_REQUEST['site_id'];
    $category_id = (int) $_REQUEST['category_id'];
    $template_data = $features = array();

    if (\Ebay\objects\CategoryFeature::isNeedSynchronization($site_id, $category_id)) {
        \Ebay\objects\CategoryFeature::synchronization($site_id, $category_id);
    }

    $current_category = \Ebay\objects\Category::getCategory($site_id, $category_id);

    if (!empty($_REQUEST['template_id'])) {
        $template_data = fn_get_ebay_template($_REQUEST['template_id']);
    }

    Tygh::$app['view']->assign('template_data', $template_data);
    Tygh::$app['view']->assign('current_category', $current_category);
    Tygh::$app['view']->assign('data_id', $_REQUEST['data_id']);

    if (defined('AJAX_REQUEST')) {
        Tygh::$app['view']->display('addons/ebay/views/ebay/components/category_features.tpl');
        exit;
    } else {
        array(CONTROLLER_STATUS_REDIRECT, 'ebay.manage');
    }
} elseif ($mode == 'get_subcategories') {

    $template_data = $subcategories = array();
    if (!empty($_REQUEST['parent_id'])) {
        $subcategories = fn_get_ebay_categories($_REQUEST['site_id'], $_REQUEST['parent_id'], true);
    }
    if (!empty($_REQUEST['template_id'])) {
        $template_data = fn_get_ebay_template($_REQUEST['template_id']);
    }
    $template_data['site_id'] = $_REQUEST['site_id'];

    Tygh::$app['view']->assign('ebay_categories', $subcategories);
    Tygh::$app['view']->assign('template_data', $template_data);
    Tygh::$app['view']->assign('data_id', $_REQUEST['data_id']);
    Tygh::$app['view']->assign('required_field', $_REQUEST['required_field']);

    if (defined('AJAX_REQUEST')) {
        Tygh::$app['view']->display('addons/ebay/views/ebay/components/ebay_categories.tpl');
        exit;
    }

} elseif ($mode == 'get_orders') {
    fn_define('ORDER_MANAGEMENT', true);
    if (fn_allowed_for('ULTIMATE')) {
        if (Registry::get('runtime.company_id')) {
            list($success_orders, $failed_orders) = fn_get_ebay_orders($cart, $customer_auth);

            if (!empty($success_orders)) {
                fn_set_notification('N', __('successful'), __('ebay_success_orders_notice', array('[SUCCESS_IDS]' => implode(', ', $success_orders))));
            } elseif (!empty($failed_orders)) {
                fn_set_notification('W', __('warning'), __('ebay_failed_orders_notice', array('[FAILED_EBAY_IDS]' => implode(', ', $failed_orders))));
            } else {
                fn_set_notification('W', __('warning'), 'no orders found');
            }
        } else {
            fn_set_notification('W', __('warning'), __('store_object_denied', array(
                '[object_type]' => '',
                '[object_name]' => ''
            )), '', 'store_object_denied');
        }
    } else {
        list($success_orders, $failed_orders) = fn_get_ebay_orders($cart, $customer_auth);

        if (!empty($success_orders)) {
            fn_set_notification('N', __('successful'), __('ebay_success_orders_notice', array('[SUCCESS_IDS]' => implode(', ', $success_orders))));
        } elseif (!empty($failed_orders)) {
            fn_set_notification('W', __('warning'), __('ebay_failed_orders_notice', array('[FAILED_EBAY_IDS]' => implode(', ', $failed_orders))));
        } else {
            fn_set_notification('W', __('warning'), 'no orders found');
        }
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'orders.manage');
} elseif ($mode == 'product_logs') {
    $params = $_REQUEST;

    if (isset($params['product_ids'])) {
        $params['product_ids'] = array_filter(explode(',', $params['product_ids']));
    }

    // Init filter
    $params = \Tygh\Navigation\LastView::instance()->update('ebay_product_logs', $params);

    list($logs, $search) = \Ebay\ProductLogger::getList(
        $params,
        Registry::get('settings.Appearance.admin_elements_per_page')
    );

    $types = \Ebay\ProductLogger::getTypes();
    $actions = \Ebay\ProductLogger::getActions();
    $templates = fn_get_ebay_templates(array(), 0, DESCR_SL, true);

    Tygh::$app['view']->assign('logs', $logs);
    Tygh::$app['view']->assign('ebay_types', $types);
    Tygh::$app['view']->assign('ebay_actions', $actions);
    Tygh::$app['view']->assign('ebay_templates', $templates);
    Tygh::$app['view']->assign('search', $search);
} elseif ($mode == 'categories_picker') {
    $company_id = !empty($_REQUEST['company_id']) ? $_REQUEST['company_id'] : null;
    $used_site_ids = \Ebay\Template::getUsedSiteIds($company_id);

    if (isset($_REQUEST['site_id']) && in_array($_REQUEST['site_id'], $used_site_ids)) {
        $current_site_id = $_REQUEST['site_id'];
    } else {
        $current_site_id = reset($used_site_ids);
    }

    $category_id = empty($_REQUEST['category_id']) ? 0 : $_REQUEST['category_id'];
    $category_count = db_get_field("SELECT COUNT(*) FROM ?:ebay_categories WHERE site_id = ?i", $current_site_id);

    $except_id = 0;
    if (!empty($_REQUEST['except_id'])) {
        $except_id = $_REQUEST['except_id'];
        Tygh::$app['view']->assign('except_id', $_REQUEST['except_id']);
    }
    if ($category_count < CATEGORY_THRESHOLD) {
        $params = array (
            'simple' => false,
            'b_id' => !empty($_REQUEST['b_id']) ? $_REQUEST['b_id'] : '',
            'except_id' => $except_id
        );
        list($categories_tree, ) = fn_ebay_get_categories($params, $current_site_id, DESCR_SL);
        Tygh::$app['view']->assign('show_all', true);
    } else {
        $params = array (
            'category_id' => $category_id,
            'current_category_id' => $category_id,
            'b_id' => !empty($_REQUEST['b_id']) ? $_REQUEST['b_id'] : '',
            'except_id' => $except_id
        );
        list($categories_tree, ) = fn_ebay_get_categories($params, $current_site_id, DESCR_SL);
    }

    Tygh::$app['view']->assign('categories_tree', $categories_tree);
    if ($category_count < CATEGORY_SHOW_ALL) {
        Tygh::$app['view']->assign('expand_all', true);
    }
    if (defined('AJAX_REQUEST')) {
        if (!empty($_REQUEST['random'])) {
            Tygh::$app['view']->assign('random', $_REQUEST['random']);
        }
        Tygh::$app['view']->assign('category_id', $category_id);
    }

    Tygh::$app['view']->assign('ebay_sites', fn_get_ebay_sites($used_site_ids));
    Tygh::$app['view']->assign('current_site_id', $current_site_id);
    Tygh::$app['view']->assign('company_id', $company_id);
    Tygh::$app['view']->display('addons/ebay/pickers/categories/picker_contents.tpl');
    exit;
}
