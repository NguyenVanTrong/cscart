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

use Tygh\BlockManager\Layout;
use Tygh\Development;
use Tygh\Embedded;
use Tygh\Exceptions\PHPErrorException;
use Tygh\Exceptions\InitException;
use Tygh\Registry;
use Tygh\Debugger;
use Tygh\Storage;
use Tygh\Settings;
use Tygh\Snapshot;
use Tygh\SmartyEngine\Core as SmartyCore;
use Tygh\Ajax;
use Tygh\Api;
use Tygh\Api\Response;
use Tygh\Themes\Styles;
use Tygh\Tools\DateTimeHelper;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Init template engine
 *
 * @return boolean always true
 */
function fn_init_templater($area = AREA)
{
    $auth = Tygh::$app['session']['auth'];
    $view = new SmartyCore();
    \SmartyException::$escape = false;

    /**
     * Change templater pre-init parameters
     *
     * @param object $view Templater object
     */
    fn_set_hook('init_templater', $view);

    $view->_dir_perms = DEFAULT_DIR_PERMISSIONS;
    $view->_file_perms = DEFAULT_FILE_PERMISSIONS;

    $view->registerResource('tygh', new Tygh\SmartyEngine\FileResource());

    // resource for shared templates loaded from backend
    $view->registerResource('backend', new Tygh\SmartyEngine\BackendResource());

    if ($area == 'A') {

        if (!empty($auth['user_id'])) {
            // Auto-tooltips for admin panel
            $view->registerFilter('pre', array('Tygh\SmartyEngine\Filters', 'preFormTooltip'));

            if (fn_allowed_for('ULTIMATE')) {
                // Enable sharing for objects
                $view->registerFilter('output', array('Tygh\SmartyEngine\Filters', 'outputSharing'));
            }
        }

        $view->registerFilter('pre', array('Tygh\SmartyEngine\Filters', 'preScript'));
    }

    if ($area == 'C') {
        $view->registerFilter('pre', array('Tygh\SmartyEngine\Filters', 'preTemplateWrapper'));

        if (Registry::get('runtime.customization_mode.design')) {
            $view->registerFilter('output', array('Tygh\SmartyEngine\Filters', 'outputTemplateIds'));
        }

        if (Registry::get('runtime.customization_mode.live_editor')) {
            $view->registerFilter('output', array('Tygh\SmartyEngine\Filters', 'outputLiveEditorWrapper'));
        }

        $view->registerFilter('output', array('Tygh\SmartyEngine\Filters', 'outputScript'));
    }

    if (Embedded::isEnabled()) {
        $view->registerFilter('output', array('Tygh\SmartyEngine\Filters', 'outputEmbeddedUrl'));
    }

    // CSRF form protection
    if (fn_is_csrf_protection_enabled($auth)) {
        $view->registerFilter('output', array('Tygh\SmartyEngine\Filters', 'outputSecurityHash'));
    }

    // Language variable retrieval optimization
    $view->registerFilter('post', array('Tygh\SmartyEngine\Filters', 'postTranslation'));

    $smarty_plugins_dir = $view->getPluginsDir();
    $view->setPluginsDir(Registry::get('config.dir.functions') . 'smarty_plugins');
    $view->addPluginsDir($smarty_plugins_dir);

    $view->error_reporting = E_ALL & ~E_NOTICE;

    $view->registerDefaultPluginHandler(array('Tygh\SmartyEngine\Filters', 'smartyDefaultHandler'));

    $view->setArea($area);
    $view->use_sub_dirs = false;
    $view->compile_check = (Development::isEnabled('compile_check') || Debugger::isActive() || fn_is_development()) ? true : false;
    $view->setLanguage(CART_LANGUAGE);

    $view->assign('ldelim', '{');
    $view->assign('rdelim', '}');

    $view->assign('currencies', Registry::get('currencies'), false);
    $view->assign('primary_currency', CART_PRIMARY_CURRENCY, false);
    $view->assign('secondary_currency', CART_SECONDARY_CURRENCY, false);
    $view->assign('languages', Registry::get('languages'));

    if ($area == 'A') {
        $view->assign('addon_permissions_text', fn_get_addon_permissions_text());
    }

    if (!fn_allowed_for('ULTIMATE:FREE')) {
        $view->assign('localizations', fn_get_localizations(CART_LANGUAGE , true));
        if (defined('CART_LOCALIZATION')) {
            $view->assign('localization', fn_get_localization_data(CART_LOCALIZATION));
        }
    }

    if (defined('THEMES_PANEL')) {
        if (fn_allowed_for('ULTIMATE')) {
            $storefronts = db_get_array('SELECT storefront, company, company_id FROM ?:companies');
            Registry::set('demo_theme.storefronts', $storefronts);
        }
        $view->assign('demo_theme', Registry::get('demo_theme'));
    }

    $view->assignByRef('app', Tygh::$app);
    Tygh::$app['view'] = $view;

    /**
     * Change templater parameters
     *
     * @param object $view Templater object
     */
    fn_set_hook('init_templater_post', $view);

    return array(INIT_STATUS_OK);
}

