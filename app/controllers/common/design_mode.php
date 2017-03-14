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
use Tygh\Themes\Themes;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (!Registry::get('runtime.customization_mode.design') && !Registry::get('runtime.customization_mode.live_editor')) {
    die('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'update_customization_mode') {

        fn_update_customization_mode($_REQUEST['customization_modes']);

        return array(CONTROLLER_STATUS_OK, $_REQUEST['current_url']);
    }

    if ($mode == 'live_editor_update') {

        fn_trusted_vars('value');

        fn_live_editor_update_object($_REQUEST);

        exit;
    }

    if ($mode == 'save_template') {

        fn_trusted_vars('content');

        $ext = fn_strtolower(fn_get_file_ext($_REQUEST['file']));
        if ($ext == 'tpl') {
            $theme = Themes::areaFactory('C');
            if ($file = $theme->getContentPath("templates/{$_REQUEST['file']}")) {
                $is_written = fn_put_contents(
                    str_replace("design/themes/{$file['theme']}/", "design/themes/{$theme->getThemeName()}/", $file[Themes::PATH_ABSOLUTE]),
                    $_REQUEST['content']
                );
                if ($is_written) {
                    fn_set_notification('N', __('notice'), __('text_file_saved', array(
                        '[file]' => fn_basename($_REQUEST['file'])
                    )));
                }
            }
        }

        return array(CONTROLLER_STATUS_REDIRECT, $_REQUEST['current_url']);

    }

    if ($mode == 'restore_template') {

        $copied = false;

        $full_path = fn_get_theme_path('[themes]/[theme]', 'C') . '/templates/' . $_REQUEST['file'];

        if (fn_check_path($full_path)) {

            $c_name = fn_normalize_path($full_path);
            $r_name = fn_get_theme_path('[repo]/[theme]', 'C') . '/templates/' . $_REQUEST['file'];

            if (!is_file($r_name)) {
                $r_name = fn_normalize_path(Registry::get('config.dir.themes_repository') . Registry::get('config.base_theme') . '/templates/' . $_REQUEST['file']);
            }

            if (is_file($r_name)) {
                $copied = fn_copy($r_name, $c_name);
            }

            if ($copied) {
                fn_set_notification('N', __('notice'), __('text_file_restored', array(
                    '[file]' => fn_basename($_REQUEST['file'])
                )));
            } else {
                fn_set_notification('E', __('error'), __('text_cannot_restore_file', array(
                    '[file]' => fn_basename($_REQUEST['file'])
                )));
            }

            if ($copied) {
                if (defined('AJAX_REQUEST')) {
                    Tygh::$app['ajax']->assign('force_redirection', fn_url($_REQUEST['current_url']));
                    Tygh::$app['ajax']->assign('non_ajax_notifications', true);
                }

                return array(CONTROLLER_STATUS_OK, $_REQUEST['current_url']);
            }
        }
        exit;
    }
}

if ($mode == 'get_content') {

    $ext = fn_strtolower(fn_get_file_ext($_REQUEST['file']));

    if ($ext == 'tpl') {
        $theme = Themes::areaFactory('C');
        if ($file = $theme->getContentPath("templates/{$_REQUEST['file']}")) {
            $content = fn_get_contents($file[Themes::PATH_ABSOLUTE]);
            Tygh::$app['ajax']->assign('content', $content);
        }
    }
    exit;
}
