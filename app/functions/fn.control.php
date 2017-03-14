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

use Tygh\Exceptions\DeveloperException;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Debugger;
use Tygh\BlockManager\Location;
use Tygh\BlockManager\SchemesManager;
use Tygh\Navigation\LastView;
use Tygh\Router;
use Tygh\Bootstrap;
use Tygh\Themes\Themes;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

define('GET_CONTROLLERS', 1);
define('GET_PRE_CONTROLLERS', 2);
define('GET_POST_CONTROLLERS', 3);

/**
 * Set hook to use by addons
 *
 * @param mixed $argN argument, passed to addon
 * @return boolean always true
 */
function fn_set_hook($hook_name = NULL, &$arg1 = NULL, &$arg2 = NULL, &$arg3 = NULL, &$arg4 = NULL, &$arg5 = NULL, &$arg6 = NULL, &$arg7 = NULL, &$arg8 = NULL, &$arg9 = NULL, &$arg10 = NULL, &$arg11 = NULL, &$arg12 = NULL, &$arg13 = NULL, &$arg14 = NULL, &$arg15 = NULL)
{
    /**
     * @var bool[]|null $callable_functions Cache of validations that hook's function is callable groupped by func name.
     */
    static $callable_functions;

    /**
     * @var array $hooks_already_sorted Cache of hook lists ordered by addon priority and groupped by hook name.
     */
    static $hooks_already_sorted = array();

    /**
     * @var array|null $hooks Function's local cache of hooks that have been registered by addons.
     */
    static $hooks = null;

    /**
     * @var bool $addons_initiated Function's local cache of addons' initialization state.
     */
    static $addons_initiated = false;

    // We use local hooks cache that was filled at previous fn_set_hook() call
    // only if addons were already initiated at that call.
    if ($addons_initiated) {
        $update_hooks_cache = false;
    }
    // Otherwise, we should renew local hooks cache:
    else {
        $update_hooks_cache = true;

        // Update local cache of addons' init state
        $addons_initiated = Registry::get('addons_initiated');
    }

    if (
        $hooks === null
        || $update_hooks_cache
        || defined('DISABLE_HOOK_CACHE')
    ) {
        // Updating local hooks cache
        $hooks = Registry::get('hooks');
        $hooks_already_sorted = array();
    }

    $arg_count = func_num_args();
    if ($arg_count === 1) {
        $args = array();
    } elseif ($arg_count === 2) {
        $args = array(&$arg1);
    } elseif ($arg_count === 3) {
        $args = array(&$arg1, &$arg2);
    } elseif ($arg_count === 4) {
        $args = array(&$arg1, &$arg2, &$arg3);
    } elseif ($arg_count === 5) {
        $args = array(&$arg1, &$arg2, &$arg3, &$arg4);
    } elseif ($arg_count === 6) {
        $args = array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5);
    } elseif ($arg_count === 7) {
        $args = array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6);
    } elseif ($arg_count === 8) {
        $args = array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6, &$arg7);
    } elseif ($arg_count === 9) {
        $args = array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6, &$arg7, &$arg8);
    } elseif ($arg_count === 10) {
        $args = array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6, &$arg7, &$arg8, &$arg9);
    } elseif ($arg_count === 11) {
        $args = array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6, &$arg7, &$arg8, &$arg9, &$arg10);
    } elseif ($arg_count === 12) {
        $args = array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6, &$arg7, &$arg8, &$arg9, &$arg10, &$arg11);
    } elseif ($arg_count === 13) {
        $args = array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6, &$arg7, &$arg8, &$arg9, &$arg10, &$arg11, &$arg12);
    } elseif ($arg_count === 14) {
        $args = array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6, &$arg7, &$arg8, &$arg9, &$arg10, &$arg11, &$arg12, &$arg13);
    } elseif ($arg_count === 15) {
        $args = array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6, &$arg7, &$arg8, &$arg9, &$arg10, &$arg11, &$arg12, &$arg13, &$arg14);
    } elseif ($arg_count === 16) {
        $args = array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6, &$arg7, &$arg8, &$arg9, &$arg10, &$arg11, &$arg12, &$arg13, &$arg14, &$arg15);
    }

    // Check for the core functions
    if (is_callable('fn_core_' . $hook_name)) {
        call_user_func_array('fn_core_' . $hook_name, $args);
    }

    $edition_acronym = fn_get_edition_acronym(PRODUCT_EDITION);
    if (!empty($edition_acronym) && function_exists("fn_{$edition_acronym}_{$hook_name}")) {
        call_user_func_array("fn_{$edition_acronym}_{$hook_name}", $args);
    }

    if (isset($hooks[$hook_name])) {
        // cache hooks sorting
        if (!isset($hooks_already_sorted[$hook_name])) {
            $hooks[$hook_name] = fn_sort_array_by_key($hooks[$hook_name], 'priority');
            $hooks_already_sorted[$hook_name] = true;
            Registry::set('hooks', $hooks, true);
        }

        foreach ($hooks[$hook_name] as $callback) {
            // cache if hook function callable
            if (!isset($callable_functions[$callback['func']])) {
                if (!is_callable($callback['func'])) {
                    throw new DeveloperException("Hook $callback[func] is not callable");
                }
                $callable_functions[$callback['func']] = true;
            }
            call_user_func_array($callback['func'], $args);
        }
    }

    return true;
}