/**
 * Init crypt engine
 *
 * @return boolean always true
 */
function fn_init_crypt()
{
    Tygh::$app['crypt'] = function ($app) {
        return new Crypt_Blowfish(Registry::get('config.crypt_key'));
    };

    return true;
}

/**
 * Init ajax engine
 *
 * @return boolean true if current request is ajax, false - otherwise
 */
function fn_init_ajax()
{
    if (defined('AJAX_REQUEST')) {
        return array(INIT_STATUS_OK);
    }

    Embedded::init();

    if (Ajax::validateRequest($_REQUEST)) {
        Tygh::$app['ajax'] = new Ajax($_REQUEST);
        fn_define('AJAX_REQUEST', true);
    }

    return array(INIT_STATUS_OK);
}

/**
 * Init languages
 *
 * @param array $params request parameters
 * @return boolean always true
 */
function fn_init_language($params, $area = AREA)
{
    $default_language = Registry::get('settings.Appearance.' . fn_get_area_name($area) . '_default_language');
    $session_language = fn_get_session_data('cart_language' . $area);

    $show_hidden_languages = $area != 'C' ? true : false;
    $avail_languages = fn_get_avail_languages($area, $show_hidden_languages);

    if (!empty($params['sl']) && !empty($avail_languages[$params['sl']])) {
        fn_define('CART_LANGUAGE', $params['sl']);
    } elseif ($session_language && !empty($avail_languages[$session_language])) {
        fn_define('CART_LANGUAGE', $session_language);
    } elseif ($_lc = fn_get_browser_language($avail_languages)) {
        fn_define('CART_LANGUAGE', $_lc);
    } elseif (!empty($avail_languages[$default_language])) {
        fn_define('CART_LANGUAGE', $default_language);
    } else {
        reset($avail_languages);
        fn_define('CART_LANGUAGE', key($avail_languages));
    }

    // For the backend, set description language
    if (!empty($params['descr_sl']) && !empty($avail_languages[$params['descr_sl']])) {
        fn_define('DESCR_SL', $params['descr_sl']);
        fn_set_session_data('descr_sl', $params['descr_sl'], COOKIE_ALIVE_TIME);
    } elseif (($d = fn_get_session_data('descr_sl')) && !empty($avail_languages[$d])) {
        fn_define('DESCR_SL', $d);
    } else {
        fn_define('DESCR_SL', CART_LANGUAGE);
    }

    if (CART_LANGUAGE != $session_language) {
        fn_set_session_data('cart_language' . $area, CART_LANGUAGE, COOKIE_ALIVE_TIME);

        // set language_changed flag only if $session_language was set before
        if (Embedded::isEnabled() && defined('AJAX_REQUEST') && $session_language) {
            Tygh::$app['ajax']->assign('language_changed', true);
        }
    }

    Registry::set('languages', $avail_languages);

    return array(INIT_STATUS_OK);
}

/**
 * Init company data
 * Company data array will be saved in the registry runtime.company_data
 *
 * @param array $params request parameters
 * @return array with init data (init status, redirect url in case of redirect)
 */
function fn_init_company_data($params)
{
    $company_data = array(
        'company' => __('all_vendors'),
    );

    $company_id = Registry::get('runtime.company_id');
    if ($company_id) {
        $company_data = fn_get_company_data($company_id);
    }

    fn_set_hook('init_company_data', $params, $company_id, $company_data);

    Registry::set('runtime.company_data', $company_data);

    return array(INIT_STATUS_OK);
}

/**
 * Init selected company
 * Selected company id will be saved in the registry runtime.company_id
 *
 * @param array $params request parameters
 * @return array with init data (init status, redirect url in case of redirect)
 */
