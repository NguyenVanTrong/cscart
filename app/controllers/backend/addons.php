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
use Tygh\Settings;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    fn_trusted_vars (
        'addon_data'
    );

    if ($mode == 'update') {
        $addon_scheme = \Tygh\Addons\SchemesManager::getScheme($_REQUEST['addon']);

        if ($addon_scheme === false || $addon_scheme->isPromo()) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        if (isset($_REQUEST['addon_data'])) {
            fn_update_addon($_REQUEST['addon_data']);
        }

        return array(CONTROLLER_STATUS_OK, 'addons.update?addon=' . $_REQUEST['addon']);

    } elseif ($mode == 'recheck') {
        $addon_name = $_REQUEST['addon_name'];
        $source = Registry::get('config.dir.root') . '/' . $_REQUEST['addon_extract_path'];
        $destination = Registry::get('config.dir.root');

        if (!file_exists($source) || !fn_validate_addon_structure($addon_name, $source)) {
            fn_set_notification('E', __('error'), __('broken_addon_pack'));

            if (defined('AJAX_REQUEST')) {
                Tygh::$app['ajax']->assign('non_ajax_notifications', true);
                Tygh::$app['ajax']->assign('force_redirection', fn_url('addons.manage'));

                exit();
            } else {
                return array(CONTROLLER_STATUS_REDIRECT, 'addons.manage');
            }
        }

        if ($action == 'ftp_upload') {
            $ftp_access = array(
                'hostname' => $_REQUEST['ftp_access']['ftp_hostname'],
                'username' => $_REQUEST['ftp_access']['ftp_username'],
                'password' => $_REQUEST['ftp_access']['ftp_password'],
                'directory' => $_REQUEST['ftp_access']['ftp_directory'],
            );

            $ftp_copy_result = fn_copy_by_ftp($source, $destination, $ftp_access);

            if ($ftp_copy_result === true) {
                fn_install_addon($addon_name);
            } else {
                fn_set_notification('E', __('error'), $ftp_copy_result);
            }

            if (defined('AJAX_REQUEST')) {
                Tygh::$app['ajax']->assign('non_ajax_notifications', true);
                Tygh::$app['ajax']->assign('force_redirection', fn_url('addons.manage'));

                exit();
            } else {
                return array(CONTROLLER_STATUS_OK, 'addons.manage');
            }
        }

        $non_writable_folders = fn_check_copy_ability($source, $destination);

        if (!empty($non_writable_folders)) {
            if (!empty($_REQUEST['ftp_access'])) {
                Tygh::$app['view']->assign('ftp_access', $_REQUEST['ftp_access']);
            }

            Tygh::$app['view']->assign('non_writable', $non_writable_folders);

            if (defined('AJAX_REQUEST')) {
                Tygh::$app['view']->display('views/addons/components/correct_permissions.tpl');

                exit();
            }

        } else {
            fn_addons_move_and_install($source, Registry::get('config.dir.root'));

            if (defined('AJAX_REQUEST')) {
                Tygh::$app['ajax']->assign('force_redirection', fn_url('addons.manage'));

                exit();
            }
        }

    } elseif ($mode == 'upload') {
        if (defined('RESTRICTED_ADMIN') || Registry::get('runtime.company_id')) {
            fn_set_notification('E', __('error'), __('access_denied'));

            return array(CONTROLLER_STATUS_REDIRECT, 'addons.manage');
        }

        $addon_pack = fn_filter_uploaded_data('addon_pack', Registry::get('config.allowed_pack_exts'));

        if (empty($addon_pack[0])) {
            fn_set_notification('E', __('error'), __('text_allowed_to_upload_file_extension', array('[ext]' => implode(',', Registry::get('config.allowed_pack_exts')))));
        } else {
            $addon_pack = $addon_pack[0];
            $tmp_path = fn_get_cache_path(false) . 'tmp/';
            $addon_file = $tmp_path . $addon_pack['name'];

            fn_mkdir($tmp_path);
            fn_copy($addon_pack['path'], $addon_file);

            $addon_pack_result = fn_extract_addon_package($addon_file);

            fn_rm($addon_file);

            if ($addon_pack_result) {
                list($addon_name, $extract_path) = $addon_pack_result;

                if (fn_validate_addon_structure($addon_name, $extract_path)) {
                    $non_writable_folders = fn_check_copy_ability($extract_path, Registry::get('config.dir.root'));

                    if (!empty($non_writable_folders)) {
                        Tygh::$app['view']->assign('non_writable', $non_writable_folders);
                        Tygh::$app['view']->assign('addon_extract_path', fn_get_rel_dir($extract_path));
                        Tygh::$app['view']->assign('addon_name', $addon_name);

                        if (defined('AJAX_REQUEST')) {
                            Tygh::$app['view']->display('views/addons/components/correct_permissions.tpl');
                            exit();
                        }
                    } else {
                        fn_addons_move_and_install($extract_path, Registry::get('config.dir.root'));

                        if (defined('AJAX_REQUEST')) {
                            Tygh::$app['ajax']->assign('force_redirection', fn_url('addons.manage'));
                            exit();
                        }
                    }
                }
            }

            fn_set_notification('E', __('error'), __('broken_addon_pack'));

            if (defined('AJAX_REQUEST')) {
                Tygh::$app['ajax']->assign('non_ajax_notifications', true);
                Tygh::$app['ajax']->assign('force_redirection', fn_url('addons.manage'));

                exit();
            } else {
                return array(CONTROLLER_STATUS_REDIRECT, 'addons.manage');
            }
        }

        if (defined('AJAX_REQUEST')) {
            Tygh::$app['view']->display('views/addons/components/upload_addon.tpl');

            exit();
        }
    } elseif ($mode == 'licensing') {  // Used for saving add-on license key to the DB
        if (!isset($_REQUEST['addon'], $_REQUEST['redirect_url'], $_REQUEST['marketplace_license_key'])) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        $addon_id = $_REQUEST['addon'];
        $redirect_url = $_REQUEST['redirect_url'];
        $license_key = $_REQUEST['marketplace_license_key'];

        $addon_data = db_get_row(
            'SELECT * FROM ?:addons AS `a`'
            . ' WHERE `a`.`addon` = ?s'
            . ' AND `a`.`unmanaged` <> 1'
            . ' AND `a`.`marketplace_id` IS NOT NULL;',
            $addon_id
        );

        if (empty($addon_data)) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        db_query('UPDATE ?:addons SET ?u WHERE `addon` = ?s;',
            array(
                'marketplace_license_key' => $license_key,
            ),
            $addon_id
        );

        fn_set_notification('N', __('notice'), __('text_changes_saved'));

        // Redirect browser back
        if (defined('AJAX_REQUEST')) {
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
        } else {
            return array(CONTROLLER_STATUS_REDIRECT, $redirect_url);
        }

        exit;
    }

    if ($mode == 'update_status') {

        $is_snapshot_correct = fn_check_addon_snapshot($_REQUEST['id']);

        if (!$is_snapshot_correct) {
            $status = false;

        } else {
            $status = fn_update_addon_status($_REQUEST['id'], $_REQUEST['status']);
        }

        if ($status !== true) {
            Tygh::$app['ajax']->assign('return_status', $status);
        }
        Registry::clearCachedKeyValues();
    }

    if ($mode == 'install') {
        fn_install_addon($_REQUEST['addon']);
        Registry::clearCachedKeyValues();
    }

    if ($mode == 'uninstall') {
        fn_uninstall_addon($_REQUEST['addon']);
    }

    if ($mode == 'tools') {
        if (\Tygh\Snapshot::exist()) {
            $init_addons = !empty($_REQUEST['init_addons']) ? $_REQUEST['init_addons'] : '';

            if ($init_addons != 'none' && $init_addons != 'core') {
                $init_addons = '';
            }

            Settings::instance()->updateValue('init_addons', $init_addons);
            fn_clear_cache();
        } else {
            fn_set_notification('E', __('error'), __('tools_snapshot_not_found'));
        }

        $return_url = !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : 'addons.manage';

        return array(CONTROLLER_STATUS_REDIRECT, $return_url);
    }

    return array(CONTROLLER_STATUS_OK, 'addons.manage');
}

