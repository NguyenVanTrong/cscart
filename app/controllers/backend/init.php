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

use Tygh\Helpdesk;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\BackendMenu;
use Tygh\Navigation\Breadcrumbs;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

Tygh::$app['view']->assign('descr_sl', DESCR_SL);

if (!empty($auth['user_id']) && $auth['area'] != AREA) {
    $auth = array();

    return array(CONTROLLER_STATUS_REDIRECT, fn_url());
}

if (empty($auth['user_id']) && !fn_check_permissions(Registry::get('runtime.controller'), Registry::get('runtime.mode'), 'trusted_controllers')) {
    if (Registry::get('runtime.controller') != 'index') {
        fn_set_notification('E', __('access_denied'), __('error_not_logged'));

        if (defined('AJAX_REQUEST')) {
            // We should make redirect to page which triggered AJAX-request instead of the AJAX-requested one.
            $login_form_url = 'auth.login_form';

            if (isset($_SERVER['HTTP_REFERER']) &&
                ($referer = @parse_url($_SERVER['HTTP_REFERER'])) &&
                isset($referer['host'], $referer['query']) &&
                $referer['host'] == Registry::get('config.current_host')
            ) {
                $login_form_url .= '?return_url=' . urlencode(
                    fn_url_remove_service_params(Registry::get('config.admin_index') . '?' . $referer['query'])
                );
            }

            Tygh::$app['ajax']->assign('force_redirection', fn_url($login_form_url));
            exit;
        }
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url')));
} elseif (!empty($auth['user_id']) && !fn_check_user_type_access_rules($auth)) {
    fn_set_notification('E', __('error'), __('error_area_access_denied'));

    return array(CONTROLLER_STATUS_DENIED);
} elseif (!empty($auth['user_id']) && !fn_check_permissions(Registry::get('runtime.controller'), Registry::get('runtime.mode'), 'trusted_controllers') && $_SERVER['REQUEST_METHOD'] != 'POST') {
    // PCI DSS Compliance
    $auth['password_change_timestamp'] = !empty($auth['password_change_timestamp']) ? $auth['password_change_timestamp'] : 0;
    $time_diff = TIME - $auth['password_change_timestamp'];
    $expire = Registry::get('settings.Security.admin_password_expiration_period') * SECONDS_IN_DAY;

    if (!isset($auth['first_expire_check'])) {
        $auth['first_expire_check'] = true;
    }

    // We do not need to change the timestamp if this is an Ajax requests
    if (!defined('AJAX_REQUEST')) {
        Tygh::$app['session']['auth_timestamp'] = !isset(Tygh::$app['session']['auth_timestamp']) ? 0 : ++Tygh::$app['session']['auth_timestamp'];
    }

    // Make user change the password if:
    // - password has expired
    // - this is the first admin's login and change_admin_password_on_first_login is enabled
    // - this is the first vendor admin's login
    if (($auth['password_change_timestamp'] <= 1 && ((Registry::get('settings.Security.change_admin_password_on_first_login') == 'Y') || (!empty($auth['company_id']) && empty($auth['password_change_timestamp'])))) || ($expire && $time_diff >= $expire)) {

        Tygh::$app['session']['auth']['forced_password_change'] = true;

        if ($auth['first_expire_check']) {
            // we can redirect only on first check, else we can corrupt some admin's working processes ( such as ajax requests
            fn_delete_notification('insecure_password');
            $return_url = !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : Registry::get('config.current_url');

            return array(CONTROLLER_STATUS_REDIRECT, 'auth.password_change?return_url=' . urlencode($return_url));
        } else {
            if (!fn_notification_exists('extra', 'password_expire')) {
                fn_set_notification('E', __('warning'), __('error_password_expired_change', array(
                    '[link]' => fn_url('profiles.update', 'A')
                )), 'S', 'password_expire');
            }
        }
    } else {
        $auth['first_expire_check'] = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (fn_allowed_for('ULTIMATE')) {
        fn_ult_parse_request($_REQUEST);
    }

    return;
}

list($static, $actions, $selected_items) = BackendMenu::instance(
    Registry::get('runtime.controller'),
    Registry::get('runtime.mode')
)->generate($_REQUEST);

Registry::set('navigation', array(
    'static' => $static,
    'dynamic' => array('actions' => $actions),
    'selected_tab' => $selected_items['section'],
    'subsection' => $selected_items['item']
));

if (fn_allowed_for('ULTIMATE')) {
    if (!fn_ult_check_store_permission($_REQUEST, $redirect_controller)) {
        return array(CONTROLLER_STATUS_REDIRECT, $redirect_controller . '.manage');
    }
}

// Navigation is passed in view->display method to allow its modification in controllers
Tygh::$app['view']->assign('quick_menu', fn_get_quick_menu_data());


// update request history
// save only current and previous page requests in history
if (!defined('AJAX_REQUEST')) {
    $current_dispatch = Registry::get('runtime.controller') . '.' . Registry::get('runtime.mode');
    if (!empty(Tygh::$app['session']['request_history']['current']['dispatch'])) {
        $hist_dispatch = !empty(Tygh::$app['session']['request_history']['current']['dispatch']) ? Tygh::$app['session']['request_history']['current']['dispatch'] : '';
        if ($hist_dispatch != $current_dispatch) {
            // replace previously saved reuest if new page is opened
            Tygh::$app['session']['request_history']['prev'] = Tygh::$app['session']['request_history']['current'];
        }
    }
    Tygh::$app['session']['request_history']['current'] = array (
        'dispatch' => $current_dispatch,
        'params' => $_REQUEST
    );
}

// generate breadcrumbs
$prev_request = !empty(Tygh::$app['session']['request_history']['prev']['params']) ? Tygh::$app['session']['request_history']['prev']['params'] : array();
$breadcrumbs = Breadcrumbs::instance(Registry::get('runtime.controller'), Registry::get('runtime.mode'), AREA, $_REQUEST, $prev_request)->getLinks();
Tygh::$app['view']->assign('breadcrumbs', $breadcrumbs);

// Check if we need translate characters to UTF-8 format
$schema = fn_get_schema('literal_converter', 'utf8');
if (isset($schema['need_converting']) && $schema['need_converting']) {
    Tygh::$app['view']->assign('data', $schema['data']);
}

$schema = fn_get_schema('last_edited_items', 'schema');
$last_items_cnt = LAST_EDITED_ITEMS_COUNT;

if (empty(Tygh::$app['session']['last_edited_items'])) {
    $stored_items = fn_get_user_additional_data('L');
    $last_edited_items = empty($stored_items) ? array() : $stored_items;
    Tygh::$app['session']['last_edited_items'] = $last_edited_items;
} else {
    $last_edited_items = Tygh::$app['session']['last_edited_items'];
}

if (!empty($schema[Registry::get('runtime.controller') . '.' . Registry::get('runtime.mode')]) && !defined('AJAX_REQUEST')) {
    $items_schema = $schema[Registry::get('runtime.controller') . '.' . Registry::get('runtime.mode')];
    if (empty($items_schema['func'])) {
        $c_elm = '';
    } else {
        $c_elm = $items_schema['func'];
        foreach ($c_elm as $k => $v) {
            if (strpos($v, '@') !== false) {
                $ind = str_replace('@', '', $v);
                if (!empty($auth[$ind]) || !empty($_REQUEST[$ind])) {
                    $c_elm[$k] = ($ind == 'user_id' && empty($_REQUEST[$ind])) ? $auth[$ind] : $_REQUEST[$ind];
                }
            }
        }
    }

    $url = Registry::get('config.current_url');

    if (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.simple_ultimate')) {
        $url = fn_link_attach($url, 'switch_company_id=' . Registry::ifGet('runtime.company_id', 'all'));
        $url = str_replace('&amp;', '&', $url); // FIXME: workaround for fn_link_attach return result
    }

    $last_item = array('func' => $c_elm, 'url' => $url, 'icon' => (empty($items_schema['icon']) ? '' : $items_schema['icon']), 'text' => (empty($items_schema['text']) ? '' : $items_schema['text']));
    $current_hash = fn_crc32(!empty($c_elm) ? implode('', $c_elm) : $items_schema['text']);

    // remove element if it already exists and add it to the end of history
    unset($last_edited_items[$current_hash]);
    $last_edited_items[$current_hash] = $last_item;

    if (count($last_edited_items) > $last_items_cnt) {
        foreach ($last_edited_items as $k => $v) {
            unset($last_edited_items[$k]);
            if (count($last_edited_items) == $last_items_cnt) {
                break;
            }
        }
    }
}

$last_items = array();
if (!empty($last_edited_items)) {
    foreach ($last_edited_items as $hash => $v) {

        if (!empty($current_hash) && $hash == $current_hash) {
            // ignore current page
            continue;
        }

        if (!empty($v['func'])) {
            $func = array_shift($v['func']);
            if (function_exists($func)) {
                $content = call_user_func_array($func, $v['func']);
                if (!empty($content)) {
                    $name = (empty($v['text']) ? '' : __($v['text']) . ': ') . $content;
                    array_unshift($last_items, array('name' => $name, 'url' => $v['url'], 'icon' => $v['icon']));
                } else {
                    unset($last_edited_items[$hash]);
                }
            } else {
                unset($last_edited_items[$hash]);
            }
        } else {
            array_unshift($last_items, array('name' => __($v['text']), 'url' => $v['url'], 'icon' => $v['icon']));
        }
    }
}

Tygh::$app['view']->assign('last_edited_items', $last_items);

// save changed items history
Tygh::$app['session']['last_edited_items'] = $last_edited_items;
fn_save_user_additional_data('L', $last_edited_items);

if (empty($auth['company_id']) && !empty($auth['user_id']) && $auth['area'] == AREA && $auth['is_root']) {
    $messages = fn_get_storage_data('hd_messages');
    if (!empty($messages)) {
        $messages = unserialize($messages);
        foreach ($messages as $message) {
            fn_set_notification($message['type'], $message['title'], $message['text'], isset($message['state']) ? $message['state'] : '');
        }

        fn_set_storage_data('hd_messages', '');
    }
}

/* HIDE IT! */
$store_mode = fn_get_storage_data('store_mode');
$store_mode_errors = fn_get_storage_data('store_mode_errors');
$store_mode_trial = fn_get_storage_data('store_mode_trial');
$license_number = fn_get_storage_data('store_mode_license');
$product_state_suffix = fn_get_product_state_suffix($store_mode);

if (empty($license_number)) {
    $license_number = Settings::instance()->getValue('license_number', 'Upgrade_center');;
}

$license_number = Helpdesk::masqueLicenseNumber(
    $license_number,
    Registry::ifGet('config.demo_mode', false)
);

Tygh::$app['view']->assign('store_mode_license', $license_number);
Tygh::$app['view']->assign('store_mode_errors', unserialize($store_mode_errors));
Tygh::$app['view']->assign('store_mode', $store_mode);
Tygh::$app['view']->assign('product_state_suffix', $product_state_suffix);
Tygh::$app['view']->assign('store_mode_number_of_storefronts', count(fn_get_all_companies_ids()));
Tygh::$app['view']->assign('store_mode_allowed_number_of_storefronts', fn_get_storage_data('allowed_number_of_stores'));

if (!Registry::get('runtime.company_id') && Registry::get('runtime.controller') != 'auth' && $store_mode_trial == 'trial_is_expired') {
    Tygh::$app['view']->assign('show_trial_dialog', true);
} elseif (!Registry::get('runtime.company_id') && Registry::get('runtime.controller') != 'auth' && $store_mode == "new" || !empty($store_mode_errors)) {
    Tygh::$app['view']->assign('show_sm_dialog', true);
}

fn_set_storage_data('store_mode_errors', null);
fn_set_storage_data('store_mode_license', null);
/* /HIDE IT! */