function fn_init_company_id(&$params)
{
    $company_id = 0;
    $available_company_ids = array();
    $result = array(INIT_STATUS_OK);

    if (isset($params['switch_company_id'])) {
        $switch_company_id = intval($params['switch_company_id']);
    } else {
        $switch_company_id = false;
    }

    if (defined('API')) {
        $api = Tygh::$app['api'];
        $api_response_status = false;
        if ($api instanceof Api) {
            if (AREA == 'A') {
                if ($user_data = $api->getUserData()) {
                    $company_id = 0;

                    if ($user_data['company_id']) {
                        $company_id = $user_data['company_id'];
                    }

                    $store = array();
                    if (preg_match('/(stores|vendors)\/(\d+)\/.+/', $api->getRequest()->getResource(), $store)) {

                        if ($company_id && $company_id != $store[2]) {
                            $api_response_status = Response::STATUS_FORBIDDEN;
                        }

                        $company_id = intval($store[2]);
                        if (!fn_get_available_company_ids($company_id)) {
                            $company_id = 0;
                        }
                    }
                } else {
                    $api_response_status = Response::STATUS_UNAUTHORIZED;
                }
            }
        } else {
            $api_response_status = Response::STATUS_FORBIDDEN;
        }

        if ($api_response_status) {
            $response = new Response($api_response_status);
            /**
             * Here is exit.
             */
            $response->send();
        }
    }
    // set company_id for vendor's admin
    if (AREA == 'A' && !empty(Tygh::$app['session']['auth']['company_id'])) {
        $company_id = intval(Tygh::$app['session']['auth']['company_id']);
        $available_company_ids = array($company_id);
        if (!fn_get_available_company_ids($company_id)) {
            return fn_init_company_id_redirect($params, 'access_denied');
        }
    }

    // admin switching company_id
    if (!$company_id) {
        if ($switch_company_id !== false) { // request not empty
            if ($switch_company_id) {
                if (fn_get_available_company_ids($switch_company_id)) {
                    $company_id = $switch_company_id;
                } else {
                    return fn_init_company_id_redirect($params, 'company_not_found');
                }
            }
            fn_set_session_data('company_id', $company_id, COOKIE_ALIVE_TIME);
        } else {
            $company_id = fn_init_company_id_find_in_session();
        }
    }

    if (empty($available_company_ids)) {
        $available_company_ids = fn_get_available_company_ids();
    }

    fn_set_hook('init_company_id', $params, $company_id, $available_company_ids, $result);

    Registry::set('runtime.company_id', $company_id);
    Registry::set('runtime.companies_available_count', count($available_company_ids));

    unset($params['switch_company_id']);

    return $result;
}

/**
 * Form error notice and make redirect. Used in fn_init_company_id
 *
 * @param array $params request parameters
 * @param string $message language variable name for message
 * @param int $redirect_company_id New company id for redirecting, if null, company id saved in session will be used
 * @return array with init data (init status, redirect url in case of redirect)
 */
function fn_init_company_id_redirect(&$params, $message, $redirect_company_id = null)
{
    if ('access_denied' == $message) {

        Tygh::$app['session']['auth'] = array();
        $redirect_url = 'auth.login_form' . (!empty($params['return_url']) ? '?return_url=' . urldecode($params['return_url']) : '');

    } elseif ('company_not_found' == $message) {

        $dispatch = !empty($params['dispatch']) ? $params['dispatch'] : 'auth.login_form';
        unset($params['dispatch']);
        $params['switch_company_id'] = (null === $redirect_company_id) ? fn_init_company_id_find_in_session() : $redirect_company_id;

        $redirect_url = $dispatch . '?' . http_build_query($params);
    }

    if (!defined('CART_LANGUAGE')) {
        fn_init_language($params); // we need CART_LANGUAGE in Tygh\Languages\Values::getLangVar()
        fn_init_currency($params); // we need CART_SECONDARY_CURRENCY in Tygh\Languages\Values::getLangVar()
        $params['dispatch'] = 'index.index'; // we need dispatch in Tygh\Languages\Values::getLangVar()
    }
    fn_set_notification('E', __('error'), __($message));

    return array(INIT_STATUS_REDIRECT, $redirect_url);
}

/**
 * Tryes to find company id in session
 *
 * @return int Company id if stored in session, 0 otherwise
 */
function fn_init_company_id_find_in_session()
{
    $session_company_id = intval(fn_get_session_data('company_id'));
    if ($session_company_id && !fn_get_available_company_ids($session_company_id)) {
        fn_delete_session_data('company_id');
        $session_company_id = 0;
    }

    return $session_company_id;
}

/**
 * Init currencies
 *
 * @param array $params request parameters
 * @param  string $area Area ('A' for admin or 'C' for customer)
 * @return boolean always true
 */
