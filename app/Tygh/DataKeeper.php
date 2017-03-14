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

namespace Tygh;

use Tygh\Registry;
use Tygh\Validators;

class DataKeeper
{
    const BACKUP_TYPE_FULL = 'full';
    const BACKUP_TYPE_FILES = 'files';
    const BACKUP_TYPE_DATABASE = 'database';
    const BACKUP_TYPE_UNKNOWN = 'unknown';

    const ERROR_UNSUPPORTED_FILE_TYPE = 'datakeeper.error_unsupported_file_type';
    const ERROR_UNWRITABLE_FILE = 'datakeeper.file_cannot_be_overrided';

    /**
     * Makes a full backup of store
     *
     * @return bool true if successfully created
     */
    public static function backup($params = array())
    {
        $tables = db_get_fields('SHOW TABLES');
        $default_params = array(
            'compress' => 'zip',
            'db_tables' => $tables,
            'db_schema' => true,
            'db_data' => true,
            'move_progress' => true,
        );

        $pack_name = !empty($params['pack_name']) ? $params['pack_name'] : date('dMY_His', TIME);
        $destination_path = fn_get_cache_path(false) . 'tmp/backup/';

        $params = array_merge($default_params, $params);

        $files = self::backupFiles($params);
        $dump = self::backupDatabase($params);

        if (!$files || !$dump) {
            return false;
        }

        fn_rm($destination_path . $pack_name);
        fn_mkdir($destination_path . $pack_name);

        fn_copy($files, $destination_path . $pack_name);
        fn_mkdir($destination_path . $pack_name . '/var/restore/');
        fn_copy($dump, $destination_path . $pack_name . '/var/restore/');

        fn_rm($files);
        fn_rm($dump);

        if (!empty($params['compress'])) {
            fn_set_progress('echo', __('compressing_backup'), false);

            $ext = $params['compress'] == 'tgz' ? '.tgz' : '.zip';

            try {
                $archiver = static::getArchiver();
                $result = $archiver->compress($destination_path . $pack_name . $ext, array($destination_path . $pack_name));
            } catch (\Exception $e) {
                $result = false;
                fn_set_notification('E', __('error'), $e->getMessage());
            }

            fn_rm($destination_path . $pack_name);

            if ($result) {
                // Move archive to backups directory
                $result = fn_rename(
                    $destination_path . $pack_name . $ext,
                    Registry::get('config.dir.backups') . $pack_name . $ext
                );

                if ($result) {
                    return Registry::get('config.dir.backups') . $pack_name . $ext;
                }
            }

            return false;
        } else {
            return $destination_path . $pack_name;
        }
    }

