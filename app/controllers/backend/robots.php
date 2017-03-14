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
use Tygh\Common\Robots;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $redirect_params = array();
    $writable = null;
    $robots = new Robots;

    if ($mode == 'check') {
        $writable = $robots->check();
        if ($writable) {
            fn_set_notification('N', __('notice'), __('text_permissions_changed'));
        } else {
            if (defined('AJAX_REQUEST')) {
                fn_set_notification('E', __('error'), __('cannot_write_file', array('[file]' => 'robots.txt')));
                exit;
            }
        }
    }

    if ($mode == 'update') {
        if (
            !empty($_REQUEST['robots_data'])
            && !empty($_REQUEST['robots_data']['edit'])
            && $_REQUEST['robots_data']['edit'] = 'Y'
            && isset($_REQUEST['robots_data']['content'])
        ) {
            if ($robots->check()) {
                $robots->save($_REQUEST['robots_data']['content']);
            } else {
                fn_delete_notification('changes_saved');
                $writable = false;
            }
        }
    }

    if ($mode == 'update_via_ftp') {
        if (
            !empty($_REQUEST['robots_data'])
            && !empty($_REQUEST['robots_data']['edit'])
            && $_REQUEST['robots_data']['edit'] = 'Y'
            && !empty($_REQUEST['robots_data']['content'])
            && !empty($_REQUEST['ftp_access'])
        ) {
            $ftp_settings = array(
                'hostname' => $_REQUEST['ftp_access']['ftp_hostname'],
                'username' => $_REQUEST['ftp_access']['ftp_username'],
                'password' => $_REQUEST['ftp_access']['ftp_password'],
                'directory' => $_REQUEST['ftp_access']['ftp_directory'],
            );
            list($result, $error_text) = $robots->updateViaFtp($_REQUEST['robots_data']['content'], $ftp_settings);
            if (!$result) {
                fn_delete_notification('changes_saved');
                fn_set_notification('E', __('error'), $error_text);
                $writable = false;
                if (defined('AJAX_REQUEST')) {
                    exit;
                }
            }
        }
    }

    if (!is_null($writable)) {
        if (!$writable) {
            fn_set_notification('E', __('error'), __('cannot_write_file', array('[file]' => 'robots.txt')));
            $redirect_params['is_not_writable'] = true;
        }
        if (!empty($_REQUEST['robots_data']['content'])) {
            $redirect_params['content'] = $_REQUEST['robots_data']['content'];
        }
    }

    return array(CONTROLLER_STATUS_OK, 'robots.manage?' . http_build_query($redirect_params));
}

if ($mode == 'manage') {

    $content = '';
    if (empty($_REQUEST['content'])) {
        $default = !empty($_REQUEST['default']);
        $robots = new Robots($default);
        $content = $robots->get();
        if ($default) {
            if (empty($content)) { // It first time, need to get original
                $robots = new Robots;
                $content = $robots->get();
            }
            Tygh::$app['view']->assign('edit', true);
        }
    } else {
        $content = $_REQUEST['content'];
        Tygh::$app['view']->assign('edit', true);
    }

    Tygh::$app['view']->assign('robots', $content);

}