function fn_init_currency($params, $area = AREA)
{
    /**
     * Performs actions before initializing currencies
     *
     * @param array $params request parameters
     * @param string $area Area ('A' for admin or 'C' for customer)
     */
    fn_set_hook('init_currency_pre', $params, $area);

    $_params = array();
    if (fn_allowed_for('ULTIMATE:FREE')) {
        $_params['only_primary'] = 'Y';
    } else {
        $_params['status'] = array('A', 'H');
    }

    $currencies = fn_get_currencies_list($_params, $area, CART_LANGUAGE);

    $primary_currency = '';

    foreach ($currencies as $v) {
        if ($v['is_primary'] == 'Y') {
            $primary_currency = $v['currency_code'];
            break;
        }
    }

    if (empty($primary_currency)) { // Restore primary currency if it empty
        $primary_currencies = fn_get_currencies_list(
            array('only_primary' => true, 'raw_query' => true), $area, CART_LANGUAGE
        );
        foreach ($primary_currencies as $key => $currency) {
            $primary_currencies[$key]['status'] = 'H'; // Hide unavailable currencies
        }
        $currencies = fn_sort_array_by_key($currencies + $primary_currencies, 'position');
        $primary_currency = key($primary_currencies);
    }

    if (!empty($params['currency']) && !empty($currencies[$params['currency']])) {
        $secondary_currency = $params['currency'];
    } elseif (($c = fn_get_session_data('secondary_currency' . $area)) && !empty($currencies[$c])) {
        $secondary_currency = $c;
    } else {
        $secondary_currency = $primary_currency;
    }

    if (empty($secondary_currency)) {
        reset($currencies);
        $secondary_currency = key($currencies);
    }

    if ($secondary_currency != fn_get_session_data('secondary_currency' . $area)) {
        fn_set_session_data('secondary_currency' . $area, $secondary_currency, COOKIE_ALIVE_TIME);
    }

    // Hide secondary currency in frontend if it is hidden
    if ($area == 'C' && $currencies[$secondary_currency]['status'] != 'A') {
        $first_currency = '';
        foreach ($currencies as $key => $currency) {
            if ($currency['status'] != 'A' && $currency['is_primary'] != 'Y') {
                unset($currencies[$key]);
            } elseif ($currency['status'] == 'A' && !$first_currency) {
                $first_currency = $currency;
            }
        }
        $secondary_currency = $first_currency['currency_code'];
    }

    /**
     * Sets currencies
     *
     * @param array $params request parameters
     * @param string $area Area ('A' for admin or 'C' for customer)
     * @param string $primary_currency Primary currency code
     * @param string $secondary_currency Secondary currency code
     */
    fn_set_hook('init_currency_post', $params, $area, $primary_currency, $secondary_currency);

    define('CART_PRIMARY_CURRENCY', $primary_currency);
    define('CART_SECONDARY_CURRENCY', $secondary_currency);

    Registry::set('currencies', $currencies);

    return array(INIT_STATUS_OK);
}

/**
 * Init layout
 *
 * @param array $params request parameters
 * @return boolean always true
 */
function fn_init_layout($params)
{
    if (fn_allowed_for('ULTIMATE')) {
        if (!Registry::get('runtime.company_id') && !Registry::get('runtime.simple_ultimate')) {
            return array(INIT_STATUS_OK);
        }
    }

    $key_name = 'stored_layout' . (Embedded::isEnabled() ? '_embedded' : '');
    $stored_layout = fn_get_session_data($key_name);

    if (!empty($params['s_layout'])) {
        $stored_layout = $params['s_layout'];

        fn_set_session_data($key_name, $params['s_layout']);
    }

    // Replace default theme with selected for current area
    if (!empty($stored_layout)) {
        $layout = Layout::instance()->get($stored_layout);

        if (!isset($layout['theme_name']) || $layout['theme_name'] != fn_get_theme_path('[theme]', 'C')) {
            unset($layout);
        }
    }

    if (empty($layout)) {
        $layout = Layout::instance()->getDefault(); // get default
    }

    $available_styles = Styles::factory($layout['theme_name'])->getList(array(
        'short_info' => true
    ));

    if (!isset($available_styles[$layout['style_id']])) {
        $layout['style_id'] = Styles::factory($layout['theme_name'])->getDefault();
    }

    Registry::set('runtime.layout', $layout);

    return array(INIT_STATUS_OK);
}

/**
 * Init user
 *
 * @return boolean always true
 */