/**
 * Register hooks addon uses
 *
 * @return boolean always true
 */
function fn_register_hooks()
{
    $args = func_get_args();
    $backtrace = debug_backtrace();

    $addon_path = fn_unified_path($backtrace[0]['file']);

    $path_dirs = explode('/', $addon_path);
    array_pop($path_dirs);
    $addon_name = array_pop($path_dirs);

    $hooks = Registry::get('hooks');

    $addon_priority = Registry::get('addons.' . $addon_name . '.priority');
    foreach ($args as &$hook) {
        $priority = $addon_priority;
        $addon = $addon_name;

        // if we get array we need to set priority manually
        if (is_array($hook)) {
            $priority = $hook[1];

            if (isset($hook[2])) {
                $addon = $hook[2];
                if (Registry::get('addons.' . $addon . '.status') != 'A') { // skip hook registration if addon is not enabled
                    continue;
                }
                if ($priority === '') {
                    $priority = Registry::get('addons.' . $addon . '.priority');
                }
            }

            if (empty($priority)) {
                $priority = $addon_priority;
            }

            $hook = $hook[0];
        }

        $callback = 'fn_' . $addon . '_' . $hook;

        if (!isset($hooks[$hook])) {
            $hooks[$hook] = array();
        }

        $hooks[$hook][] = array('func' => $callback, 'addon' => $addon, 'priority' => $priority);
    }

    Registry::set('hooks', $hooks, true);

    return true;
}

/**
 * Gets list of secure controllers which use https connection
 *
 * @return array list of secure controllers
 */
function fn_get_secure_controllers()
{
    $secure_controllers = array(
        'payment_notification' => 'passive',
        'image' => 'passive',
    );

    if (Registry::get('settings.Security.secure_storefront') != 'none') {
        $secure_controllers = array_merge($secure_controllers, array(
            'auth' => 'active',
            'orders' => 'active',
            'profiles' => 'active',
            'checkout' => 'active',
        ));
    }

    fn_set_hook('init_secure_controllers', $secure_controllers);

    return $secure_controllers;
}

/**
 * Dispathes the execution control to correct controller
 *
 * @return nothing
 */
