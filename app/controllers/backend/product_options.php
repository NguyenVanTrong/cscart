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

fn_define('KEEP_UPLOADED_FILES', true);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $suffix = '';
    if (!fn_allowed_for('ULTIMATE:FREE')) {
        if ($mode == 'add_exceptions') {
            foreach ($_REQUEST['add_options_combination'] as $k => $v) {
                $_data = array(
                    'product_id' => $_REQUEST['product_id'],
                    'combination' => $v,
                );

                fn_update_exception($_data);
            }

            fn_recalculate_exceptions($_REQUEST['product_id']);

            $suffix = ".exceptions?product_id=$_REQUEST[product_id]";
        }

        if ($mode == 'm_delete_exceptions') {
            foreach ($_REQUEST['exception_ids'] as $id) {
                fn_delete_exception($id);
            }

            $suffix = ".exceptions?product_id=$_REQUEST[product_id]";
        }
    }

    if ($mode == 'add_combinations') {
        if (is_array($_REQUEST['add_inventory'])) {
            foreach ($_REQUEST['add_inventory'] as $k => $v) {
                $_data = array(
                    'product_id' => $_REQUEST['product_id'],
                    'combination' => $_REQUEST['add_options_combination'][$k],
                    'amount' => isset($_REQUEST['add_inventory'][$k]['amount']) ? $_REQUEST['add_inventory'][$k]['amount'] : 0,
                );

                $_data = fn_array_merge($v, $_data);

                fn_update_option_combination($_data);
            }
        }

        $suffix = ".inventory?product_id=$_REQUEST[product_id]";
    }

    if ($mode == 'update_combinations') {
        if (!empty($_REQUEST['inventory'])) {
            foreach ($_REQUEST['inventory'] as $k => $v) {
                fn_update_option_combination($v, $k);
            }
        }

        $suffix = ".inventory?product_id=$_REQUEST[product_id]";
    }

    if ($mode == 'm_delete_combinations') {
        foreach ($_REQUEST['combination_hashes'] as $v) {
            fn_delete_option_combination($v);
        }

        $suffix = ".inventory?product_id=$_REQUEST[product_id]";
    }

    // Apply global options to the selected products
    if ($mode == 'apply') {
        if (!empty($_REQUEST['apply_options']['options'])) {

            $_data = $_REQUEST['apply_options'];

            foreach ($_data['options'] as $key => $value) {
                $products_ids = empty($_data['product_ids']) ? array() : explode(',', $_data['product_ids']);

                foreach ($products_ids as $k) {
                    $updated_products[$k] = db_get_row(
                        "SELECT a.product_id, a.company_id, b.product FROM ?:products as a"
                        . " LEFT JOIN ?:product_descriptions as b ON a.product_id = b.product_id"
                        . " AND lang_code = ?s"
                        . " WHERE a.product_id = ?i",
                        CART_LANGUAGE, $k
                    );

                    if ($_data['link'] == 'N') {
                        fn_clone_product_options(0, $k, $value);
                    } else {
                        db_query("REPLACE INTO ?:product_global_option_links (option_id, product_id) VALUES (?i, ?i)", $value, $k);

                        if (fn_allowed_for('ULTIMATE')) {
                            fn_ult_share_product_option($value, $k);
                        }
                    }
                }
            }

            if (!empty($updated_products)) {
                fn_set_notification('N', __('notice'), __('options_have_been_applied_to_products'));
            }
        }

        $suffix = ".apply";
    }

    if ($mode == 'update') {
        fn_trusted_vars('option_data', 'regexp');

        if (fn_allowed_for('MULTIVENDOR')) {
            $option_data = array();

            if (!empty($_REQUEST['option_id'])) {
                $condition = fn_get_company_condition('?:product_options.company_id');
                $option_data = db_get_row("SELECT * FROM ?:product_options WHERE option_id = ?i $condition", $_REQUEST['option_id']);
                if (empty($option_data)) {
                    fn_set_notification('W', __('warning'), __('access_denied'));

                    return array(CONTROLLER_STATUS_REDIRECT, 'product_options.manage');
                }
            }

            $_REQUEST['option_data'] = array_merge($option_data, $_REQUEST['option_data']);
            fn_set_company_id($_REQUEST['option_data']);
        }

        $option_id = fn_update_product_option($_REQUEST['option_data'], $_REQUEST['option_id'], DESCR_SL);

        if (!empty($_REQUEST['object']) && $_REQUEST['object'] == 'product') { // FIXME (when assigning page and current url will be removed from ajax)

            return array(CONTROLLER_STATUS_OK, $_SERVER['HTTP_REFERER'] . '&selected_section=options');
        }

        $suffix = ".manage";
    }

    if ($mode == 'delete') {
        if (!empty($_REQUEST['option_id']) && fn_check_company_id('product_options', 'option_id', $_REQUEST['option_id']) || (!empty($_REQUEST['product_id']) && fn_check_company_id('products', 'product_id', $_REQUEST['product_id']))) {

            $p_id = db_get_field("SELECT product_id FROM ?:product_options WHERE option_id = ?i", $_REQUEST['option_id']);

            if (!empty($_REQUEST['product_id']) && empty($p_id)) { // we're deleting global option from the product
                db_query("DELETE FROM ?:product_global_option_links WHERE product_id = ?i AND option_id = ?i", $_REQUEST['product_id'], $_REQUEST['option_id']);

            } else {
                fn_delete_product_option($_REQUEST['option_id']);
            }

            if (empty($_REQUEST['product_id']) && empty($p_id)) { // we're deleting global option itself
                db_query("DELETE FROM ?:product_global_option_links WHERE option_id = ?i", $_REQUEST['option_id']);
            }
        }
        if (!empty($_REQUEST['product_id'])) {
            $_options = fn_get_product_options($_REQUEST['product_id']);
            if (empty($_options)) {
                Tygh::$app['view']->display('views/product_options/manage.tpl');
            }
            exit();
        }

        $suffix = ".manage";
    }

    if ($mode == 'rebuild_combinations') {
        fn_rebuild_product_options_inventory($_REQUEST['product_id']);

        $suffix = ".inventory?product_id=$_REQUEST[product_id]";
    }

    if ($mode == 'delete_combination') {
        if (!empty($_REQUEST['combination_hash'])) {
            fn_delete_product_combination($_REQUEST['combination_hash']);
        }

        $suffix = ".inventory?product_id=$_REQUEST[product_id]";
    }

    if (!fn_allowed_for('ULTIMATE:FREE')) {
        if ($mode == 'delete_exception') {
            if (!empty($_REQUEST['exception_id'])) {
                db_query("DELETE FROM ?:product_options_exceptions WHERE exception_id = ?i", $_REQUEST['exception_id']);
            }

            $suffix = ".exceptions?product_id=$_REQUEST[product_id]";
        }
    }

    return array(CONTROLLER_STATUS_OK, 'product_options' . $suffix);
}

