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

use Tygh\Bootstrap;
use Tygh\Debugger;
use Tygh\Registry;

// Register autoloader
$this_dir = dirname(__FILE__);
$classLoader = require($this_dir . '/app/lib/vendor/autoload.php');
$classLoader->add('Tygh', $this_dir . '/app');
class_alias('\Tygh\Tygh', 'Tygh');

// Prepare environment and process request vars
list($_REQUEST, $_SERVER, $_GET, $_POST) = Bootstrap::initEnv($_GET, $_POST, $_SERVER, $this_dir);

// Get config data
$config = require(DIR_ROOT . '/config.php');

if (isset($_REQUEST['version'])) {
    die(PRODUCT_NAME . ' <b>' . PRODUCT_VERSION . ' ' . (PRODUCT_STATUS != '' ? (' (' . PRODUCT_STATUS . ')') : '') . (PRODUCT_BUILD != '' ? (' ' . PRODUCT_BUILD) : '') . '</b>');
}

Debugger::init(false, $config);

// Start debugger log
Debugger::checkpoint('Before init');

// Callback: verifies if https works
if (isset($_REQUEST['check_https'])) {
    die(defined('HTTPS') ? 'OK' : '');
}

// Check if software is installed
if ($config['db_host'] == '%DB_HOST%') {
    die(PRODUCT_NAME . ' is <b>not installed</b>. Please click here to start the installation process: <a href="install/">[install]</a>');
}

// Load core functions
$fn_list = array(
    'fn.database.php',
    'fn.users.php',
    'fn.catalog.php',
    'fn.cms.php',
    'fn.cart.php',
    'fn.locations.php',
    'fn.common.php',
    'fn.fs.php',
    'fn.images.php',
    'fn.init.php',
    'fn.control.php',
    'fn.search.php',
    'fn.promotions.php',
    'fn.log.php',
    'fn.companies.php',
    'fn.addons.php',
    'fn.exim.php'
);

$fn_list[] = 'fn.' . strtolower(PRODUCT_EDITION) . '.php';

foreach ($fn_list as $file) {
    require($config['dir']['functions'] . $file);
}

Registry::set('config', $config);
unset($config);

$application = Tygh\Tygh::createApplication();
$application['class_loader'] = $classLoader;

// Register service providers
$application->register(new Tygh\Providers\DatabaseProvider());
$application->register(new Tygh\Providers\SessionProvider());
$application->register(new Tygh\Providers\MailerProvider());
$application->register(new Tygh\Providers\TwigProvider());
$application->register(new Tygh\Providers\EnvironmentProvider());
$application->register(new Tygh\Providers\TemplateProvider());
$application->register(new Tygh\Providers\CommonProvider());
$application->register(new Tygh\Providers\ServerEnvironmentProvider(), array(
    'server.env.ini_vars' => array(
        'post_max_size' => Bootstrap::getIniParam('post_max_size', true),
        'upload_max_filesize' => Bootstrap::getIniParam('upload_max_filesize', true),
    ),
));

register_shutdown_function(array('\\Tygh\\Registry', 'save'));

fn_init_stack(
    array('fn_init_error_handler'),
    array('fn_init_unmanaged_addons')
);

if (defined('API')) {
    fn_init_stack(
        array('fn_init_api')
    );
}

fn_init_stack(
    array('fn_init_crypt'),
    array('fn_init_imagine'),
    array('fn_init_archiver'),
    array('fn_init_storage'),
    array('fn_init_ua')
);

if (fn_allowed_for('ULTIMATE')) {
    fn_init_stack(array('fn_init_store_params_by_host', &$_REQUEST));
}

fn_init_stack(
    array(function() use ($application) {
        $application['session']->init();
    }),
    array('fn_init_ajax'),
    array('fn_init_company_id', &$_REQUEST),
    array('fn_check_cache', $_REQUEST),
    array('fn_init_settings'),
    array('fn_init_addons'),
    array('fn_get_route', &$_REQUEST),
    array('fn_simple_ultimate', &$_REQUEST)
);

if (!Registry::get('config.tweaks.disable_localizations') && !fn_allowed_for('ULTIMATE:FREE')) {
    fn_init_stack(array('fn_init_localization', &$_REQUEST));
}

fn_init_stack(array('fn_init_language', &$_REQUEST),
    array('fn_init_currency', &$_REQUEST),
    array('fn_init_company_data', $_REQUEST),
    array('fn_init_full_path', $_REQUEST),
    array('fn_init_layout', &$_REQUEST),
    array('fn_init_user'),
    array('fn_init_templater')
);

// Run INIT
fn_init($_REQUEST);
