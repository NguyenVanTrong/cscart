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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/*
 * Static options
 */

define('NEW_FEATURE_GROUP_ID', 'OG');

// These constants define when select box with categories list should be replaced with picker
define('CATEGORY_THRESHOLD', 100); // if number of categories less than this value, all categories will be retrieved, otherwise subcategories will be retrieved by ajax
define('CATEGORY_SHOW_ALL', 100);  // if number of categories less than this value, categories tree will be expanded

// These constants define when select box with pages list should be replaced with picker
define('PAGE_THRESHOLD', 40); // if number of pages less than this value, all pages will be retrieved, otherwise subpages will be retrieved by ajax
define('PAGE_SHOW_ALL', 100); // if number of pages less than this value, pages tree will be expanded

// These constants define when select box with product feature variants list should be replaced with picker
define('PRODUCT_FEATURE_VARIANTS_THRESHOLD', 40); // if number of product feature variants less than this value, all product feature variants will be retrieved, otherwise product features variants will be retrieved by ajax

// Maximum number of recently viewed products, stored in session
define('MAX_RECENTLY_VIEWED', 10);

// The maximum allowed value of the "Initial order ID value" in Settings: General
define('MAX_INITIAL_ORDER_ID', 10000000);

// Number of product features per page to display on product details page in admin panel
define('PRODUCT_FEATURES_THRESHOLD', 50);

// Week days
define('SUNDAY',    0);
define('MONDAY',    1);
define('TUESDAY',   2);
define('WEDNESDAY', 3);
define('THURSDAY',  4);
define('FRIDAY',    5);
define('SATURDAY',  6);

// statuses definitions
define('STATUSES_ORDER', 'O');
define('STATUSES_SHIPMENT', 'S');

define('STATUS_INCOMPLETED_ORDER', 'N');
define('STATUS_PARENT_ORDER', 'T');
define('STATUS_BACKORDERED_ORDER', 'B');
define('STATUS_CANCELED_ORDER', 'I');

//Login statuses
define('LOGIN_STATUS_USER_NOT_FOUND', '0');
define('LOGIN_STATUS_OK', '1');
define('LOGIN_STATUS_USER_DISABLED', '2');

// usergroup definitions
define('ALLOW_USERGROUP_ID_FROM', 3);
define('ALL_USERGROUPS', -1);
define('USERGROUP_ALL', 0);
define('USERGROUP_GUEST', 1);
define('USERGROUP_REGISTERED', 2);

// Authentication settings
define('USER_PASSWORD_LENGTH', '8');

// SEF urls delimiter
define('SEO_DELIMITER', '-');

// Number of seconds in one hour (for different calculations)
define('SECONDS_IN_HOUR', 60 * 60); // one hour

// Number of seconds in one day (for different calculations)
define('SECONDS_IN_DAY', SECONDS_IN_HOUR * 24); // one day

// Live time for permanent cookies (currency, language, etc...)
define('COOKIE_ALIVE_TIME', SECONDS_IN_DAY * 7); // one week

// Session live time
define('SESSION_ALIVE_TIME', SECONDS_IN_HOUR * 2); // 2 hours

// Sessions storage live time
define('SESSIONS_STORAGE_ALIVE_TIME',  SECONDS_IN_DAY * 7 * 2); // 2 weeks

// Number of seconds after last session update, while user considered as online
define('SESSION_ONLINE', 60 * 5); // 5 minutes

// Number of seconds before installation script will be redirected to itself to avoid server timeouts
define('INSTALL_DB_EXECUTION', SECONDS_IN_HOUR); // 1 hour

//Uncomment to enable the developer tools: debugger, PHP and SQL loggers, etc.
//define('DEBUG_MODE', true);

//Uncomment to enable error reporting.
//define('DEVELOPMENT', true);

// Theme description file name
define('THEME_MANIFEST', 'manifest.json');
define('THEME_MANIFEST_INI', 'manifest.ini');

// Controller return statuses
define('CONTROLLER_STATUS_REDIRECT', 302);
define('CONTROLLER_STATUS_OK', 200);
define('CONTROLLER_STATUS_NO_PAGE', 404);
define('CONTROLLER_STATUS_DENIED', 403);
define('CONTROLLER_STATUS_DEMO', 401);

define('INIT_STATUS_OK', 1);
define('INIT_STATUS_REDIRECT', 2);
define('INIT_STATUS_FAIL', 3);

define('PLACE_ORDER_STATUS_OK', 1);
define('PLACE_ORDER_STATUS_TO_CART', 2);
define('PLACE_ORDER_STATUS_DENIED', 3);

// Maximum number of items in "Last edited items" list (in the backend)
define('LAST_EDITED_ITEMS_COUNT', 10);

// Meta description auto generation
define('AUTO_META_DESCRIPTION', true);

// Database default tables prefix
define('DEFAULT_TABLE_PREFIX', 'cscart_');

define('CS_PHP_VERSION', phpversion());

// Product information
define('PRODUCT_NAME', 'CS-Cart');
define('PRODUCT_VERSION', '4.5.1.SP1');
define('PRODUCT_STATUS', '');


define('PRODUCT_EDITION', 'ULTIMATE');
define('PRODUCT_BUILD', '');


if (!defined('ACCOUNT_TYPE')) {
    define('ACCOUNT_TYPE', 'customer');
}

//Popularity rating
define('POPULARITY_VIEW', 3);
define('POPULARITY_ADD_TO_CART', 5);
define('POPULARITY_DELETE_FROM_CART', 5);
define('POPULARITY_BUY', 10);

define('MAILING_LIST_ID', 1);

