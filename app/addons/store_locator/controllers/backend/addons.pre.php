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
use Tygh\Settings;
use Tygh\Themes\Themes;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'update' && $_REQUEST['addon'] == 'store_locator' && !empty($_REQUEST['sl_settings'])) {
        $sl_settings = $_REQUEST['sl_settings'];

        fn_update_store_locator_settings($sl_settings);
        fn_update_addon($_REQUEST['addon_data']);

        return array(CONTROLLER_STATUS_REDIRECT, 'addons.manage');
    }
}

if ($mode == 'update') {

    if ($_REQUEST['addon'] == 'store_locator') {
        Tygh::$app['view']->assign('sl_provider_templates', fn_get_map_provider_setting_templates());
        Tygh::$app['view']->assign('sl_settings', fn_get_store_locator_settings());
    }

}

function fn_get_map_provider_setting_templates()
{
    $templates = array();

    $theme = Themes::areaFactory('A');
    $search_path = 'addons/store_locator/settings/';

    $_templates = $theme->getDirContents(array(
        'dir' => 'templates/' . $search_path,
        'get_dirs' => false,
        'get_files' => true,
        'extension' => array('.tpl')
    ), Themes::STR_MERGE, Themes::PATH_ABSOLUTE, Themes::USE_BASE);

    if (!empty($_templates)) {
        $needles = array('settings_', '.tpl');
        $replacements = array('', '');

        foreach ($_templates as $template => $file_info) {
            if (preg_match('/^settings_/', $template, $m)) {
                $_template = str_replace($needles, $replacements, $template); // Get the provider name
                $templates[$_template] = $search_path . $template;
            }
        }
    }

    return $templates;
}

function fn_update_store_locator_settings($sl_settings, $company_id = null)
{
    if (!$setting_id = Settings::instance()->getId('store_locator_', '')) {
        $setting_id = Settings::instance()->update(array(
            'name' =>           'store_locator_',
            'section_id' =>     0,
            'section_tab_id' => 0,
            'type' =>           'A', // any not existing type
            'position' =>       0,
            'is_global' =>      'N',
            'handler' =>        ''
        ));
    }

    Settings::instance()->updateValueById($setting_id, serialize($sl_settings), $company_id);
}