function fn_dispatch($controller = '', $mode = '', $action = '', $dispatch_extra = '', $area = AREA)
{
    Debugger::checkpoint('After init');

    $auth = Tygh::$app['session']['auth'];
    $controller = empty($controller) ? Registry::get('runtime.controller') : $controller;
    $mode = empty($mode) ? Registry::get('runtime.mode') : $mode;
    $action = empty($action) ? Registry::get('runtime.action') : $action;
    $dispatch_extra = empty($dispatch_extra) ? Registry::get('runtime.dispatch_extra') : $dispatch_extra;

    fn_set_hook('before_dispatch', $controller, $mode, $action, $dispatch_extra, $area);

    $view = Tygh::$app['view'];
    $run_controllers = true;
    $external = false;
    $status = CONTROLLER_STATUS_NO_PAGE;

    // CSRF protection
    if (fn_is_csrf_protection_enabled($auth) && !fn_csrf_validate_request(array(
            'server' => $_SERVER,
            'request' => $_REQUEST,
            'session' => Tygh::$app['session'],
            'controller' => $controller,
            'mode' => $mode,
            'action' => $action,
            'dispatch_extra' => $dispatch_extra,
            'area' => $area,
            'auth' => $auth
        ))
    ) {
        fn_set_notification('E', __('error'), __('text_csrf_attack'));
        fn_redirect(fn_url());
    }

    // If $config['http_host'] was different from the domain name, there was redirection to $config['http_host'] value.
    if (strtolower(Registry::get('config.current_host')) != strtolower(REAL_HOST)
        && $_SERVER['REQUEST_METHOD'] == 'GET'
        && !defined('CONSOLE')
    ) {
        if (!empty($_SERVER['REDIRECT_URL'])) {
            $qstring = $_SERVER['REDIRECT_URL'];
        } else {
            if (!empty($_SERVER['REQUEST_URI'])) {
                $qstring = $_SERVER['REQUEST_URI'];
            } else {
                $qstring = Registry::get('config.current_url');
            }
        }

        $curent_path = Registry::get('config.current_path');
        if (!empty($curent_path) && strpos($qstring, $curent_path) === 0) {
            $qstring = substr_replace($qstring, '', 0, fn_strlen($curent_path));
        }

        fn_redirect(Registry::get('config.current_location') . $qstring, false, true);
    }

    $upload_max_filesize = Bootstrap::getIniParam('upload_max_filesize');
    $post_max_size = Bootstrap::getIniParam('post_max_size');

    if (!defined('AJAX_REQUEST') &&
        isset($_SERVER['CONTENT_LENGTH']) && (
            (!empty($upload_max_filesize) && $_SERVER['CONTENT_LENGTH'] > fn_return_bytes($upload_max_filesize)) ||
            (!empty($post_max_size) && $_SERVER['CONTENT_LENGTH'] > fn_return_bytes($post_max_size))
        )
    ) {
        $max_size = fn_return_bytes($upload_max_filesize) < fn_return_bytes($post_max_size) ? $upload_max_filesize : $post_max_size;

        fn_set_notification('E', __('error'), __('text_forbidden_uploaded_file_size', array(
            '[size]' => $max_size
        )));
        fn_redirect($_SERVER['HTTP_REFERER']);
    }

    // If URL contains session ID, remove it
    if (!defined('AJAX_REQUEST') && !empty($_REQUEST[Tygh::$app['session']->getName()]) && $_SERVER['REQUEST_METHOD'] == 'GET') {
        fn_redirect(fn_query_remove(Registry::get('config.current_url'), Tygh::$app['session']->getName()));
    }

    // If demo mode is enabled, check permissions FIX ME - why did we need one more user login check?
    if ($area == 'A') {
        if (Registry::get('config.demo_mode') == true) {
            $run_controllers = fn_check_permissions($controller, $mode, 'demo');

            if ($run_controllers == false) {
                fn_set_notification('W', __('demo_mode'), __('demo_mode_content_text'), 'K', 'demo_mode');
                if (defined('AJAX_REQUEST')) {
                    exit;
                }

                fn_delete_notification('changes_saved');

                $status = CONTROLLER_STATUS_REDIRECT;
                $_REQUEST['redirect_url'] = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : fn_url('');
            }

        } else {
            $run_controllers = fn_check_permissions($controller, $mode, 'admin', '', $_REQUEST);
            if ($run_controllers == false) {
                if (defined('AJAX_REQUEST')) {
                    $_info = (Debugger::isActive() || fn_is_development()) ? ' ' . $controller . '.' . $mode : '';
                    fn_set_notification('W', __('warning'), __('access_denied') . $_info);
                    exit;
                }
                $status = CONTROLLER_STATUS_DENIED;
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] != 'POST' && !defined('AJAX_REQUEST') && !defined('CONSOLE')) {
        if ($area == 'A' && empty($_REQUEST['keep_location'])) {
            if (!defined('HTTPS') && Registry::get('settings.Security.secure_admin') == 'Y') {
                fn_redirect(Registry::get('config.https_location') . '/' . Registry::get('config.current_url'));
            } elseif (defined('HTTPS') && Registry::get('settings.Security.secure_admin') != 'Y') {
                fn_redirect(Registry::get('config.http_location') . '/' . Registry::get('config.current_url'));
            }
        } elseif ($area == 'C') {
            $secure_controllers = fn_get_secure_controllers();
            // if we are not on https but controller is secure, redirect to https
            if (
                !defined('HTTPS')
                && (
                    Registry::get('settings.Security.secure_storefront') == 'full' ||
                    (isset($secure_controllers[$controller])
                    && $secure_controllers[$controller] == 'active')
                )
            ) {
                fn_redirect(Registry::get('config.https_location') . '/' . Registry::get('config.current_url'), false, true);
            }

            // if we are on https and the controller is insecure, redirect to http
            if (
                defined('HTTPS')
                && Registry::get('settings.Security.secure_storefront') != 'full'
                && !isset($secure_controllers[$controller])
                && Registry::get('settings.Security.keep_https') != 'Y'
            ) {
                fn_redirect(Registry::get('config.http_location') . '/' . Registry::get('config.current_url'), false, true);
            }
        }
    }

    LastView::instance()->prepare($_REQUEST);

    $controllers_cascade = array();
    $controllers_list = array('init');
    if ($run_controllers == true) {
        $controllers_list[] = $controller;
        $controllers_list = array_unique($controllers_list);
    }
    foreach ($controllers_list as $ctrl) {
        $core_controllers = fn_init_core_controllers($ctrl);
        list($addon_controllers) = fn_init_addon_controllers($ctrl);

        if (empty($core_controllers) && empty($addon_controllers)) {
            //$controllers_cascade = array(); // FIXME: controllers_cascade contains INIT. We should not clear initiation code.
            $status = CONTROLLER_STATUS_NO_PAGE;
            $run_controllers = false;
            break;
        }

        if ((count($core_controllers) + count($addon_controllers)) > 1) {
            throw new DeveloperException('Duplicate controller ' . $controller . var_export(array_merge($core_controllers, $addon_controllers), true));
        }

        $core_pre_controllers = fn_init_core_controllers($ctrl, GET_PRE_CONTROLLERS);
        $core_post_controllers = fn_init_core_controllers($ctrl, GET_POST_CONTROLLERS);

        list($addon_pre_controllers) = fn_init_addon_controllers($ctrl, GET_PRE_CONTROLLERS);
        list($addon_post_controllers, $addons) = fn_init_addon_controllers($ctrl, GET_POST_CONTROLLERS);

        // we put addon post-controller to the top of post-controller cascade if current addon serves this request
        if (count($addon_controllers)) {
            $addon_post_controllers = fn_reorder_post_controllers($addon_post_controllers, $addon_controllers[0]);
        }

        $controllers_cascade = array_merge($controllers_cascade, $addon_pre_controllers, $core_pre_controllers, $core_controllers, $addon_controllers, $core_post_controllers, $addon_post_controllers);

        if (empty($controllers_cascade)) {
            throw new DeveloperException("No controllers for: $ctrl");
        }
    }

    if ($mode == 'add') {
        $tpl = 'update.tpl';
    } elseif (strpos($mode, 'add_') === 0) {
        $tpl = str_replace('add_', 'update_', $mode) . '.tpl';
    } else {
        $tpl = $mode . '.tpl';
    }

    $view = Tygh::$app['view'];
    if ($view->templateExists('views/' . $controller . '/' . $tpl)) { // try to find template in base views
        $view->assign('content_tpl', 'views/' . $controller . '/' . $tpl);
    } elseif (defined('LOADED_ADDON_PATH') && $view->templateExists('addons/' . LOADED_ADDON_PATH . '/views/' . $controller . '/' . $tpl)) { // try to find template in addon views
        $view->assign('content_tpl', 'addons/' . LOADED_ADDON_PATH . '/views/' . $controller . '/' . $tpl);
    } elseif (!empty($addons)) { // try to find template in addon views that extend base views
        foreach ($addons as $addon => $_v) {
            if ($view->templateExists('addons/' . $addon . '/views/' . $controller . '/' . $tpl)) {
                $view->assign('content_tpl', 'addons/' . $addon . '/views/' . $controller . '/' . $tpl);
                break;
            }
        }
    }

    /**
     * Performs actions after template assignment and before controller run
     *
     * @param string $controller          controller name
     * @param string $mode                controller mode name
     * @param string $area                current working area
     * @param array  $controllers_cascade list of controllers to run
     */
    fn_set_hook('dispatch_assign_template', $controller, $mode, $area, $controllers_cascade);

    foreach ($controllers_cascade as $item) {
        $_res = fn_run_controller($item, $controller, $mode, $action, $dispatch_extra); // 0 - status, 1 - url

        $url = !empty($_res[1]) ? $_res[1] : '';
        $external = !empty($_res[2]) ? $_res[2] : false;
        $permanent = !empty($_res[3]) ? $_res[3] : false;

        // Status could be changed only if we allow to run controllers despite of init controller
        if ($run_controllers == true) {
            $status = !empty($_res[0]) ? $_res[0] : CONTROLLER_STATUS_OK;
        }

        if ($status == CONTROLLER_STATUS_OK && !empty($url)) {
            $redirect_url = $url;
        } elseif ($status == CONTROLLER_STATUS_REDIRECT && !empty($url)) {
            $redirect_url = $url;
            break;
        } elseif ($status == CONTROLLER_STATUS_DENIED || $status == CONTROLLER_STATUS_NO_PAGE) {
            break;
        }
    }

    LastView::instance()->init($_REQUEST);

    // In console mode, just stop here
    if (defined('CONSOLE')) {
        $notifications = fn_get_notifications();
        $exit_code = 0;
        foreach ($notifications as $n) {
            fn_echo('[' . $n['title'] . '] ' . $n['message'] . "\n");
            if ($n['type'] == 'E') {
                $exit_code = 1;
            }
        }
        exit($exit_code);
    }

    if (!empty($auth['this_login']) && Registry::ifGet($auth['this_login'], 'N') === 'Y') {
        fn_set_notification('E', __('error'), __(ACCOUNT_TYPE . LOGIN_STATUS_USER_DISABLED));
        $status = CONTROLLER_STATUS_DENIED;
    }

    // [Block manager]
    // block manager is disabled for vendors.
    if (!(
        (fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id'))
        ||
        (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.company_id'))
    )) {
        if (fn_check_permissions('block_manager', 'manage', 'admin')) {
            $dynamic_object = SchemesManager::getDynamicObject($_REQUEST['dispatch'], $area, $_REQUEST);
            if (!empty($dynamic_object)) {
                if ($area == 'A' && Registry::get('runtime.mode') != 'add' && !empty($_REQUEST[$dynamic_object['key']])) {
                    $object_id = $_REQUEST[$dynamic_object['key']];
                    $location = Location::instance()->get($dynamic_object['customer_dispatch'], $dynamic_object, CART_LANGUAGE);

                    if (!empty($location) && $location['is_default'] != 1) {
                        $params = array(
                            'dynamic_object' => array(
                                'object_type' => $dynamic_object['object_type'],
                                'object_id' => $object_id
                            ),
                            $dynamic_object['key'] => $object_id,
                            'manage_url' => Registry::get('config.current_url')
                        );

                        Registry::set('navigation.tabs.blocks', array(
                            'title' => __('layouts'),
                            'href' => 'block_manager.manage_in_tab?' . http_build_query($params),
                            'ajax' => true,
                        ));
                    }
                }
            }
        }
    }
    // [/Block manager]

    // Redirect if controller returned successful/redirect status only
    if (in_array($status, array(CONTROLLER_STATUS_OK, CONTROLLER_STATUS_REDIRECT)) && !empty($_REQUEST['redirect_url']) && !$external) {
        $redirect_url = $_REQUEST['redirect_url'];
    }

    // If controller returns "Redirect" status, check if redirect url exists
    if ($status == CONTROLLER_STATUS_REDIRECT && empty($redirect_url)) {
        $status = CONTROLLER_STATUS_NO_PAGE;
    }

    // In backend show "changes saved" notification
    if ($area == 'A' && $_SERVER['REQUEST_METHOD'] == 'POST' && in_array($status, array(CONTROLLER_STATUS_OK, CONTROLLER_STATUS_REDIRECT))) {
        if (strpos($mode, 'update') !== false && $mode != 'update_status' && $mode != 'update_mode' && !fn_notification_exists('extra', 'demo_mode') && !fn_notification_exists('type', 'E')) {
            fn_set_notification('N', __('notice'), __('text_changes_saved'), 'I', 'changes_saved');
        }
    }

    // Attach params and redirect if needed
    if (in_array($status, array(CONTROLLER_STATUS_OK, CONTROLLER_STATUS_REDIRECT)) && !empty($redirect_url)) {
        if (!isset($_REQUEST['return_to_list'])) {
            $params = array (
                'page',
                'selected_section',
                'active_tab'
            );

            $url_params = array();
            foreach ($params as $param) {
                if (!empty($_REQUEST[$param])) {
                    $url_params[$param] = $_REQUEST[$param];
                }
            }

            if (!empty($url_params)) {
                $redirect_url = fn_link_attach($redirect_url, http_build_query($url_params));
            }
        }

        if (!isset($external)) {
            $external = false;
        }

        if (!isset($permanent)) {
            $permanent = false;
        }
        fn_redirect($redirect_url, $external, $permanent);
    }

    if (!$view->getTemplateVars('content_tpl') && $status == CONTROLLER_STATUS_OK) { // FIXME
        $status = CONTROLLER_STATUS_NO_PAGE;
    }

    if ($status != CONTROLLER_STATUS_OK) {

        if ($status == CONTROLLER_STATUS_NO_PAGE) {
            if ($area == 'A' && empty($auth['user_id'])) {
                // If admin is not logged in redirect to login page from not found page
                fn_set_notification('W', __('page_not_found'), __('page_not_found_text'));
                fn_redirect("auth.login_form");
            }

            header(' ', true, 404);
        }
        $view->assign('exception_status', $status);
        if ($area == 'A') {
            $view->assign('content_tpl', 'exception.tpl'); // for backend only
        }
        if ($status == CONTROLLER_STATUS_DENIED) {
            $view->assign('page_title', __('access_denied'));
        } elseif ($status == CONTROLLER_STATUS_NO_PAGE) {
            $view->assign('page_title', __('page_not_found'));
        }
    }

    fn_set_hook('dispatch_before_display');

    Debugger::checkpoint('Before TPL');

    // Pass current URL to ajax response only if we render whole page
    if (defined('AJAX_REQUEST') && Registry::get('runtime.root_template') == 'index.tpl') {
        Tygh::$app['ajax']->assign('current_url', fn_url(Registry::get('config.current_url'), $area, 'current'));
    }

    $template = Tygh::$app['view']->fetch(Registry::get('runtime.root_template'));

    /**
     * Allows to perform actions before HTTP response is sent to the client.
     * This is the last place where you can modify HTTP headers list.
     *
     * @param string $status     Controller response status
     * @param string $area       Currentry running application area
     * @param string $controller Executed controller
     * @param string $mode       Executed mode
     * @param string $action     Executed action
     */
    fn_set_hook('dispatch_before_send_response', $status, $area, $controller, $mode, $action);

    echo $template;

    Debugger::checkpoint('After TPL');
    Debugger::display();

    fn_set_hook('complete');

    if (defined('AJAX_REQUEST')) {
        // HHVM workaround. Destroy Ajax object manually if it has been created.
        $ajax = Tygh::$app['ajax'];
        $ajax = null;
    }

    exit; // stop execution
}

/**
 * Puts the addon post-controller to the top of post-controllers cascade if current addon serves this request
 *
 * @param array $addon_post_controllers post controllers from addons
 * @param array $current_controller current controllers list
 * @return array controllers list
 */
function fn_reorder_post_controllers($addon_post_controllers, $current_controller)
{
    if (empty($addon_post_controllers) || empty($current_controller)) {
        return $addon_post_controllers;
    }

    // get addon name from the path like /var/www/html/cart/app/addons/[addon]/controllers/backend/[controller].php
    $part = substr($current_controller, strlen(Registry::get('config.dir.addons')));
    // we have [addon]/controllers/backend/[controller].php in the $part
    $addon_name = substr($part, 0, strpos($part, '/'));

    // we search post-controller of the addon that owns active controller of current request
    // and if we find it we put this post-controller to the top of the cascade
    foreach ($addon_post_controllers as $k => $post_controller) {
        if (strpos($post_controller, Registry::get('config.dir.addons') . $post_controller) !== false) {
            // delete in current place..
            unset($addon_post_controllers[$k]);
            // and put at the beginning
            array_unshift($addon_post_controllers, $post_controller);
            break; // only one post controller can be here
        }
    }

    return $addon_post_controllers;
}

/**
 * Runs specified controller by including its file
 *
 * @param string $path path to controller
 * @return array controller return status
 */
function fn_run_controller($path, $controller, $mode, $action, $dispatch_extra)
{
    static $check_included = array();

    $auth = & Tygh::$app['session']['auth'];

    if (!empty($check_included[$path])) {
        $code = fn_get_contents($path);
        $code = str_replace(array('function fn', '<?php', '?>'), array('function _fn', '', ''), $code);

        return eval($code);

    } else {
        $check_included[$path] = true;

        return include($path);
    }
}

/**
 * Generates list of core (pre/post)controllers
 *
 * @param string $controller controller name
 * @param string $type controller type (pre/post)
 * @return array controllers list
 */
function fn_init_core_controllers($controller, $type = GET_CONTROLLERS, $area = AREA)
{
    $controllers = array();

    $prefix = '';
    $area_name = fn_get_area_name($area);

    if ($type == GET_POST_CONTROLLERS) {
        $prefix = '.post';
    } elseif ($type == GET_PRE_CONTROLLERS) {
        $prefix = '.pre';
    }

    // try to find area-specific controller
    if (is_readable(Registry::get('config.dir.root') . '/app/controllers/' . $area_name . '/' . $controller . $prefix . '.php')) {
        $controllers[] = Registry::get('config.dir.root') . '/app/controllers/' . $area_name . '/' . $controller . $prefix . '.php';
    }

    // try to find common controller
    if (is_readable(Registry::get('config.dir.root') . '/app/controllers/common/' . $controller . $prefix . '.php')) {
        $controllers[] = Registry::get('config.dir.root') . '/app/controllers/common/' . $controller . $prefix . '.php';
    }

    return $controllers;
}

/**
 * Generates list of (pre/post)controllers from active addons
 *
 * @param string $controller controller name
 * @param string $type controller type (pre/post)
 * @return array controllers list and active addons
 */
function fn_init_addon_controllers($controller, $type = GET_CONTROLLERS, $area = AREA)
{
    $controllers = array();
    static $addons = array();
    $prefix = '';
    $area_name = fn_get_area_name($area);

    if ($type == GET_POST_CONTROLLERS) {
        $prefix = '.post';
    } elseif ($type == GET_PRE_CONTROLLERS) {
        $prefix = '.pre';
    }

    foreach ((array) Registry::get('addons') as $addon_name => $data) {
        if ($data['status'] == 'A') {
            // try to find area-specific controller
            $dir = Registry::get('config.dir.addons') . $addon_name . '/controllers/' . $area_name . '/';
            if (is_readable($dir . $controller . $prefix . '.php')) {
                $controllers[] = $dir . $controller . $prefix . '.php';
                $addons[$addon_name] = true;
                if (empty($prefix)) {
                    fn_define('LOADED_ADDON_PATH', $addon_name);
                }
            }

            // try to find common controller
            $dir = Registry::get('config.dir.addons') . $addon_name . '/controllers/common/';
            if (is_readable($dir . $controller . $prefix . '.php')) {
                $controllers[] = $dir . $controller . $prefix . '.php';
                $addons[$addon_name] = true;
                if (empty($prefix)) {
                    fn_define('LOADED_ADDON_PATH', $addon_name);
                }
            }
        }
    }

    return array($controllers, $addons);
}

/**
 * Looks for "dispatch" parameter in REQUEST array and extracts controller, mode, action and extra parameters.
 *
 * @param array $req Request parameters
 * @param string $area Area
 * @return boolean always true
 */
function fn_get_route(&$req, $area = AREA)
{
    $result = array(INIT_STATUS_OK);

    $is_allowed_url = fn_check_requested_url();

    if (!$is_allowed_url) {

        $request_uri = fn_get_request_uri($_SERVER['REQUEST_URI']);

        $router = new Router($req);
        $router->addRoutes(fn_get_schema('routes', 'objects'));

        if ($params = $router->match($request_uri)) {
            $is_allowed_url = true;
            $req = $params;
        }
    }

    fn_set_hook('get_route', $req, $result, $area, $is_allowed_url);

    if (!$is_allowed_url) {
        $req = array(
            'dispatch' => '_no_page'
        );
    }

    if (!empty($req['dispatch'])) {
        $dispatch = is_array($req['dispatch']) ? key($req['dispatch']) : $req['dispatch'];
    } else {
        $dispatch = 'index.index';
    }

    rtrim($dispatch, '/.');
    $dispatch = str_replace('/', '.', $dispatch);
    $parts = explode('.', $dispatch);

    Registry::set('runtime.controller', !empty($parts[0]) ? basename($parts[0]) : 'index');
    Registry::set('runtime.mode', !empty($parts[1]) ? basename($parts[1]) : 'index');
    Registry::set('runtime.action', !empty($parts[2]) ? $parts[2] : '');
    Registry::set('runtime.dispatch_extra', !empty($parts[3]) ? $parts[3] : '');

    Registry::set('runtime.checkout', false);
    Registry::set('runtime.root_template', 'index.tpl');

    $current_url_params = $req;
    $req['dispatch'] = $dispatch;

    // prevent dispatch=index.index in current url on homepage
    if ($dispatch != 'index.index') {
        $current_url_params['dispatch'] = $dispatch;
    }

    // URL's assignments
    Registry::set('config.current_url', fn_url_remove_service_params(Registry::get('config.' . ACCOUNT_TYPE . '_index') . ((!empty($current_url_params)) ? '?' . http_build_query($current_url_params) : '')));

    return $result;
}

/**
 * Parse addon options
 *
 * @param string $options serialized options
 * @return array parsed options list
 */
function fn_parse_addon_options($options)
{
    $options = unserialize($options);
    if (!empty($options)) {
        foreach ($options as $k => $v) {
            if (strpos($v, '#M#') === 0) {
                parse_str(str_replace('#M#', '', $v), $options[$k]);
            }
        }
    }

    return $options;
}

/**
 * Get list of templates that should be overridden by addons
 *
 * @param  string $resource_name    Base template name
 * @param  Smarty $view             Templater object
 *
 * @return string Overridden template name
 */
function fn_addon_template_overrides($resource_name, &$view)
{
    static $init = array();

    //$o_name = 'template_overrides_' . AREA;
    $template_dir = rtrim($view->getTemplateDir(0), '/').'/';

    $cache_prefix = 'template_overrides';
    $cache_key = md5(rtrim($template_dir, '/'));

    if (!isset($init[$cache_key])) {
        Registry::registerCache(array($cache_prefix, $cache_key), array('addons'), Registry::cacheLevel('static'));

        if (!Registry::isExist($cache_key)) {
            $template_overrides = array();
            list($area, $area_type) = $view->getArea();

            foreach (Registry::get('addons') as $addon_name => $_settings) {
                if ($_settings['status'] == 'A') {
                    $tpls = $view->theme->getDirContents(array(
                        'dir' => "{$area_type}/templates/addons/{$addon_name}/overrides/",
                        'get_dirs' => false,
                        'get_files' => true,
                        'recursive' => true,
                    ), Themes::STR_MERGE, Themes::PATH_ABSOLUTE, Themes::USE_BASE);

                    foreach ($tpls as $file_name => $file_info) {
                        $tpl_hash = md5($file_name);
                        if (empty($template_overrides[$tpl_hash])) {
                            $template_overrides[$tpl_hash] = $file_info[Themes::PATH_ABSOLUTE];
                        }
                    }
                }
            }

            if (empty($template_overrides)) {
                $template_overrides['plug'] = true;
            }

            Registry::set($cache_key, $template_overrides);
        }

        $init[$cache_key] = true;
    }

    return Registry::ifGet($cache_key . '.' . md5($resource_name), $resource_name);
}

/**
 * Check if functionality is available for the edition
 *
 * @param string $editions Allowed editions ('ULTIMATE,MULTIVENDOR')
 * @return bool true if available
 */
function fn_allowed_for($editions)
{
    static $cache = array();

    if ($editions == 'TRUNK') {
        return true;
    }

    if (isset($cache[$editions])) {
        return $cache[$editions];
    }

    $is_allowed = false;

    $_mode = fn_get_storage_data('store_mode');

    switch ($_mode) {
        case 'free': {
            $store_mode = array(':FREE');
            $extra = '';
            break;
        }
        case 'full': {
            $store_mode = ':FULL';
            $extra = '';
            break;
        }
        case '': {
            $store_mode = ':';
            $extra = '';
            break;
        }
        case 'ultimate': {
            $store_mode = ':ULTIMATE';
            $extra = '';
            break;
        }
        default: {
            $store_mode = PRODUCT_EDITION == 'MULTIVENDOR' ? ':FULL' : ':ULTIMATE';
            $extra = ':TRIAL';
        }
    }

    foreach (explode(',', $editions) as $edition) {
        if (strpos($edition, ':') !== false) {

            if ($edition == PRODUCT_EDITION . $store_mode || $edition == PRODUCT_EDITION . $store_mode . $extra) {
                $is_allowed = true;
                break;
            }

        } elseif ($edition == PRODUCT_EDITION) {
            $is_allowed = true;
            break;
        }
    }

    $cache[$editions] = $is_allowed;

    return $is_allowed;
}

/**
 * Puts data to storage
 * @param string $key key     Key to store data
 * @param string $data data   Data to store
 * @param bool   $allow_empty When set to false, specifying empty value will remove storage data record.
 * @return integer data ID
 */
function fn_set_storage_data($key, $data = '', $allow_empty = false)
{
    $data_id = 0;
    if (!empty($data) || $allow_empty) {
        $data_id = db_query('REPLACE ?:storage_data (`data_key`, `data`) VALUES(?s, ?s)', $key, $data);
        Registry::set('storage_data.' . $key, $data);
    } else {
        db_query('DELETE FROM ?:storage_data WHERE `data_key` = ?s', $key);
        Registry::del('storage_data.' . $key);
    }

    return $data_id;
}

/**
 * Gets data from storage
 * @param string $key key
 * @return mixed key value
 */
function fn_get_storage_data($key)
{
    if (!Registry::isExist('storage_data.' . $key)) {
        Registry::set('storage_data.' . $key, db_get_field('SELECT `data` FROM ?:storage_data WHERE `data_key` = ?s', $key));
    }

    return Registry::get('storage_data.' . $key);
}

/**
 * Checks is some key is expired (value of given key should be timestamp).
 *
 * @param string $key Key name
 * @param int $time_period Time period (in seconds), that should be added to the current timestamp for the future check.
 * @return boolean True, if saved timestamp is less than current timestamp, false otherwise.
 */
function fn_is_expired_storage_data($key, $time_period = null)
{
    $time = fn_get_storage_data($key);
    if ($time < TIME && $time_period) {
        fn_set_storage_data($key, TIME + $time_period);
    }

    return $time < TIME;
}

/**
 * Removes service parameters from URL
 * @param string $url URL
 * @return string clean URL
 */
function fn_url_remove_service_params($url)
{
    $params = array(
        'is_ajax',
        'callback',
        'full_render',
        'result_ids',
        'init_context',
        'skip_result_ids_check',
        'anchor',
        Tygh::$app['session']->getName()
    );

    array_unshift($params, $url);

    return call_user_func_array('fn_query_remove', $params);
}

/**
 * Gets storefront URL
 * @param string $protocol protocol (http/https/current)
 * @param integer $company_id company ID
 * @return string storefront URL
 */
function fn_get_storefront_url($protocol = 'current', $company_id = 0)
{
    $url = Registry::get('config.' . $protocol . '_location');

    /**
     * Changes storefront URL
     * @param string  $protocol   protocol (http/https/current)
     * @param integer $company_id company ID
     * @param string  $url        storefront URL
     */
    fn_set_hook('get_storefront_url', $protocol, $company_id, $url);

    return $url;
}

/*
 * Gets URI part from REQUEST_URI
 * @param string $request_uri request URI
 * @return mixed URI part on success, boolean false otherwise
 */
function fn_get_request_uri($request_uri)
{
    $url_pattern = @parse_url(urldecode($request_uri));

    if (empty($url_pattern)) {
        $url_pattern = @parse_url($request_uri);
    }

    if (empty($url_pattern)) {
        return false;
    }

    $current_path = Registry::get('config.current_path');
    if (fn_allowed_for('ULTIMATE')) {
        $urls = fn_get_storefront_urls(Registry::get('runtime.company_id'));
        if (!empty($urls)) {
            $current_path = $urls['current_path'];
        }
    }

    return rtrim(substr($url_pattern['path'], strlen($current_path)), '/');
}

/**
 * Checks if correct url was requested
 *
 * @param string $area Area
 * @return boolean Return true if currecnt url requested or requested url was correct, false otherwise
 */
function fn_check_requested_url($area = AREA)
{
    if (!defined('API') && $area == 'C' && !empty($_SERVER['REQUEST_URI']) && !empty($_SERVER['SCRIPT_NAME'])) {
        $request_path = rtrim(@parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        if ($request_path != $_SERVER['SCRIPT_NAME']) {
            $index_script = Registry::get('config.customer_index');
            $current_path = Registry::get('config.current_path');

            return preg_match("!^$current_path(/$index_script)?$!", $request_path);
        }
    }

    return true;
}

/**
 * Gets storefront protocol (depends on security settings)
 * @return string protocol - http or https
 */
function fn_get_storefront_protocol()
{
    static $protocol;

    if (empty($protocol)) {
        $protocol = Registry::get('settings.Security.secure_storefront') == 'full' ? 'https' : 'http';
    }

    return $protocol;
}

/**
 * Clears output buffers contents
 *
 * @return void
 */
function fn_clear_ob()
{
    for ($level = ob_get_level(); $level > 0; --$level) {
        @ob_end_clean() || @ob_clean();
    }
}