//
// Product options combination inventory tracking
//
if ($mode == 'inventory') {
    list($inventory, $search) = fn_get_product_options_inventory($_REQUEST, Registry::get('settings.Appearance.admin_elements_per_page'));

    $product_options = fn_get_product_options($_REQUEST['product_id'], DESCR_SL, true, true);
    $product_inventory = db_get_field("SELECT tracking FROM ?:products WHERE product_id = ?i", $_REQUEST['product_id']);

    Tygh::$app['view']->assign('product_inventory', $product_inventory);
    Tygh::$app['view']->assign('product_options', $product_options);
    Tygh::$app['view']->assign('inventory', $inventory);
    Tygh::$app['view']->assign('search', $search);

//
// Options list
//
} elseif ($mode == 'manage') {
    $params = $_REQUEST;

    list($product_options, $search) = fn_get_product_global_options($params, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);

    Tygh::$app['view']->assign('product_options', $product_options);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('object', 'global');

    if (empty($product_options) && defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('force_redirection', fn_url('product_options.manage'));
    }

//
// Apply options to products
//
} elseif ($mode == 'apply') {

    list($product_options, $search) = fn_get_product_global_options();

    Tygh::$app['view']->assign('product_options', $product_options);

//
// Update option
//
} elseif ($mode == 'update') {

    $product_id = !empty($_REQUEST['product_id']) ? $_REQUEST['product_id'] : 0;

    $o_data = fn_get_product_option_data($_REQUEST['option_id'], $product_id);

    if (fn_allowed_for('ULTIMATE') && !empty($_REQUEST['product_id'])) {
        Tygh::$app['view']->assign('shared_product', fn_ult_is_shared_product($_REQUEST['product_id']));
        Tygh::$app['view']->assign('product_company_id', db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $_REQUEST['product_id']));
    }

    if (isset($_REQUEST['object'])) {
        Tygh::$app['view']->assign('object', $_REQUEST['object']);
    }
    Tygh::$app['view']->assign('option_data', $o_data);
    Tygh::$app['view']->assign('option_id', $_REQUEST['option_id']);

}

if (!fn_allowed_for('ULTIMATE:FREE')) {
    //
    // Product options exceptions
    //
    if ($mode == 'exceptions') {

        $exceptions = fn_get_product_exceptions($_REQUEST['product_id']);
        $product_options = fn_get_product_options($_REQUEST['product_id'], DESCR_SL, true);
        $product_data = fn_get_product_data($_REQUEST['product_id'], $auth, DESCR_SL, '', true, true, true, true);

        Tygh::$app['view']->assign('product_options', $product_options);
        Tygh::$app['view']->assign('exceptions', $exceptions);
        Tygh::$app['view']->assign('product_data', $product_data);
    }
}

if (!empty($_REQUEST['product_id'])) {
    Tygh::$app['view']->assign('product_id', $_REQUEST['product_id']);
}
