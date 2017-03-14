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

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

use Tygh\Registry;

// Functions removed in 4.3.1
if (!function_exists('fn_uc_check_files')) {
    /**
     * Check if files can be upgraded
     *
     * @param string $path files path
     * @param array $hash_table table with hashes of original files
     * @param array $result resulting array
     * @param string $package package to check files from
     * @param array $custom_theme_files list of custom theme files
     * @return boolean always true
     */
    function fn_uc_check_files($path, $hash_table, &$result, $package, $custom_theme_files)
    {
        // Simple copy for a file
        if (is_file($path)) {
            // Get original file name
            $original_file = str_replace(Registry::get('config.dir.upgrade') . $package . '/package/', Registry::get('config.dir.root') . '/', $path);
            $relative_file = str_replace(Registry::get('config.dir.root') . '/', '', $original_file);
            $file_name = fn_basename($original_file);

            if (file_exists($original_file)) {
                if (md5_file($original_file) != md5_file($path)) {

                    $_relative_file = $relative_file;
                    // For themes, convert relative path to themes_repository
                    if (strpos($relative_file, 'design/themes/') === 0) {
                        $_relative_file = str_replace('design/themes/', 'var/themes_repository/', $relative_file);

                        // replace all themes except base
                        if (fn_uc_check_array_value($relative_file, $custom_theme_files) && strpos($relative_file, '/' . Registry::get('config.base_theme') . '/') === false) {
                            $_relative_file = preg_replace('!design/\themes/([\w]+)/!S', 'var/themes_repository/${1}/', $relative_file);
                        }
                    }

                    if (!empty($hash_table[$_relative_file])) {
                        if (md5_file($original_file) != $hash_table[$_relative_file]) {
                            $result['changed'][] = $relative_file;
                        }
                    } else {
                        $result['changed'][] = $relative_file;
                    }
                }
            } else {
                $result['new'][] = $relative_file;
            }

            return true;
        }

        if (is_dir($path)) {
            $dir = dir($path);
            while (false !== ($entry = $dir->read())) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                fn_uc_check_files(rtrim($path, '/') . '/' . $entry, $hash_table, $result, $package, $custom_theme_files);
            }
            // Clean up
            $dir->close();

            return true;
        } else {
            fn_set_notification('E', __('error'), __('text_uc_incorrect_upgrade_path'));

            return false;
        }
    }

    /**
     * Create directory taking into account accessibility via php/ftp
     *
     * @param string $dir directory
     * @return boolean true if directory created successfully, false - otherwise
     */
    function fn_uc_mkdir($dir)
    {
        $result = true;
        fn_mkdir($dir);

        if (!is_dir($dir)) {
            fn_uc_ftp_mkdir($dir);
        }
        if (!is_dir($dir)) {
            fn_set_notification('E', __('error'), __('text_uc_failed_to_create_directory'));
            $result = false;
        }

        return $result;
    }

    /**
     * Copy file taking into account accessibility via php/ftp
     *
     * @param string $source source file
     * @param string $dest destination file/directory
     * @return boolean true if directory copied correctly, false - otherwise
     */
    function fn_uc_copy($source, $dest)
    {
        $result = false;
        $file_name = fn_basename($source);

        if (!file_exists($dest)) {
            if (fn_basename($dest) == $file_name) { // if we're copying the file, create parent directory
                fn_uc_mkdir(dirname($dest));
            } else {
                fn_uc_mkdir($dest);
            }
        }

        fn_echo(' .');

        if (is_writable($dest) || (is_writable(dirname($dest)) && !file_exists($dest))) {
            if (is_dir($dest)) {
                $dest .= '/' . fn_basename($source);
            }
            $result = copy($source, $dest);
            fn_uc_chmod_file($dest);
        }

        if (!$result && is_resource(Registry::get('ftp_connection'))) { // try ftp
            $result = fn_uc_ftp_copy($source, $dest);
        }

        if (!$result) {
            fn_set_notification('E', __('error'), __('cannot_write_file', array(
                '[file]' => $dest
            )));
        }

        return $result;
    }

    function fn_uc_chmod_file($filename)
    {
        $ext = fn_get_file_ext($filename);
        $perm = ($ext == 'php' ? 0644 : DEFAULT_FILE_PERMISSIONS);

        $result = @chmod($filename, $perm);

        if (!$result) {
            $ftp = Registry::get('ftp_connection');
            if (is_resource($ftp)) {
                $dest = dirname($filename);
                $dest = rtrim($dest, '/') . '/'; // force adding trailing slash to path

                $rel_path = str_replace(Registry::get('config.dir.root') . '/', '', $dest);
                $cdir = ftp_pwd($ftp);

                if (empty($rel_path)) { // if rel_path is empty, assume it's root directory
                    $rel_path = $cdir;
                }

                if (ftp_chdir($ftp, $rel_path)) {
                    $result = @ftp_site($ftp, "CHMOD " . sprintf('0%o', $perm) . " " . fn_basename($filename));
                    ftp_chdir($ftp, $cdir);
                }
            }
        }

        return $result;
    }

    /**
     * Copy files from one directory to another
     *
     * @param string $source source directory
     * @param string $dest destination directory
     * @return boolean true if directory copied correctly, false - otherwise
     */
    function fn_uc_copy_files($source, $dest)
    {
        // Simple copy for a file
        if (is_file($source)) {
            return fn_uc_copy($source, $dest);
        }

        // Loop through the folder
        if (is_dir($source)) {
            $dir = dir($source);
            while (false !== $entry = $dir->read()) {
                // Skip pointers
                if ($entry == '.' || $entry == '..') {
                    continue;
                }

                // Deep copy directories
                if ($dest !== $source . '/' . $entry) {
                    if (fn_uc_copy_files(rtrim($source, '/') . '/' . $entry, $dest . '/' . $entry) == false) {
                        return false;
                    }
                }
            }

            // Clean up
            $dir->close();

            return true;
        } else {
            fn_set_notification('E', __('error'), __('cannot_write_file', array(
                '[file]' => $dest
            )));

            return false;
        }
    }

    /**
     * Copy file using ftp
     *
     * @param string $source source file
     * @param string $dest destination file/directory
     * @return boolean true if copied successfully, false - otherwise
     */
    function fn_uc_ftp_copy($source, $dest)
    {
        $result = false;

        $ftp = Registry::get('ftp_connection');
        if (is_resource($ftp)) {
            if (!is_dir($dest)) { // file
                $dest = dirname($dest);
            }
            $dest = rtrim($dest, '/') . '/'; // force adding trailing slash to path

            $rel_path = str_replace(Registry::get('config.dir.root') . '/', '', $dest);
            $cdir = ftp_pwd($ftp);

            if (empty($rel_path)) { // if rel_path is empty, assume it's root directory
                $rel_path = $cdir;
            }

            if (ftp_chdir($ftp, $rel_path) && ftp_put($ftp, fn_basename($source), $source, FTP_BINARY)) {
                @ftp_site($ftp, "CHMOD " . (fn_get_file_ext($source) == 'php' ? '0644' : sprintf('0%o', DEFAULT_FILE_PERMISSIONS)) . " " . fn_basename($source));
                $result = true;
                ftp_chdir($ftp, $cdir);
            }
        }

        if (false === $result) {
            fn_set_notification('E', __('error'), __('text_uc_failed_to_ftp_copy'));
        }

        return $result;
    }

    /**
     * Create directory using ftp
     *
     * @param string $dir directory
     * @return boolean true if directory created successfully, false - otherwise
     */
    function fn_uc_ftp_mkdir($dir)
    {
        if (@is_dir($dir)) {
            return true;
        }

        $ftp = Registry::get('ftp_connection');
        if (!is_resource($ftp)) {
            fn_set_notification('E', __('error'), __('text_uc_ftp_connection_failed'));

            return false;
        }

        $result = false;

        $rel_path = str_replace(Registry::get('config.dir.root') . '/', '', $dir);
        $path = '';
        $dir_arr = array();
        if (strstr($rel_path, '/')) {
            $dir_arr = explode('/', $rel_path);
        } else {
            $dir_arr[] = $rel_path;
        }

        foreach ($dir_arr as $k => $v) {
            $path .= (empty($k) ? '' : '/') . $v;
            if (!@is_dir(Registry::get('config.dir.root') . '/' . $path)) {
                if (ftp_mkdir($ftp, $path)) {
                    $result = true;
                } else {
                    $result = false;
                    break;
                }
            } else {
                $result = true;
            }
        }

        if (false === $result) {
            fn_set_notification('E', __('error'), __('text_uc_failed_to_ftp_mkdir'));
        }

        return $result;
    }

    /**
     * Check if array item exists in the string
     *
     * @param string $value string to search array item in
     * @param array $array items list
     * @return boolean true if value found, false - otherwise
     */
    function fn_uc_check_array_value($value, $array)
    {
        foreach ($array as $v) {
            if (strpos($value, $v) !== false) {
                return true;
            }
        }

        return false;
    }

    function fn_uc_rm($path)
    {
        fn_rm($path);
        if (file_exists($path)) {
            fn_uc_ftp_rm($path);
        }
        if (file_exists($path)) {
            fn_set_notification('E', __('error'), __('text_uc_unable_to_remove_file') . ' ' . $path);
        }

        return true;
    }

    function fn_uc_ftp_rm($path)
    {
        $ftp = Registry::get('ftp_connection');
        if (is_resource($ftp)) {
            $rel_path = str_replace(Registry::get('config.dir.root') . '/', '', $path);
            if (is_file($path)) {
                return @ftp_delete($ftp, $rel_path);
            }

            // Loop through the folder
            if (is_dir($path)) {
                $dir = dir($path);
                while (false !== $entry = $dir->read()) {
                    // Skip pointers
                    if ($entry == '.' || $entry == '..') {
                        continue;
                    }
                    if (fn_uc_ftp_rm($path . '/' . $entry) == false) {
                        return false;
                    }
                }
                // Clean up
                $dir->close();

                return @ftp_rmdir($ftp, $rel_path);
            }
        }

        return false;
    }
}