    /**
     * Makes store files backup
     *
     * @param array $params Extra params
     *  backup_files - array List of files/folders to be added to backup
     *  pack_name - string name of result pack. Will be stored in Registry::get('config.dir.backups') . 'files/' . $pack_name
     *  fs_compress - bool Compress result dir
     * @return string|false Path to backuped files/archve
     */
    public static function backupFiles($params = array())
    {
        $backup_files = array(
            'app',
            'design',
            'js',
            '.htaccess',
            'api.php',
            'config.local.php',
            'config.php',
            'index.php',
            'init.php',
            'robots.txt',
            'var/themes_repository',
            'var/snapshots',
            'upgrades/source_restore.php'
        );

        $backup_files[] = Registry::get('config.admin_index');

        if (fn_allowed_for('MULTIVENDOR')) {
            $backup_files[] = Registry::get('config.vendor_index');
        }

        if (!empty($params['backup_files'])) {
            $backup_files = $params['backup_files'];
        }

        if (!empty($params['extra_folders'])) {
            $params['extra_folders'] = array_map(function ($path) {
                return fn_normalize_path($path);
            }, $params['extra_folders']);

            $backup_files = array_merge($backup_files, $params['extra_folders']);
        }

        fn_set_hook('data_keeper_backup_files', $backup_files);

        $pack_name = !empty($params['pack_name']) ? $params['pack_name'] : 'backup_' . date('dMY_His', TIME);
        $destination_path = fn_get_cache_path(false) . 'tmp/backup/_files/' . $pack_name;
        $source_path = Registry::get('config.dir.root') . '/';

        fn_set_progress('step_scale', (sizeof($backup_files) + 1) * 2);
        fn_set_progress('echo', __('backup_files'), false);

        fn_rm($destination_path);
        fn_mkdir($destination_path);

        if (!fn_mkdir(Registry::get('config.dir.backups'))) {
            fn_set_notification('E', __('error'), __('text_cannot_create_directory', array(
                '[directory]' => fn_get_rel_dir(Registry::get('config.dir.backups'))
            )));

            return false;
        }

        if (!fn_is_writable($destination_path)) {
            fn_set_notification('E', __('error'), __('error_directory_not_writable', array('[dir]' => fn_get_rel_dir($destination_path))));
            return false;
        }

        if (!fn_is_writable(Registry::get('config.dir.backups'))) {
            fn_set_notification('E', __('error'), __('error_directory_not_writable', array('[dir]' => fn_get_rel_dir(Registry::get('config.dir.backups')))));
            return false;
        }

        foreach ($backup_files as $file) {
            fn_set_progress('echo', __('uc_copy_files') . ': <b>' . $file . '</b>', true);
            $dir = dirname($destination_path . '/' . $file);

            if ($dir != $destination_path) {
                fn_mkdir($dir);
            }

            fn_copy($source_path . $file, $destination_path . '/' . $file);
        }

        if (!empty($params['fs_compress'])) {
            fn_set_progress('echo', __('compressing_backup'), true);

            $ext = $params['fs_compress'] == 'tgz' ? '.tgz' : '.zip';

            try {
                $archiver = static::getArchiver();
                $result = $archiver->compress(
                    fn_get_cache_path(false) . 'tmp/backup/_files/' . $pack_name . $ext,
                    array(fn_get_cache_path(false) . 'tmp/backup/_files/' . $pack_name)
                );
            } catch (\Exception $e) {
                $result = false;
                fn_set_notification('E', __('error'), $e->getMessage());
            }

            $destination_path = rtrim($destination_path, '/');

            if ($result) {
                fn_rename($destination_path . $ext, Registry::get('config.dir.backups') . $pack_name . $ext);
            }
            fn_rm($destination_path);

            $destination_path .= $ext;
        }

        return $destination_path;
    }

    /**
     * Makes DB backup
     *  db_filename - string name of result pack. Will be stored in Registry::get('config.dir.database') . $db_filename;
     *  db_tables - array List of tables to be backuped
     *  db_schema - bool Backup tables schema
     *  db_data - bool Backup data from tables
     * @param array $params
     *
     * @return string|false Path to backuped DB sql/tgz file
     */
    public static function backupDatabase($params = array())
    {
        $default_params = array(
            'db_tables' => array(),
            'db_schema' => false,
            'db_data' => false,
            'db_compress' => false,
            'move_progress' => true,
        );

        $params = array_merge($default_params, $params);

        $db_filename = empty($params['db_filename']) ? 'dump_' . date('mdY') . '.sql' : fn_basename($params['db_filename']);

        if (!fn_mkdir(Registry::get('config.dir.backups'))) {
            fn_set_notification('E', __('error'), __('text_cannot_create_directory', array(
                '[directory]' => fn_get_rel_dir(Registry::get('config.dir.backups'))
            )));

            return false;
        }

        if (!fn_is_writable(Registry::get('config.dir.backups'))) {
            fn_set_notification('E', __('error'), __('error_directory_not_writable', array('[dir]' => fn_get_rel_dir(Registry::get('config.dir.backups')))));
            return false;
        }

        $dump_file = Registry::get('config.dir.backups') . $db_filename;

        if (is_file($dump_file)) {
            if (!is_writable($dump_file)) {
                fn_set_notification('E', __('error'), __('dump_file_not_writable'));

                return false;
            }
        }
        $result = db_export_to_file($dump_file, $params['db_tables'], $params['db_schema'], $params['db_data'], true, true, $params['move_progress']);

        if (!empty($params['db_compress'])) {
            fn_set_progress('echo', __('compress_dump'), false);

            $ext = $params['db_compress'] == 'tgz' ? '.tgz' : '.zip';

            try {
                $archiver = static::getArchiver();
                $result = $archiver->compress(
                    dirname($dump_file) . '/' . $db_filename . $ext,
                    array($db_filename => $dump_file)
                );
            } catch (\Exception $e) {
                $result = false;
                fn_set_notification('E', __('error'), $e->getMessage());
            }

            unlink($dump_file);

            $dump_file .= $ext;
        }

        if ($result) {
            return $dump_file;
        }

        return false;
    }