function fn_init_user($area = AREA)
{
    $user_info = array();
    if (!empty(Tygh::$app['session']['auth']['user_id'])) {
        $user_info = fn_get_user_short_info(Tygh::$app['session']['auth']['user_id']);
        if (empty($user_info)) { // user does not exist in the database, but exists in session
            Tygh::$app['session']['auth'] = array();
        } else {
            Tygh::$app['session']['auth']['usergroup_ids'] = fn_define_usergroups(array(
                'user_id' => Tygh::$app['session']['auth']['user_id'],
                'user_type' => $user_info['user_type']
            ));
        }
    }

    $first_init = false;
    if (empty(Tygh::$app['session']['auth'])) {

        $udata = array();
        $user_id = fn_get_session_data($area . '_user_id');

        if ($area == 'A' && defined('CONSOLE')) {
            $user_id = 1;
        }

        if ($user_id) {
            fn_define('LOGGED_VIA_COOKIE', true);
        }

        fn_login_user($user_id);

        if (!defined('NO_SESSION')) {
            Tygh::$app['session']['cart'] = isset(Tygh::$app['session']['cart']) ? Tygh::$app['session']['cart'] : array();
        }

        if ((defined('LOGGED_VIA_COOKIE') && !empty(Tygh::$app['session']['auth']['user_id'])) || ($cu_id = fn_get_session_data('cu_id'))) {
            $first_init = true;
            if (!empty($cu_id)) {
                fn_define('COOKIE_CART' , true);
            }

            // Cleanup cached shipping rates

            unset(Tygh::$app['session']['shipping_rates']);

            $_utype = empty(Tygh::$app['session']['auth']['user_id']) ? 'U' : 'R';
            $_uid = empty(Tygh::$app['session']['auth']['user_id']) ? $cu_id : Tygh::$app['session']['auth']['user_id'];
            fn_extract_cart_content(Tygh::$app['session']['cart'], $_uid , 'C' , $_utype);
            fn_save_cart_content(Tygh::$app['session']['cart'] , $_uid , 'C' , $_utype);
            if (!empty(Tygh::$app['session']['auth']['user_id'])) {
                Tygh::$app['session']['cart']['user_data'] = fn_get_user_info(Tygh::$app['session']['auth']['user_id']);
                $user_info = fn_get_user_short_info(Tygh::$app['session']['auth']['user_id']);
            }
        }
    }

    if (fn_is_expired_storage_data('cart_products_next_check', SECONDS_IN_HOUR * 12)) {
        db_query("DELETE FROM ?:user_session_products WHERE user_type = 'U' AND timestamp < ?i", (TIME - SECONDS_IN_DAY * 30));
    }

    if (!fn_allowed_for('ULTIMATE:FREE')) {
        // If administrative account has usergroup, it means the access restrictions are in action
        if ($area == 'A' && !empty(Tygh::$app['session']['auth']['usergroup_ids'])) {
            fn_define('RESTRICTED_ADMIN', true);
        }
    }

    if (!empty($user_info) && $user_info['user_type'] == 'A' && (empty($user_info['company_id']) || (fn_allowed_for('ULTIMATE') && $user_info['company_id'] == Registry::get('runtime.company_id')))) {
        $customization_mode = fn_array_combine(explode(',', Registry::get('settings.customization_mode')), true);
        if (!empty($customization_mode)) {
            Registry::set('runtime.customization_mode', $customization_mode);

            if ($area == 'A' || Embedded::isEnabled()) {
                Registry::set('runtime.customization_mode.live_editor', false);
            }
        }
    }

    fn_set_hook('user_init', Tygh::$app['session']['auth'], $user_info, $first_init);

    Registry::set('user_info', $user_info);

    return array(INIT_STATUS_OK);
}

/**
 * Init localizations
 *
 * @param array $params request parameters
 * @return boolean true if localizations exists, false otherwise
 */
function fn_init_localization($params)
{
    if (AREA != 'C') {
        return array(INIT_STATUS_OK);
    }

    $locs = db_get_hash_array("SELECT localization_id, custom_weight_settings, weight_symbol, weight_unit FROM ?:localizations WHERE status = 'A'", 'localization_id');

    if (!empty($locs)) {
        if (!empty($_REQUEST['lc']) && !empty($locs[$_REQUEST['lc']])) {
            $cart_localization = $_REQUEST['lc'];

        } elseif (($l = fn_get_session_data('cart_localization')) && !empty($locs[$l])) {
            $cart_localization = $l;

        } else {
            $_ip = fn_get_ip(true);
            $_country = fn_get_country_by_ip($_ip['host']);
            $_lngs = db_get_hash_single_array("SELECT lang_code, 1 as 'l' FROM ?:languages WHERE status = 'A'", array('lang_code', 'l'));
            $_language = fn_get_browser_language($_lngs);

            $cart_localization = db_get_field("SELECT localization_id, COUNT(localization_id) as c FROM ?:localization_elements WHERE (element = ?s AND element_type = 'C') OR (element = ?s AND element_type = 'L') GROUP BY localization_id ORDER BY c DESC LIMIT 1", $_country, $_language);

            if (empty($cart_localization) || empty($locs[$cart_localization])) {
                $cart_localization = db_get_field("SELECT localization_id FROM ?:localizations WHERE status = 'A' AND is_default = 'Y'");
            }
        }

        if (empty($cart_localization)) {
            reset($locs);
            $cart_localization = key($locs);
        }

        if ($cart_localization != fn_get_session_data('cart_localization')) {
            fn_set_session_data('cart_localization', $cart_localization, COOKIE_ALIVE_TIME);
        }

        if ($locs[$cart_localization]['custom_weight_settings'] == 'Y') {
            Registry::set('config.localization.weight_symbol', $locs[$cart_localization]['weight_symbol']);
            Registry::set('config.localization.weight_unit', $locs[$cart_localization]['weight_unit']);
        }

        fn_define('CART_LOCALIZATION', $cart_localization);
    }

    return array(INIT_STATUS_OK);
}