if ($mode == 'update') {
    $addon_scheme = \Tygh\Addons\SchemesManager::getScheme($_REQUEST['addon']);

    if ($addon_scheme === false || $addon_scheme->isPromo()) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $addon_name = addslashes($_REQUEST['addon']);

    $section = Settings::instance()->getSectionByName($_REQUEST['addon'], Settings::ADDON_SECTION);

    if (empty($section)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $subsections = Settings::instance()->getSectionTabs($section['section_id'], CART_LANGUAGE);
    $options = Settings::instance()->getList($section['section_id']);

    fn_update_lang_objects('sections', $subsections);
    fn_update_lang_objects('options', $options);

    Tygh::$app['view']->assign('options', $options);
    Tygh::$app['view']->assign('subsections', $subsections);

    $addon = db_get_row(
        'SELECT a.addon, a.status, b.name as name, b.description as description, a.separate, a.install_datetime '
        . 'FROM ?:addons as a LEFT JOIN ?:addon_descriptions as b ON b.addon = a.addon AND b.lang_code = ?s WHERE a.addon = ?s'
        . 'ORDER BY b.name ASC',
        CART_LANGUAGE, $_REQUEST['addon']
    );

    Tygh::$app['view']->assign('addon_version', $addon_scheme->getVersion());
    Tygh::$app['view']->assign('addon_supplier', $addon_scheme->getSupplier());
    Tygh::$app['view']->assign('addon_supplier_link', $addon_scheme->getSupplierLink());
    Tygh::$app['view']->assign('addon_install_datetime', $addon['install_datetime']);

    if ($addon['separate'] == true || !defined('AJAX_REQUEST')) {
        Tygh::$app['view']->assign('separate', true);
        Tygh::$app['view']->assign('addon_name', $addon['name']);
    }

} elseif ($mode == 'manage') {
    $params = $_REQUEST;
    $params['for_company'] = (bool) Registry::get('runtime.company_id');

    list($addons, $search, $addons_counter) = fn_get_addons($params);

    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('addons_list', $addons);
    Tygh::$app['view']->assign('addons_counter', $addons_counter);
    Tygh::$app['view']->assign('snapshot_exist', \Tygh\Snapshot::exist());

} elseif ($mode == 'licensing') {

    if (empty($_REQUEST['addon'])) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $addon_id = $_REQUEST['addon'];
    $redirect_url = isset($_REQUEST['return_url']) ? $_REQUEST['return_url'] : null;

    $addon_data = db_get_row(
        'SELECT * FROM ?:addons AS `a`'
        . ' WHERE `a`.`addon` = ?s'
        . ' AND `a`.`unmanaged` <> 1'
        . ' AND `a`.`marketplace_id` IS NOT NULL;',
        $addon_id
    );

    if (empty($addon_data)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    Tygh::$app['view']
        ->assign('addon_data', $addon_data)
        ->assign('redirect_url', $redirect_url);
}