    /**
     * Restores backup file
     *
     * @param  string $filename  File to be restored
     * @param  string $base_path Base folder path (default: dir.backups)
     * @return bool   true if restored, error code if errors
     */
    public static function restore($filename, $base_path = '')
    {
        // Directories for check on unnecessary files
        $check_dirs = array(
            'app',
            'design',
            'var/themes_repository',
            'var/langs',
            'js',
        );
        $file_ext = fn_get_file_ext($filename);

        if (!in_array($file_ext, array('sql', 'tgz', 'zip'))) {
            return __(self::ERROR_UNSUPPORTED_FILE_TYPE);
        }

        if (empty($base_path)) {
            $base_path = Registry::get('config.dir.backups');
        }

        $backup_path = $base_path . basename($filename);

        if (in_array($file_ext, array('zip', 'tgz'))) {
            try {
                $type = self::getBackupType($backup_path, true);
            } catch (\Exception $e) {
                return $e->getMessage();
            }

            $extract_path = fn_get_cache_path(false) . 'tmp/backup/';
            fn_rm($extract_path);
            fn_mkdir($extract_path);

            if ($type == self::BACKUP_TYPE_DATABASE) {
                if (!static::decompressFiles($backup_path, $extract_path)) {
                    return false;
                }
                $list = fn_get_dir_contents($extract_path, false, true, 'sql');

                foreach ($list as $sql_file) {
                    db_import_sql_file($extract_path . $sql_file);
                }
            } else {
                $root_dir = Registry::get('config.dir.root') . '/';

                try {
                    $archiver = static::getArchiver();
                    $files_list = $archiver->getFiles($backup_path);
                } catch (\Exception $e) {
                    return $e->getMessage();
                }

                // Check permissions on all files
                foreach ($files_list as $file) {
                    if (!self::checkWritable($root_dir . $file)) {
                        return __(self::ERROR_UNWRITABLE_FILE, array('[file]' => $root_dir . $file, '[url]' => fn_url('settings.manage?section_id=Upgrade_center')));
                    }

                    fn_set_progress('echo', __('check_permissions') . ': ' . $file . '<br>', true);
                }

                // All files can be overrided. Restore backupped files
                if (!static::decompressFiles($backup_path, $extract_path)) {
                    return false;
                }
                $root_dir = Registry::get('config.dir.root') . '/';

                foreach ($files_list as $file) {
                    $ext = fn_get_file_ext($file);
                    if ($ext == 'sql' && strpos($file, 'var/restore/') !== false) {
                        // This is a DB dump. Restore it
                        db_import_sql_file($extract_path . $file);
                        continue;
                    }

                    fn_set_progress('echo', __('restore') . ': ' . $file . '<br>', true);

                    self::restoreFile($extract_path . $file, $root_dir . $file);
                }

                // Check unnecessary files in directories
                foreach ($check_dirs as $dir) {
                    if (file_exists($extract_path . $dir)) {
                        $dir_files = fn_get_dir_contents($root_dir . $dir, true, true, '', '', true);

                        foreach ($dir_files as $file) {
                            if (!file_exists($extract_path . $dir . '/' . $file)) {
                                fn_rm($root_dir . $dir . '/' . $file);
                            }
                        }
                    }
                }

                fn_rm($extract_path);
            }
        } else {
            db_import_sql_file($backup_path);
        }

        static::logEvent('database', 'restore');
        static::clearCache();

        return true;
    }