/**
 * Detect user agent
 *
 * @return boolean true always
 */
function fn_init_ua()
{
    static $crawlers = array(
        'google', 'bot', 'yahoo',
        'spider', 'archiver', 'curl',
        'python', 'nambu', 'Twitterbot',
        'perl', 'sphere', 'PEAR',
        'java', 'wordpress', 'radian',
        'crawl', 'yandex', 'eventbox',
        'monitor', 'mechanize', 'facebookexternal'
    );

    $http_ua = fn_strtolower($_SERVER['HTTP_USER_AGENT']);

    if (strpos($http_ua, 'shiretoko') !== false || strpos($http_ua, 'firefox') !== false) {
        $ua = 'firefox';
    } elseif (strpos($http_ua, 'chrome') !== false) {
        $ua = 'chrome';
    } elseif (strpos($http_ua, 'safari') !== false) {
        $ua = 'safari';
    } elseif (strpos($http_ua, 'opera') !== false) {
        $ua = 'opera';
    } elseif (strpos($http_ua, 'msie') !== false || strpos($http_ua, 'trident/7.0; rv:11.0') !== false) {
        // IE11 does not send normal headers and seems like Mozilla:
        // Mozilla/5.0 (Windows NT 6.1; Trident/7.0; rv:11.0) like Gecko
        $ua = 'ie';
        if (preg_match("/msie (6|7|8)/i", $http_ua)) {
            Registry::set('runtime.unsupported_browser', true);
        }
    } elseif (preg_match('/(' . implode('|', $crawlers) . ')/', $http_ua, $m)) {
        $ua = 'crawler';
        fn_define('CRAWLER', $m[1]);
        fn_define('NO_SESSION', true); // do not start session for crawler
    } else {
        $ua = 'unknown';
    }

    if (isset($_REQUEST['no_session']) && $_REQUEST['no_session'] === 'Y') {
        fn_define('NO_SESSION', true);
    }

    fn_define('USER_AGENT', $ua);

    return array(INIT_STATUS_OK);
}

