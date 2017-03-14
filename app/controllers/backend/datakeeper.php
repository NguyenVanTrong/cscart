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
use Tygh\DataKeeper;
use Tygh\Validators;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD']	== 'POST') {

    set_time_limit(0);

    // Backup database
    if ($mode == 'backup') {

        if (!empty($_REQUEST['backup_database']) && $_REQUEST['backup_database'] == 'Y' && !empty($_REQUEST['backup_files']) && $_REQUEST['backup_files'] == 'Y') {
            $mode = 'both';
        } elseif (!empty($_REQUEST['backup_database']) && $_REQUEST['backup_database'] == 'Y') {
            $mode = 'database';
        } elseif (!empty($_REQUEST['backup_files']) && $_REQUEST['backup_files'] == 'Y') {
            $mode = 'files';
        }

        switch ($mode) {
            case 'both':
                $params = array(
                    'compress' => !empty($_REQUEST['compress_type']) ? $_REQUEST['compress_type'] : 'zip',
                    'db_filename' => empty($_REQUEST['dbdump_filename']) ? date('dMY_His', TIME) . '.sql' : fn_basename($_REQUEST['dbdump_filename']) . '.sql',
                    'db_tables' => empty($_REQUEST['dbdump_tables']) ? array() : $_REQUEST['dbdump_tables'],
                    'db_schema' => !empty($_REQUEST['dbdump_schema']) && $_REQUEST['dbdump_schema'] == 'Y',
                    'db_data' => !empty($_REQUEST['dbdump_data']) && $_REQUEST['dbdump_data'] == 'Y',
                    'pack_name' => empty($_REQUEST['dbdump_filename']) ? date('dMY_His', TIME) : fn_basename($_REQUEST['dbdump_filename']),
                    'extra_folders' => !empty($_REQUEST['extra_folders']) ? $_REQUEST['extra_folders'] : array(),
                );

                $dump_file_path = DataKeeper::backup($params);

                if (!empty($dump_file_path)) {
                    fn_set_notification('N', __('notice'), __('done'));
                }
                break;

            case 'database':
                $params = array(
                    'db_filename' => empty($_REQUEST['dbdump_filename']) ? date('dMY_His', TIME) . '.sql' : fn_basename($_REQUEST['dbdump_filename']) . '.sql',
                    'db_tables' => empty($_REQUEST['dbdump_tables']) ? array() : $_REQUEST['dbdump_tables'],
                    'db_schema' => !empty($_REQUEST['dbdump_schema']) && $_REQUEST['dbdump_schema'] == 'Y',
                    'db_data' => !empty($_REQUEST['dbdump_data']) && $_REQUEST['dbdump_data'] == 'Y',
                    'db_compress' => !empty($_REQUEST['compress_type']) ? $_REQUEST['compress_type'] : 'zip',
                );

                $dump_file_path = DataKeeper::backupDatabase($params);

                if (!empty($dump_file_path)) {
                    fn_set_notification('N', __('notice'), __('done'));
                }
                break;

            case 'files':
                $params = array(
                    'pack_name' => empty($_REQUEST['dbdump_filename']) ? date('dMY_His', TIME) : fn_basename($_REQUEST['dbdump_filename']),
                    'fs_compress' => !empty($_REQUEST['compress_type']) ? $_REQUEST['compress_type'] : 'zip',
                    'extra_folders' => !empty($_REQUEST['extra_folders']) ? $_REQUEST['extra_folders'] : array(),
                );

                $dump_file_path = DataKeeper::backupFiles($params);

                if (!empty($dump_file_path)) {
                    fn_set_notification('N', __('notice'), __('done'));
                }
                break;
        }
    }

    // Restore
    if ($mode == 'restore') {
        if (!empty($_REQUEST['backup_file'])) {
            $restore_result = DataKeeper::restore($_REQUEST['backup_file']);
            if ($restore_result === true) {
                fn_set_notification('N', __('notice'), __('done'));
            } elseif ($restore_result === false) {
            } else {
                fn_set_notification('E', __('error'), $restore_result);
            }
        }
    }

    if ($mode == 'm_delete') {
        if (!empty($_REQUEST['backup_files'])) {
            $error_files = array();

            foreach ($_REQUEST['backup_files'] as $file) {
                $base_name = fn_basename($file);

                if ($base_name && !fn_rm(Registry::get('config.dir.backups') . $base_name)) {
                    $error_files[] = $base_name;
                }
            }

            if (empty($error_files)) {
                fn_set_notification('N', __('notice'), __('done'));
            } else {
                fn_set_notification('E', __('error'), __('error_cannot_delete_files', array('[files]' => implode(', ', $error_files))));
            }
        }
    }

    if ($mode == 'upload') {
        $dump = fn_filter_uploaded_data('dump', array('sql', 'tgz', 'zip'));

        if (!empty($dump)) {
            $dump = array_shift($dump);
            // Check if backups folder exists. If not - create it
            if (!is_dir(Registry::get('config.dir.backups'))) {
                fn_mkdir(Registry::get('config.dir.backups'));
            }

            if (fn_copy($dump['path'], Registry::get('config.dir.backups') . $dump['name'])) {
                fn_set_notification('N', __('notice'), __('done'));
            } else {
                fn_set_notification('E', __('error'), __('cant_create_backup_file'));
            }
        } else {
            fn_set_notification('E', __('error'), __('cant_upload_file'));
        }
    }

    if ($mode == 'optimize') {
        // Log database optimization
        fn_log_event('database', 'optimize');

        $all_tables = db_get_fields("SHOW TABLES");

        fn_set_progress('parts', sizeof($all_tables));

        foreach ($all_tables as $table) {
            fn_set_progress('echo', __('optimizing_table') . "&nbsp;<b>$table</b>...<br />");

            db_query("OPTIMIZE TABLE $table");
            db_query("ANALYZE TABLE $table");
            $fields = db_get_hash_array("SHOW COLUMNS FROM $table", 'Field');

            if (!empty($fields['is_global'])) { // Sort table by is_global field
                fn_echo('.');
                db_query("ALTER TABLE $table ORDER BY is_global DESC");
            } elseif (!empty($fields['position'])) { // Sort table by position field
                fn_echo('.');
                db_query("ALTER TABLE $table ORDER BY position");
            }
        }

        fn_set_notification('N', __('notice'), __('done'));
    }

    if ($mode == 'delete') {
        if (!empty($_REQUEST['backup_file'])) {
            $base_name = fn_basename($_REQUEST['backup_file']);

            if (fn_rm(Registry::get('config.dir.backups') . $base_name)) {
                fn_set_notification('N', __('notice'), __('done'));
            } else {
                fn_set_notification('E', __('error'), __('text_cannot_delete_file', array('[file]' => $base_name)));
            }
        }
    }

    return array(CONTROLLER_STATUS_OK, 'datakeeper.manage');
}