// Session options
// define('SESS_VALIDATE_IP', true); // link session ID with ip address
define('SESS_VALIDATE_UA', true); // link session ID with user-agent

define('BILLING_ADDRESS_PREFIX', 'b');
define('SHIPPING_ADDRESS_PREFIX', 's');

define('DB_MAX_ROW_SIZE', 10000);
define('DB_ROWS_PER_PASS', 400);

/*
 * Dynamic options
 */
$config = array();

$config['dir'] = array(
    'root' => DIR_ROOT,
    'functions' => DIR_ROOT . '/app/functions/',
    'lib' => DIR_ROOT . '/app/lib/',
    'addons' => DIR_ROOT . '/app/addons/',
    'design_frontend' => DIR_ROOT . '/design/themes/',
    'design_backend' => DIR_ROOT . '/design/backend/',
    'payments' => DIR_ROOT . '/app/payments/',
    'schemas' => DIR_ROOT . '/app/schemas/',
    'themes_repository' => DIR_ROOT . '/var/themes_repository/',
    'database' => DIR_ROOT . '/var/database/',
    'var' => DIR_ROOT . '/var/',
    'upgrade' => DIR_ROOT . '/var/upgrade/',
    'cache_templates' => DIR_ROOT . '/var/cache/templates/',
    'cache_mail_templates' => DIR_ROOT . '/var/cache/templates/mail/',
    'cache_twig_templates' => DIR_ROOT . '/var/cache/templates/twig/',
    'cache_registry' => DIR_ROOT . '/var/cache/registry/',
    'files' => DIR_ROOT . '/var/files/',
    'cache_misc' => DIR_ROOT . '/var/cache/misc/',
    'cache_static' => DIR_ROOT . '/var/cache/static/',
    'layouts' => DIR_ROOT . '/var/layouts/',
    'snapshots' => DIR_ROOT . '/var/snapshots/',
    'lang_packs' => DIR_ROOT . '/var/langs/',
    'certificates' => DIR_ROOT . '/var/certificates/',
    'store_import' => DIR_ROOT . '/var/store_import/',
    'backups' => DIR_ROOT . '/var/backups/',
);

// List of forbidden file extensions (for uploaded files)
$config['forbidden_file_extensions'] = array (
    'php',
    'php3',
    'pl',
    'com',
    'exe',
    'bat',
    'cgi',
    'htaccess'
);

$config['forbidden_mime_types'] = array (
    'text/x-php',
    'text/x-perl',
    'text/x-python',
    'text/x-shellscript'
);

$config['js_css_cache_msg'] = "/*
ATTENTION! Please do not modify this file, it's auto-generated and all your changes will be lost.
The complete list of files it's generated from:
[files]
*/

";

$config['base_theme'] = 'responsive';

// FIXME: backward compatibility
// Updates server address
$config['updates_server'] = 'http://updates.cs-cart.com';

// external resources, related to product
$config['resources'] = array(
    'knowledge_base' => 'http://kb.cs-cart.com/installation',
    'updates_server' => 'http://updates.cs-cart.com',
    'twitter' => 'cscart',
    'feedback_api' => 'http://www.cs-cart.com/index.php?dispatch=feedback',
    'product_url' => 'https://www.cs-cart.com',
    'helpdesk_url' => 'https://www.cs-cart.com/helpdesk',
    'license_url' => 'https://www.cs-cart.com/licenses.html',
    'ultimate_license_url' => 'https://www.cs-cart.com/cs-cart-ultimate-license.html',
    'standard_license_url' => 'https://www.cs-cart.com/cs-cart-license.html',
    'storefront_license_url' => 'https://www.cs-cart.com/cs-cart-storefront-license.html',
    'marketplace_url' => 'http://marketplace.cs-cart.com',
    'admin_protection_url' => 'http://kb.cs-cart.com/adminarea-protection',
    'widget_mode_url' => 'http://kb.cs-cart.com/widget-mode',
    'upgrade_center_specialist_url' => 'http://marketplace.cs-cart.com/developers-catalog.html?services=M',
    'upgrade_center_team_url' => 'https://www.cs-cart.com/index.php?dispatch=communication.tickets&submit_ticket=Y',
    'kb_https_failed_url' => 'http://kb.cs-cart.com/secure-connection-failed',
    'curl_error_interpretation' => 'http://curl.haxx.se/libcurl/c/libcurl-errors.html',
    'product_buy_url' => 'https://www.cs-cart.com/cscart.html?utm_source=trial',
    'bug_tracker_url' => 'http://forum.cs-cart.com/index.php?app=tracker&module=post&section=post&do=postnew&pid=11&new_module_versions_id=138',
    'docs_guideline' => 'http://docs.cs-cart.com/4.4.x/developer_guide/getting_started/guidelines.html'
);

// Debugger token
$config['debugger_token'] = 'debug';

// Get local configuration
require_once($config['dir']['root'] . '/config.local.php');

// Backward compatibility
if (!empty($config['saas_uid']) && empty($config['store_prefix'])) {
    $config['store_prefix'] = $config['saas_uid'];
}

// Define host directory depending on the current connection
$config['current_path'] = (defined('HTTPS')) ? $config['https_path'] : $config['http_path'];

$config['http_location'] = 'http://' . $config['http_host'] . $config['http_path'];
$config['https_location'] = 'https://' . $config['https_host'] . $config['https_path'];
$config['current_location'] = (defined('HTTPS')) ? $config['https_location'] : $config['http_location'];
$config['current_host'] = (defined('HTTPS')) ? $config['https_host'] : $config['http_host'];

$config['allowed_pack_exts'] = array('tgz', 'gz', 'zip');

return $config;