function fn_check_cache($params)
{
    $regenerated = true;
    $dir_root = Registry::get('config.dir.root') . '/';

    if (isset($params['ct']) && ((AREA == 'A' && !(fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id'))) || Debugger::isActive() || fn_is_development())) {
        Storage::instance('images')->deleteDir('thumbnails');
    }

    // Clean up cache
    if (isset($params['cc']) && ((AREA == 'A' && !(fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id'))) || Debugger::isActive() || fn_is_development())) {
        fn_clear_cache();
    }

    // Clean up templates cache
    if (isset($params['ctpl']) && ((AREA == 'A' && !(fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id'))) || Debugger::isActive() || fn_is_development())) {
        fn_clear_template_cache();
    }

    if (!in_array(AREA, array('A', 'V'))) {
        return array(INIT_STATUS_OK);
    }

    /* Add extra files for cache checking if needed */
    $core_hashes = array(
        '142c370724fbe6f8d149925ad3f8433e9e24c6ee' => array(
            'file' => 'cuc.xfrqcyrU/utlG/ccn',
            'notice' => 'nqzva_cnary_jvyy_or_oybpxrq'
        ),
        '04f02438edef1b34cce088e6b48204d2fd9adb7b' => array(
            'file' => 'cuc.8sgh/ergeriabp_ynergvy/fnzrupf/ccn',
            'notice' => 'nqzva_cnary_jvyy_or_oybpxrq'
        ),
    );

    foreach ($core_hashes as $hash => $file) {

        if ($hash != sha1_file($dir_root . strrev(str_rot13($file['file'])))) {
            if (filemtime($dir_root . strrev(str_rot13($file['file']))) < TIME - SECONDS_IN_DAY * 2) { // 2-days cache
                Tygh::$app['cache']->regenerate($hash, $file['file']);
            } else {
                $regenerated = false;
            }

            fn_process_cache_notifications($file['notice']);

            break;
        }
    }

    return array(INIT_STATUS_OK);
}

function fn_init_settings()
{
    Registry::registerCache('settings', array('settings_objects', 'settings_vendor_values', 'settings_descriptions', 'settings_sections', 'settings_variants'), Registry::cacheLevel('static'));
    if (Registry::isExist('settings') == false) {
        $settings = Settings::instance()->getValues();

        // Deprecated: workaround for old security settings
        $settings['Security']['secure_auth'] = $settings['Security']['secure_storefront'] == 'partial' ? 'Y' : 'N';
        $settings['Security']['secure_checkout'] = $settings['Security']['secure_storefront'] == 'partial' ? 'Y' : 'N';

        Registry::set('settings', $settings);
    }

    //initialization remote settings for compatibility with third-party addons
    //deprecated settings
    $default_elements = Registry::get('settings.Appearance.admin_elements_per_page');
    Registry::set('settings.Appearance.admin_products_per_page', $default_elements);
    Registry::set('settings.Appearance.admin_orders_per_page', $default_elements);
    Registry::set('settings.Appearance.admin_pages_per_page', $default_elements);

    fn_init_time_zone(Registry::get('settings.Appearance.timezone'));

    fn_define('DEFAULT_LANGUAGE', Registry::get('settings.Appearance.backend_default_language'));

    return array(INIT_STATUS_OK);
}

/**
 * Sets the given timezone as the PHP runtime timezone and as the current MySQL connection timezone.
 *
 * @param string $time_zone_name The name of a timezone like "Europe/London"
 */
function fn_init_time_zone($time_zone_name)
{
    $valid_timezone_identifiers = timezone_identifiers_list();

    if (is_array($valid_timezone_identifiers) && in_array($time_zone_name, $valid_timezone_identifiers)) {
        date_default_timezone_set($time_zone_name);
        $offset_string = DateTimeHelper::getTimeZoneOffsetString($time_zone_name);

        if ($offset_string) {
            db_query('SET time_zone = ?s', $offset_string);
        } else {
            error_log("Unable to get timezone offset for {$time_zone_name}");
        }
    }
}

/**
 * Initialize all enabled addons
 *
 * @return array INIT_STATUS_OK
 */
function fn_init_addons()
{
    Registry::registerCache(
        'addons',
        array(
            'addons', 'settings_objects', 'settings_vendor_values',
            'settings_descriptions', 'settings_sections', 'settings_variants'
        ),
        Registry::cacheLevel('static')
    );

    if (Registry::isExist('addons') == false) {
        $init_addons = Registry::get('settings.init_addons');
        $allowed_addons = null;

        if ($init_addons == 'none') {
            $allowed_addons = array();
        } elseif ($init_addons == 'core') {
            $allowed_addons = Snapshot::getCoreAddons();
        }

        $_addons = db_get_hash_array("SELECT addon, priority, status, unmanaged FROM ?:addons WHERE 1 ORDER BY priority", 'addon');

        foreach ($_addons as $k => $v) {
            $_addons[$k] = Settings::instance()->getValues($v['addon'], Settings::ADDON_SECTION, false);
            if (fn_check_addon_snapshot($k)) {
                $_addons[$k]['status'] = $v['status'];
            } else {
                $_addons[$k]['status'] = 'D';
            }

            if ($allowed_addons !== null && !in_array($v['addon'], $allowed_addons)) {
                $_addons[$k]['status'] = 'D';
            }

            $_addons[$k]['priority'] = $v['priority'];
            $_addons[$k]['unmanaged'] = $v['unmanaged'];
        }

        // Some addons could be disabled for vendors.
        if (fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id')) {
            Registry::set('addons', $_addons);

            // So, we have to parse it one more time
            foreach ($_addons as $k => $v) {
                // and check permissions schema.
                // We couldn't make it in the previous cycle because the fn_get_scheme func works only with full list of addons.
                if (!fn_check_addon_permission($k)) {
                    unset($_addons[$k]);
                }
            }
        }

        Registry::set('addons', $_addons);
    }

    foreach ((array) Registry::get('addons') as $addon_name => $data) {
        if (empty($data['status'])) {
            // FIX ME: Remove me
            error_log("ERROR: Addons initialization: Bad '$addon_name' addon data:" . serialize($data) . " Addons Registry:" . serialize(Registry::get('addons')));
        }
        if (!empty($data['status']) && $data['status'] == 'A') {
            fn_load_addon($addon_name);
        }
    }

    Registry::set('addons_initiated', true, true);

    return array(INIT_STATUS_OK);
}

/**
 * Initialize unmanaged addons
 *
 * @return array INIT_STATUS_OK
 */
function fn_init_unmanaged_addons()
{
    // Do not use cache here, because company ID is not initialized yet
    $addons = db_get_fields("SELECT addon FROM ?:addons WHERE unmanaged = 1 AND status = 'A' ORDER BY priority");

    foreach ($addons as $addon_name) {
        fn_load_addon($addon_name);
    }

    return array(INIT_STATUS_OK);
}

function fn_init_full_path($request)
{
    // Display full paths cresecure payment processor
    if (isset($request['display_full_path']) && ($request['display_full_path'] == 'Y')) {
        define('DISPLAY_FULL_PATHS', true);
        Registry::set('config.full_host_name', (defined('HTTPS') ? 'https://' . Registry::get('config.https_host') : 'http://' . Registry::get('config.http_host')));
    } else {
        Registry::set('config.full_host_name', '');
    }

    return array(INIT_STATUS_OK);
}

function fn_init_stack()
{
    $stack = Registry::get('init_stack');
    if (empty($stack)) {
        $stack = array();
    }

    $stack_data = func_get_args();

    foreach ($stack_data as $data) {
        $stack[] = $data;
    }

    Registry::set('init_stack', $stack);

    return true;
}

/**
 * Run init functions
 *
 * @param array $request $_REQUEST global variable
 * @return bool always true
 */
function fn_init(&$request)
{
    // New init functions can be added to stack while init
    while ($stack = Registry::get('init_stack')) {
        $function_data = array_shift($stack);
        $function = array_shift($function_data);

        // Remove function from stack
        Registry::set('init_stack', $stack);

        if (!is_callable($function)) {
            continue;
        }

        $result = call_user_func_array($function, $function_data);

        $status = !empty($result[0]) ? $result[0] : INIT_STATUS_OK;
        $url = !empty($result[1]) ? $result[1] : '';
        $message = !empty($result[2]) ? $result[2] : '';
        $permanent = !empty($result[3]) ? $result[3] : '';

        if ($status == INIT_STATUS_OK && !empty($url)) {
            $redirect_url = $url;

        } elseif ($status == INIT_STATUS_REDIRECT && !empty($url)) {
            $redirect_url = $url;
            break;

        } elseif ($status == INIT_STATUS_FAIL) {
            if (empty($message)) {
                $message = 'Initialization failed in <b>' . (is_array($function) ? implode('::', $function) : $function) . '</b> function';
            }

            throw new InitException($message);
        }
    }

    if (!empty($redirect_url)) {
        if (!defined('CART_LANGUAGE')) {
            fn_init_language($request); // we need CART_LANGUAGE in fn_url function that called in fn_redirect
        }
        fn_redirect($redirect_url, true, !empty($permanent));
    }

    Debugger::init(true);

    return true;
}

/**
 * Init paths for storage store data (mse, saas)
 *
 * @return boolean true always
 */
function fn_init_storage()
{
    fn_set_hook('init_storage');

    $storage = Settings::instance()->getValue('storage', '');

    Registry::set('runtime.storage', unserialize($storage));

    Registry::set('config.images_path', Storage::instance('images')->getUrl()); // FIXME this path should be removed

    return array(INIT_STATUS_OK);
}

/**
 * Init api object and put it to Application container.
 */
function fn_init_api()
{
    Tygh::$app['api'] = new Api();

    return array(INIT_STATUS_OK);
}

/**
 * Registers image manipulation library object at Application container.
 *
 * @return array
 */
function fn_init_imagine()
{
    Tygh::$app['image'] = function ($app) {

        $driver = Registry::ifGet('config.tweaks.image_resize_lib', 'gd');

        if ($driver == 'auto') {
            try {
                return new Imagine\Imagick\Imagine();
            } catch (\Exception $e) {
                try {
                    return new Imagine\Gd\Imagine();
                } catch (\Exception $e) {
                    return null;
                }
            }
        } else {
            switch ($driver) {
                case 'gd':
                    return new Imagine\Gd\Imagine();
                    break;
                case 'imagick':
                    return new Imagine\Imagick\Imagine();
                    break;
            }
        }
    };

    return array(INIT_STATUS_OK);
}

/**
 * Registers archiver object at Application container.
 *
 * @return array
 */
function fn_init_archiver()
{
    Tygh::$app['archiver'] = function ($app) {
        return new \Tygh\Tools\Archiver();
    };

    return array(INIT_STATUS_OK);
}

/**
 * Registers custom error handlers
 *
 * @return array
 */
function fn_init_error_handler()
{
    // Fatal error handler
    defined('AREA') && AREA == 'C' && register_shutdown_function(function () {
        $error = error_get_last();

        // Check whether error is fatal (i.e. couldn't have been catched with trivial error handler)
        if (isset($error['type']) &&
            in_array($error['type'], array(
                E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING
            ))
        ) {
            // Try to hide PHP's fatal error message
            fn_clear_ob();

            $exception = new PHPErrorException($error['message'], $error['type'], $error['file'], $error['line']);
            $exception->output();

            exit(1);
        }
    });

    // Non-fatal errors, warnings and notices are caught and properly formatted
    defined('DEVELOPMENT')
    && DEVELOPMENT
    && !extension_loaded('xdebug')
    && set_error_handler(function($code, $message, $filename, $line) {
        if (error_reporting() & $code) {
            switch ($code) {
                // Non-fatal errors, code execution wouldn't be stopped
                case E_NOTICE:
                case E_USER_NOTICE:
                case E_WARNING:
                case E_USER_WARNING:
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                    $exception = new PHPErrorException($message, $code, $filename, $line);
                    $exception->output();

                    error_log(addslashes((string) $exception), 0);

                    return true;
                break;
            }
        }

        // Let PHP's internal error handler handle other cases
        return false;
    });

    return array(INIT_STATUS_OK);
}