    /**
     * Clear cache
     */
    protected static function clearCache()
    {
        fn_clear_cache();
        fn_clear_template_cache();

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * Set event to log
     * @param string $type
     * @param string $action
     * @param array  $data
     */
    protected static function logEvent($type, $action, array $data = array())
    {
        fn_log_event($type, $action, $data);
    }

    /**
     * Returns type of given backup
     *
     * @param string $backup_filepath Path to file
     * @param bool   $throw_exception need throw an exception
     *
     * @return string     Type of backup (database/files/full)
     * @throws \Exception
     */
    public static function getBackupType($backup_filepath, $throw_exception = false)
    {
        $backup_type = self::BACKUP_TYPE_UNKNOWN;

        $extension = fn_get_file_ext($backup_filepath);

        if ($extension == 'sql') {
            $backup_type = self::BACKUP_TYPE_DATABASE;
        } elseif (in_array($extension, array('zip', 'tgz'))) {
            try {
                $archiver = static::getArchiver();
                $files_list = $archiver->getFiles($backup_filepath);
            } catch (\Exception $e) {
                if ($throw_exception) {
                    throw $e;
                }

                return self::BACKUP_TYPE_UNKNOWN;
            }

            if (!empty($files_list)) {
                $type = array(
                    'database' => false,
                    'files' => false,
                );

                // Archive contains only one .sql file
                if (count($files_list) == 1 && fn_get_file_ext(reset($files_list)) == 'sql') {
                    $type['database'] = true;
                } else {
                    $type['files'] = true;

                    foreach ($files_list as $filename) {
                        if (strpos($filename, 'var/restore/') !== false) {
                            $type['database'] = true;
                            break;
                        }
                    }
                }

                if ($type['database'] && $type['files']) {
                    $backup_type = self::BACKUP_TYPE_FULL;
                } elseif ($type['database']) {
                    $backup_type = self::BACKUP_TYPE_DATABASE;
                } elseif ($type['files']) {
                    $backup_type = self::BACKUP_TYPE_FILES;
                }
            }
        }

        return $backup_type;
    }

    /**
     * @param string     $archive_file_path
     * @param string     $root_directory_path
     * @param null|array $file_list
     *
     * @return bool
     * @deprecated since 4.3.6
     * @see Archive::compress
     */
    public static function createZipArchive($archive_file_path, $root_directory_path, $file_list = null)
    {
        try {
            $zip = new \ZipArchive;

            $create_result = $zip->open($archive_file_path, \ZipArchive::CREATE);

            if (true === $create_result) {
                foreach (self::revealFilePathsList($root_directory_path, $file_list) as $relative_path) {
                    $absolute_path = $root_directory_path . DIRECTORY_SEPARATOR . $relative_path;
                    if (is_dir($absolute_path)) {
                        $zip->addEmptyDir($relative_path);
                    } elseif (is_file($absolute_path)) {
                        $zip->addFile($absolute_path, $relative_path);
                    }
                }
            } else {
                return false;
            }

            $zip->close();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string     $root_directory_path
     * @param null|array $file_list
     *
     * @return array
     */
    public static function revealFilePathsList($root_directory_path, $file_list = null)
    {
        $root_directory_path = rtrim(realpath($root_directory_path), '\\/') . DIRECTORY_SEPARATOR;
        if ($file_list === null) {
            $file_list = array('.');
        }

        $output_structure = array();
        $counter = 0;
        foreach ($file_list as $relative_path) {
            $relative_path = ltrim($relative_path, '\\/');
            $absolute_path = realpath($root_directory_path . $relative_path);

            if (is_dir($absolute_path)) {
                $absolute_path = rtrim($absolute_path, '\\/').DIRECTORY_SEPARATOR;

                if ($absolute_path == $root_directory_path) {
                    $relative_path = null;
                } else {
                    $relative_path = rtrim($relative_path, '\\/') . DIRECTORY_SEPARATOR;
                }
            }

            $parent_directory_stack = fn_get_parent_directory_stack($relative_path);
            foreach ($parent_directory_stack as $parent_directory_relative_path) {
                $output_structure[$parent_directory_relative_path] = $counter++;
            }

            if (is_file($absolute_path)) {
                $output_structure[$relative_path] = $counter++;
            } elseif (is_dir($absolute_path)) {
                if ($relative_path !== null) {
                    $output_structure[$relative_path] = $counter++;
                }

                $directory_iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($absolute_path,
                        \FilesystemIterator::SKIP_DOTS |
                        \FilesystemIterator::CURRENT_AS_FILEINFO |
                        \FilesystemIterator::KEY_AS_PATHNAME
                    ),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($directory_iterator as $sub_absolute_path => $spl_file_info) {
                    /** @var \SplFileInfo $spl_file_info */
                    if ($spl_file_info->isDir()) {
                        $sub_relative_path = $relative_path . trim($directory_iterator->getSubPathname(), '\\/')
                                             . DIRECTORY_SEPARATOR;
                    } else {
                        $sub_relative_path = $relative_path . $directory_iterator->getSubPathname();
                    }

                    $sub_relative_path = ltrim($sub_relative_path, '\\/');

                    $output_structure[$sub_relative_path] = $counter++;
                }
            }
        }

        $output_structure = array_flip($output_structure);

        sort($output_structure, SORT_STRING);

        return $output_structure;
    }

    /**
     * Gets archive file tree without unpacking
     *
     * @param  string      $file_path Path to packed file
     * @param  bool        $only_root gets only root folders and files
     * @return array|false List of files in packed archive or false if archive cannot be read or archive does not support
     * @throws \Exception
     * @deprecated since 4.3.6
     * @see Archive::getFiles
     */
    public static function getCompressedFilesList($file_path, $only_root = false)
    {
        $archiver = static::getArchiver();

        return $archiver->getFiles($file_path, $only_root);
    }

    /**
     * Checks if file has writable permissions
     * @param  string $path          Path to file
     * @param  bool   $restore_perms Save the same permissions after checking
     * @return bool   true if has, false otherwise
     */
    protected static function checkWritable($path, $restore_perms = true)
    {
        if (file_exists($path) || is_dir($path)) {
            if (!is_writable($path)) {
                $old_perms = substr(sprintf('%o', fileperms($path)), -4);
                @chmod($path, 0777);
                if (!is_writable($path)) {
                    return self::checkFtpWritable($path, $restore_perms);
                } else {
                    if ($restore_perms) {
                        @chmod($path, intval($old_perms, 8));
                    }

                    return true;
                }
            }
        } else {
            return self::checkWritable(dirname($path), $restore_perms);
        }

        return true;
    }

    /**
     * Checks if file has writable permissions (FTP)
     *
     * @param  string $path          Path to file
     * @param  bool   $restore_perms Save the same permissions after checking
     * @return bool   true if has, false otherwise
     */
    protected static function checkFtpWritable($path, $restore_perms = true)
    {
        static $ftp_link = null;
        static $ftp_connection_status = false;

        if (empty($ftp_link) && !$ftp_connection_status) {
            if (fn_ftp_connect(Registry::get('settings.Upgrade_center'), true)) {
                $ftp_link = Registry::get('ftp_connection');
            }

            $ftp_connection_status = true;
        }

        if (empty($ftp_link)) {
            return false;
        }

        $old_perms = substr(sprintf('%o', fileperms($path)), -4);
        fn_ftp_chmod_file($path, 0777);

        $result = is_writable($path);

        if ($restore_perms) {
            fn_ftp_chmod_file($path, intval($old_perms, 8));
        }

        return $result;
    }

    /**
     * Restores file from the backup archive
     *
     * @param string $source      Path to source file
     * @param string $destination Path to destination file
     */
    protected static function restoreFile($source, $destination)
    {
        $log_message = sprintf('Restoring "%s" to "%s"... ', $source, $destination);

        if (file_exists($destination) || is_dir($destination)) {
            $old_perms = substr(sprintf('%o', fileperms($destination)), -4);
        }

        if (self::checkWritable($destination, false)) {
            if (is_dir($source)) {
                $result = fn_mkdir($destination);
                $log_message .= '<br>Creating directory... ' . ($result ? 'OK' : 'FAILED');
            } else {
                $result = fn_copy($source, $destination);
                $log_message .= '<br>Copying file... ' . ($result ? 'OK' : 'FAILED');
            }
        } else {
            $log_message .= 'FAILED: destination path is not writable';
        }

        fn_set_progress('echo', $log_message . '<br>', true);
    }

    /**
     * Get archiver objec
     *
     * @return \Tygh\Tools\Archiver
     */
    protected static function getArchiver()
    {
        return \Tygh\Tygh::$app['archiver'];
    }

    /**
     * Decompress files
     *
     * @param string $backup_path
     * @param string $extract_path
     * @return bool
     */
    protected static function decompressFiles($backup_path, $extract_path)
    {
        return fn_decompress_files($backup_path, $extract_path);
    }
}