if ($mode == 'getfile' && !empty($_REQUEST['file'])) {
    fn_get_file(Registry::get('config.dir.backups') . fn_basename($_REQUEST['file']));

} elseif ($mode == 'manage') {
    // Calculate database size and fill tables array
    $status_data = db_get_array("SHOW TABLE STATUS");
    $database_size = 0;
    $all_tables = array();
    foreach ($status_data as $v) {
        $database_size += $v['Data_length'] + $v['Index_length'];
        $all_tables[] = $v['Name'];
    }

    $files = fn_get_dir_contents(Registry::get('config.dir.backups'), false, true, array('.sql', '.tgz', '.zip'), '', true);
    sort($files, SORT_STRING);

    $date_format = Registry::get('settings.Appearance.date_format'). ' ' . Registry::get('settings.Appearance.time_format');

    $validators = new Validators();
    $backup_files = array();

    $required_phardata = false;

    if (is_array($files)) {
        $backup_dir = Registry::get('config.dir.backups');
        foreach ($files as $file) {
            $ext = fn_get_file_ext($backup_dir . $file);
            $backup_files[$file]['size'] = filesize($backup_dir . $file);
            $backup_files[$file]['create'] = fn_date_format(filemtime($backup_dir . $file), $date_format);

            if ($ext == 'tgz') {
                $backup_files[$file]['type'] = DataKeeper::BACKUP_TYPE_UNKNOWN;
            } else {
                $backup_files[$file]['type'] = DataKeeper::getBackupType($backup_dir . $file);
            }

            $backup_files[$file]['can_be_restored'] = true;

            if ($ext == 'tgz' && !$validators->isPharDataAvailable()) {
                $backup_files[$file]['can_be_restored'] = false;
                $required_phardata = true;
            }
            if ($ext == 'zip' && !$validators->isZipArchiveAvailable()) {
                $backup_files[$file]['can_be_restored'] = false;
            }
        }
    }

    if ($required_phardata) {
        fn_set_notification('E', __('error'), __('error_class_phar_data_not_found'));
    }

    $backup_create_allowed = true;
    if (!$validators->isZipArchiveAvailable()) {
        $backup_create_allowed = false;
        fn_set_notification('E', __('error'), __('error_unable_to_create_backups'));
        fn_set_notification('E', __('error'), __('error_zip_php_extension_not_installed'));
    }

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $view->assign('database_size', $database_size)
        ->assign('all_tables', $all_tables)
        ->assign('backup_create_allowed', $backup_create_allowed)
        ->assign('backup_files', $backup_files)
        ->assign('backup_dir', fn_get_rel_dir(Registry::get('config.dir.backups')));
}