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
use Tygh\Http;
use Tygh\Registry;
use Tygh\Storage;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Normalize path (URL also accepted): remove "../", "./" and duplicated slashes
 *
 * @param string $path
 * @param string $separator
 * @return string normilized path
 */
function fn_normalize_path($path, $separator = '/')
{
    $prefix = '';
    if (strpos($path, '://') !== false) { // url is passed
        list($prefix, $path) = explode('://', $path);
        $prefix .= '://';
    }

    $result = array();
    $path = preg_replace("/[\\\\\/]+/S", $separator, $path);
    $path_array = explode($separator, $path);
    if (!$path_array[0]) {
        $result[] = '';
    }

    foreach ($path_array as $key => $dir) {
        if ($dir == '..') {
            if (end($result) == '..') {
               $result[] = '..';
            } elseif (!array_pop($result)) {
               $result[] = '..';
            }
        } elseif ($dir != '' && $dir != '.') {
            $result[] = $dir;
        }
    }

    if (!end($path_array)) {
        $result[] = '';
    }

    return fn_is_empty($result) ? '' : $prefix . implode($separator, $result);
}

/**
 * Create directory wrapper. Allows to create included directories
 *
 * @param string $dir
 * @param int $perms permission for new directory
 * @return array List of directories
 */
function fn_mkdir($dir, $perms = DEFAULT_DIR_PERMISSIONS)
{
    $result = false;

    if (!empty($dir)) {

        clearstatcache();
        if (@is_dir($dir)) {

            $result = true;

        } else {

            // Truncate the full path to related to avoid problems with some buggy hostings
            if (strpos($dir, DIR_ROOT) === 0) {
                $dir = './' . substr($dir, strlen(DIR_ROOT) + 1);
                $old_dir = getcwd();
                chdir(DIR_ROOT);
            }

            $dir = fn_normalize_path($dir, '/');
            $path = '';
            $dir_arr = array();
            if (strstr($dir, '/')) {
                $dir_arr = explode('/', $dir);
            } else {
                $dir_arr[] = $dir;
            }

            foreach ($dir_arr as $k => $v) {
                $path .= (empty($k) ? '' : '/') . $v;
                clearstatcache();
                if (!is_dir($path)) {
                    umask(0);
                    $result = @mkdir($path, $perms);
                    if (!$result) {
                        $parent_dir = dirname($path);
                        $parent_perms = fileperms($parent_dir);
                        @chmod($parent_dir, 0777);
                        $result = @mkdir($path, $perms);
                        @chmod($parent_dir, $parent_perms);
                        if (!$result) {
                            break;
                        }
                    }
                }
            }

            if (!empty($old_dir)) {
                @chdir($old_dir);
            }
        }
    }

    return $result;
}

/**
 * Compress files with Tar archiver
 *
 * @param string $archive_name - archive name (zip, tgz, gz and tar.gz supported)
 * @param string $file_list - list of files to place into archive
 * @param string $dirname - directory, where the files should be get from
 * @return bool true
 */
function fn_compress_files($archive_name, $file_list, $dirname = '')
{
    $files = array();

    if (empty($dirname)) {
        $dirname = Registry::get('config.dir.files');
    }

    if (!is_array($file_list)) {
        $file_list = array($file_list);
    }

    foreach ($file_list as $key => $file) {
        $file = fn_normalize_path($dirname . '/' . $file);

        if (is_file($file) && is_numeric($key)) {
            $key = basename($file);
        }

        $files[$key] = $file;
    }

    $arch = fn_normalize_path($dirname . '/' . $archive_name);

    fn_rm($arch);

    try {
        /** @var \Tygh\Tools\Archiver $archiver */
        $archiver = \Tygh\Tygh::$app['archiver'];
        return $archiver->compress($arch, $files);
    } catch (Exception $e) {
        fn_set_notification('E', __('error'), $e->getMessage());
    }

    return false;
}

/**
 * Extracts files from archive to specified place
 *
 * @param $archive_name - path to the compressed file
 * @param $dirname - directory, where the files should be extracted to
 * @return bool true if archive was succesfully extracted, false otherwise
 */
function fn_decompress_files($archive_name, $dirname = '')
{
    $result = false;

    if (empty($dirname)) {
        $dirname = Registry::get('config.dir.files');
    }

    try {
        /** @var \Tygh\Tools\Archiver $archiver */
        $archiver = \Tygh\Tygh::$app['archiver'];
        $result = $archiver->extractTo($archive_name, $dirname);

        if (!$result) {
            fn_set_notification('E', __('error'), __('unable_to_unpack_file'));
        }
    } catch (Exception $e) {
        fn_set_notification('E', __('error'), $e->getMessage());
    }

    return $result;
}

/**
 * Gets list of extensions with mime types or mime types with exts
 *
 * @param string $key get ext list with the mime linked, or mime with the ext linked
 * @return array List of Exts/Mime
 */
function fn_get_ext_mime_types($key = 'ext')
{
    $types = array (
        'zip' => 'application/zip',
        'tgz' => 'application/tgz',
        'rar' => 'application/rar',

        'exe' => 'application/exe',
        'com' => 'application/com',
        'bat' => 'application/bat',

        'png' => 'image/png',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/x-icon',
        'swf' => 'application/x-shockwave-flash',

        'csv' => 'text/csv',
        'txt' => 'text/plain',
        'doc' => 'application/msword',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pdf' => 'application/pdf',

        'css' => 'text/css',
        'js' => 'text/javascript'
    );

    if ($key == 'mime') {
        $types = array_flip($types);
    }

    return $types;
}

/**
 * Get MIME type by the file name
 *
 * @param string $filename
 * @param string $not_available_result MIME type that will be returned in case all checks fail
 * @return string $file_type MIME type of the given file.
 */
function fn_get_file_type($filename, $not_available_result = 'application/octet-stream')
{
    $file_type = $not_available_result;

    $types = fn_get_ext_mime_types('ext');

    $ext = fn_strtolower(fn_get_file_ext($filename));

    if (!empty($types[$ext])) {
        $file_type = $types[$ext];
    }

    return $file_type;
}

/**
 * Function tries to get MIME type by different ways.
 *
 * @param string $filename Full path with name to file
 * @param boolean $check_by_extension Try to get MIME type by extension of the file
 * @param string $not_available_result MIME type that will be returned in case all checks fail
 * @return string MIME type of the given file.
 */
function fn_get_mime_content_type($filename, $check_by_extension = true, $not_available_result = 'application/octet-stream')
{
    $type = '';

    if (class_exists('finfo')) {
        $finfo_handler = @finfo_open(FILEINFO_MIME);
        if ($finfo_handler !== false) {
            $type = @finfo_file($finfo_handler, $filename);
            list($type) = explode(';', $type);
            @finfo_close($finfo_handler);
        }
    }

    if (empty($type) && function_exists('mime_content_type')) {
        $type = @mime_content_type($filename);
    }

    if (empty($type) && $check_by_extension && strpos(fn_basename($filename), '.') !== false) {
        $type = fn_get_file_type(fn_basename($filename), $not_available_result);
    }

    return !empty($type) ? $type : $not_available_result;
}

/**
 * Get the EDP downloaded
 *
 * @param string $path path to the file
 * @param string $filename file name to be displayed in download dialog
 * @param boolean $delete deletes original file after download
 * @return bool Always false
 */
function fn_get_file($file_path, $filename = '', $delete = false)
{
    $handle_stream = @fopen($file_path, 'rb');
    if (!$handle_stream) {
        return false;
    }
    $file_size = filesize($file_path);
    $file_mime_type = fn_get_mime_content_type($file_path);
    $file_last_modified_time = date('D, d M Y H:i:s T', filemtime($file_path));
    if (empty($filename)) {
        // Non-ASCII filenames containing spaces and underscore
        // characters are chunked if no locale is provided
        setlocale(LC_ALL, 'en_US.UTF8');
        $filename = fn_basename($file_path);
    }

    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = str_replace('bytes=', '', $_SERVER['HTTP_RANGE']);
        $range = (int) strtok($range, '-');

        if (!empty($range)) {
            fseek($handle_stream, $range);
        }
    } else {
        $range = 0;
    }

    // Clear output buffers before headers are sent to prevent dowloading damaged file
    // if any content was added to buffers before
    $gz_handler = false;
    foreach (ob_list_handlers() as $handler) {
        if (strpos($handler, 'gzhandler') !== false) {
            $gz_handler = true;
            break;
        }
    }
    fn_clear_ob();
    // Delete headers added by ob_start("ob_gzhandler")
    if ($gz_handler && !headers_sent() && !ob_list_handlers()) {
        header_remove('Vary');
        header_remove('Content-Encoding');
    }

    // Browser bug workaround: filenames can't be sent to IE if there is
    // any kind of traffic compression enabled on the server side
    if (USER_AGENT == 'ie') {
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', '1');
        }
        ini_set("zlib.output_compression", "Off");

        // Browser bug workaround: During the file download with IE,
        // non-ASCII filenames appears with a broken encoding
        $filename = rawurlencode($filename);
    }

    if ($range) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 206 Partial Content');
        header("Content-Range: bytes $range-" . ($file_size - 1) . '/' . $file_size);
    } else {
        header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
    }

    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Type: ' . $file_mime_type);
    header('Last-Modified: ' . $file_last_modified_time);
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . ($file_size - $range));
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false);

    $result = fpassthru($handle_stream);
    fclose($handle_stream);

    if ($delete) {
        fn_rm($file_path);
    }
    if ($result === false) {
        return false;
    }

    exit;
}

/**
 * Gets file, located on server FS
 *
 * @param string $val file path
 * @return array $val
 */
function fn_get_server_data($val)
{
    if (defined('IS_WINDOWS')) {
        $val = str_replace('\\', '/', $val);
    }

    $allowed_paths = array(
        fn_get_files_dir_path(),
        fn_get_public_files_path()
    );

    $val = fn_normalize_path($val);

    if (Registry::get('runtime.allow_upload_external_paths') && strpos($val, Registry::get('config.dir.root')) === 0) {
        $allowed_paths = array($val);
    }

    setlocale(LC_ALL, 'en_US.UTF8');

    foreach ($allowed_paths as $root_path) {

        if (strpos($val, $root_path) === 0) {
            $path = $val;
        } else {
            $path = fn_normalize_path($root_path . $val);
        }

        if (strpos($path, $root_path) === 0 && file_exists($path)) {

            $result = array(
                'name' => fn_basename($path),
                'path' => $path
            );

            $tempfile = fn_create_temp_file();
            fn_copy($result['path'], $tempfile);
            $result['path'] = $tempfile;
            $result['size'] = filesize($result['path']);
            $result['type'] = fn_get_mime_content_type($path);

            $cache = Registry::get('temp_fs_data');

            if (!isset($cache[$result['path']])) { // cache file to allow multiple usage
                $cache[$result['path']] = $tempfile;
                Registry::set('temp_fs_data', $cache);
            }

            return $result;
        }
    }

    return false;
}

/**
 * Rebuilds $_FILES array to more user-friendly look
 *
 * @param string $name Name of file parameter
 * @return array Rebuilt file array
 */
function fn_rebuild_files($name)
{
    $rebuilt = array();

    if (!is_array(@$_FILES[$name])) {
        return $rebuilt;
    }

    if (isset($_FILES[$name]['error'])) {
        if (!is_array($_FILES[$name]['error'])) {
            return $_FILES[$name];
        }
    } elseif (fn_is_empty($_FILES[$name]['size'])) {
        return $_FILES[$name];
    }

    foreach ($_FILES[$name] as $k => $v) {
        if ($k == 'tmp_name') {
            $k = 'path';
        }
        $rebuilt = fn_array_multimerge($rebuilt, $v, $k);
    }

    return $rebuilt;
}

/**
 * Recursively copy directory (or just a file)
 *
 * @param string $source
 * @param string $dest
 * @param bool $silent
 * @param array $exclude_files
 * @return bool True on success, false otherwise
 */
function fn_copy($source, $dest, $silent = true, $exclude_files = array())
{
    /**
     * Ability to forbid file copy or change parameters
     *
     * @param string  $source  source file/directory
     * @param string  $dest    destination file/directory
     * @param boolean $silent  silent flag
     * @param array   $exclude files to exclude
     */
    fn_set_hook('copy_file', $source, $dest, $silent, $exclude_files);

    if (empty($source)) {
        return false;
    }

    // Simple copy for a file
    if (is_file($source)) {
        $source_file_name = fn_basename($source);
        if (in_array($source_file_name, $exclude_files)) {
            return true;
        }
        if (@is_dir($dest)) {
            $dest .= '/' . $source_file_name;
        }
        if (filesize($source) == 0) {
            $fd = fopen($dest, 'w');
            fclose($fd);
            $res = true;
        } else {
            $res = @copy($source, $dest);
        }
        @chmod($dest, DEFAULT_FILE_PERMISSIONS);
        clearstatcache(true, $dest);

        return $res;
    }

    // Make destination directory
    if ($silent == false) {
        $_dir = strpos($dest, Registry::get('config.dir.root')) === 0 ? str_replace(Registry::get('config.dir.root') . '/', '', $dest) : $dest;
        fn_set_progress('echo', $_dir . '<br/>');
    }

    if (!fn_mkdir($dest)) {
        return false;
    }

    // Loop through the folder
    if (@is_dir($source)) {
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories
            if ($dest !== $source . '/' . $entry) {
                if (fn_copy($source . '/' . $entry, $dest . '/' . $entry, $silent, $exclude_files) == false) {
                    return false;
                }
            }
        }

        // Clean up
        $dir->close();

        return true;
    } else {
        return false;
    }
}

/**
 * Recursively remove directory (or just a file)
 *
 * @param string $source
 * @param bool $delete_root
 * @param string $pattern
 * @return bool
 */
function fn_rm($source, $delete_root = true, $pattern = '')
{
    // Simple copy for a file
    if (is_file($source)) {
        $res = true;
        if (empty($pattern) || (!empty($pattern) && preg_match('/' . $pattern . '/', fn_basename($source)))) {
            $res = @unlink($source);
        }

        return $res;
    }

    // Loop through the folder
    if (is_dir($source) && $dir = dir($source)) {
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }
             if (fn_rm($source . '/' . $entry, true, $pattern) == false) {
                return false;
            }
        }
        // Clean up
        $dir->close();

        return ($delete_root == true && empty($pattern)) ? @rmdir($source) : true;
    } else {
        return false;
    }
}

/**
 * Get file extension
 *
 * @param string $filename
 * @return string File extension
 */
function fn_get_file_ext($filename)
{
    return (string)pathinfo($filename, PATHINFO_EXTENSION);
}

/**
 * Get directory contents
 *
 * @param string $dir directory path
 * @param bool $get_dirs get sub directories
 * @param bool $get_files
 * @param mixed $extension allowed file extensions
 * @param string $prefix file/dir path prefix
 * @return array $contents directory contents
 */
function fn_get_dir_contents($dir, $get_dirs = true, $get_files = false, $extension = '', $prefix = '', $recursive = false, $exclude = array())
{
    $current_dir = getcwd();

    $contents = array();
    $dir = realpath(rtrim($dir, '\\/'));

    if (is_dir($dir) || (is_link($dir) && is_dir(readlink($dir)))) {
        if ($dh = opendir($dir)) {

            // $extention - can be string or array. Transform to array.
            $extension = is_array($extension) ? $extension : array($extension);

            while (($file = readdir($dh)) !== false) {
                if ($file == '.' || $file == '..' || in_array($file, $exclude)) {
                    continue;
                }

                $full_path = $dir . '/' . $file;
                chdir($dir);

                $is_symlink = is_link($full_path);
                $is_dir = $is_symlink ? is_dir(readlink($full_path)) : is_dir($full_path);
                $is_file = $is_symlink ? is_file(readlink($full_path)) : is_file($full_path);

                if ($recursive && $is_dir) {
                    $contents = fn_array_merge($contents, fn_get_dir_contents($full_path, $get_dirs, $get_files, $extension, $prefix . $file . '/', $recursive, $exclude), false);
                }

                if (($is_dir && $get_dirs) || ($is_file && $get_files)) {
                    if ($get_files && !fn_is_empty($extension)) {
                        // Check all extentions for file
                        foreach ($extension as $_ext) {
                             if (substr($file, -strlen($_ext)) == $_ext) {
                                $contents[] = $prefix . $file;
                                break;
                             }
                        }
                    } else {
                        $contents[] = $prefix . $file;
                    }
                }
            }
            closedir($dh);
        }
    }

    asort($contents, SORT_STRING);

    chdir($current_dir);

    return $contents;
}

/**
 * Get file contents from local or remote filesystem
 *
 * @param string $location file location
 * @param string $base_dir
 * @return string $result
 */
function fn_get_contents($location, $base_dir = '')
{
    $result = '';
    $path = $base_dir . $location;

    if (!empty($base_dir) && !fn_check_path($path)) {
        return $result;
    }

    // Location is regular file
    if (is_file($path)) {
        $result = @file_get_contents($path);

    // Location is url
    } elseif (strpos($path, '://') !== false) {
        // Prepare url
        $url = new \Tygh\Tools\Url($path);
        $path = $url->build($url->getIsEncoded());

        if (Bootstrap::getIniParam('allow_url_fopen') == true) {
            $result = @file_get_contents($path);
        } else {
            $result = Http::get($path);
        }
    }

    return $result;
}

/**
 * Write a string to a file
 *
 * @param string $location file location
 * @param string $content
 * @param string $base_dir
 * @param int $file_perm File access permissions for setting after writing into the file. For example 0666.
 * @param boolean $append append content if set to true
 * @return string $result
 */
function fn_put_contents($location, $content, $base_dir = '', $file_perm = DEFAULT_FILE_PERMISSIONS, $append = false)
{
    $result = '';
    $path = $base_dir . $location;

    if (!empty($base_dir) && !fn_check_path($path)) {
        return false;
    }

    fn_mkdir(dirname($path));

    $flags = 0;
    if ($append == true) {
        $flags = FILE_APPEND;
    }

    // Location is regular file
    $result = @file_put_contents($path, $content, $flags);
    if ($result !== false) {
        @chmod($path, $file_perm);
    }

    return $result;
}

/**
 * Get data from url
 *
 * @param string $val
 * @return array $val
 */
function fn_get_url_data($val)
{
    if (!preg_match('/:\/\//', $val)) {
        $val = 'http://' . $val;
    }

    $result = false;
    $_data = fn_get_contents($val);

    if (!empty($_data)) {
        $result = array(
            'name' => fn_basename($val)
        );

        // Check if the file is dynamically generated
        if (strpos($result['name'], '&') !== false || strpos($result['name'], '?') !== false) {
            $result['name'] = 'url_uploaded_file_' . uniqid(TIME);
        }
        $result['path'] = fn_create_temp_file();
        $result['size'] = strlen($_data);

        $fd = fopen($result['path'], 'wb');
        fwrite($fd, $_data, $result['size']);
        fclose($fd);
        @chmod($result['path'], DEFAULT_FILE_PERMISSIONS);

        $result['type'] = fn_get_mime_content_type($result['path'], false, '');

        if (empty($result['type'])) {
            $result['type'] = fn_get_file_type($result['name']);
        }

        $cache = Registry::get('temp_fs_data');

        if (!isset($cache[$result['path']])) { // cache file to allow multiple usage
            $cache[$result['path']] = $result['path'];
            Registry::set('temp_fs_data', $cache);
        }
    }

    return $result;
}

/**
 * Function get local uploaded
 *
 * @param array $val
 * @staticvar array $cache
 * @return array
 */
function fn_get_local_data($val)
{
    $cache = Registry::get('temp_fs_data');

    if (!isset($cache[$val['path']])) { // cache file to allow multiple usage
        $tempfile = fn_create_temp_file();
        if (move_uploaded_file($val['path'], $tempfile) == true) {
            @chmod($tempfile, DEFAULT_FILE_PERMISSIONS);
            clearstatcache(true, $tempfile);
            $cache[$val['path']] = $tempfile;
        } else {
            $cache[$val['path']] = '';
        }

        Registry::set('temp_fs_data', $cache);
    }

    if (defined('KEEP_UPLOADED_FILES')) {
        $tempfile = fn_create_temp_file();
        fn_copy($cache[$val['path']], $tempfile);
        $val['path'] = $tempfile;
    } else {
        $val['path'] = $cache[$val['path']];
    }

    return !empty($val['size']) ? $val : false;
}

/**
 * Finds the last key in the array and applies the custom function to it.
 *
 * @param array $arr
 * @param string $fn
 * @param bool $is_first
 */
function fn_get_last_key(&$arr, $fn = '', $is_first = false)
{
    if (!is_array($arr)&&$is_first == true) {
        $arr = call_user_func($fn, $arr);

        return;
    }

    foreach ($arr as $k => $v) {
        if (is_array($v) && count($v)) {
            fn_get_last_key($arr[$k], $fn);
        } elseif (!is_array($v)&&!empty($v)) {
            $arr[$k] = call_user_func($fn, $arr[$k]);
        }
    }
}

/**
 * Filters data from instant file uploader
 * @param string $name name of uploaded data
 * @param array $filter_by_ext allow file extensions
 * @return mixed filtered file data on success, false otherwise
 */
function fn_filter_uploaded_data($name, $filter_by_ext = array())
{
    $udata_local = fn_rebuild_files('file_' . $name);
    $udata_other = !empty($_REQUEST['file_' . $name]) ? $_REQUEST['file_' . $name] : array();
    $utype = !empty($_REQUEST['type_' . $name]) ? $_REQUEST['type_' . $name] : array();

    if (empty($utype)) {
        return array();
    }

    $filtered = array();

    foreach ($utype as $id => $type) {
        if ($type == 'local' && !fn_is_empty(@$udata_local[$id])) {
            $filtered[$id] = fn_get_local_data(Bootstrap::stripSlashes($udata_local[$id]));

        } elseif ($type == 'server' && !fn_is_empty(@$udata_other[$id]) && (Registry::get('runtime.skip_area_checking') || AREA == 'A')) {
            fn_get_last_key($udata_other[$id], 'fn_get_server_data', true);
            $filtered[$id] = $udata_other[$id];

        } elseif ($type == 'url' && !fn_is_empty(@$udata_other[$id])) {
            fn_get_last_key($udata_other[$id], 'fn_get_url_data', true);
            $filtered[$id] = $udata_other[$id];
        }

        if (isset($filtered[$id]) && $filtered[$id] === false) {
            unset($filtered[$id]);
            fn_set_notification('E', __('error'), __('cant_upload_file'));
            continue;
        }

        if (!empty($filtered[$id]['name'])) {
            $filtered[$id]['name'] = \Tygh\Tools\SecurityHelper::sanitizeFileName(urldecode($filtered[$id]['name']));
            
            if (!fn_check_uploaded_data($filtered[$id], $filter_by_ext)) {
                unset($filtered[$id]);
            }
        }
    }

    static $shutdown_inited;

    if (!$shutdown_inited) {
        $shutdown_inited = true;
        register_shutdown_function('fn_remove_temp_data');
    }

    return $filtered;
}

/**
 * Filters data from instant file uploader
 * @param array $filter_by_ext allow file extensions
 * @return mixed filtered file data on success, false otherwise
 */
function fn_filter_instant_upload($filter_by_ext = array())
{
    if (!empty($_FILES['upload'])) {
        $_FILES['upload']['path'] = $_FILES['upload']['tmp_name'];
        $uploaded_data = fn_get_local_data(Bootstrap::stripSlashes($_FILES['upload']));
        if (fn_check_uploaded_data($uploaded_data, $filter_by_ext)) {
            return $uploaded_data;
        }
    }

    return false;
}

/**
 * Checks uploaded file can be processed
 * @param array $uploaded_data uploaded file data
 * @param array $filter_by_ext allowed file extensions
 * @return boolean true if file can be processed, false - otherwise
 */
function fn_check_uploaded_data($uploaded_data, $filter_by_ext)
{
    $result = true;
    $processed = false;

    /**
     * Actions before check uploaded data
     *
     * @param array $uploaded_data Uploaded data
     * @param array $filter_by_ext Allowed file extensions
     * @param bool  $result        Result status
     * @param bool  $processed     Processed flag
     */
    fn_set_hook('check_uploaded_data_pre', $uploaded_data, $filter_by_ext, $result, $processed);

    if ($processed) {
        return $result;
    }

    if (!empty($uploaded_data) && is_array($uploaded_data) && !empty($uploaded_data['name'])) {
        $ext = fn_get_file_ext($uploaded_data['name']);

        if (empty($ext)) {
            $types = fn_get_ext_mime_types('mime');
            $mime = fn_get_mime_content_type($uploaded_data['path']);

            $ext = isset($types[$mime]) ? $types[$mime] : '';
        }

        if (!$processed && !empty($filter_by_ext) && !in_array(fn_strtolower($ext), $filter_by_ext)) {
            fn_set_notification('E', __('error'), __('text_not_allowed_to_upload_file_extension', array(
                '[ext]' => $ext
            )));

            $result = false;
            $processed = true;
        }

        if (!$processed && in_array(fn_strtolower($ext), Registry::get('config.forbidden_file_extensions'))) {
            fn_set_notification('E', __('error'), __('text_forbidden_file_extension', array(
                '[ext]' => $ext
            )));

            $result = false;
            $processed = true;
        }

        $mime_type = fn_get_mime_content_type($uploaded_data['path'], true, 'text/plain');
        if (
            !$processed
            && !empty($uploaded_data['path'])
            && in_array($mime_type, Registry::get('config.forbidden_mime_types'))
        ) {
            fn_set_notification('E', __('error'), __('text_forbidden_file_mime', array(
                '[mime]' => $mime_type
            )));

            $result = false;
            $processed = true;
        }
    }

    /**
     * Actions after check uploaded data
     *
     * @param array $uploaded_data Uploaded data
     * @param array $filter_by_ext Allowed file extensions
     * @param bool  $result        Result status
     * @param bool  $processed     Processed flag
     */
    fn_set_hook('check_uploaded_data_post', $uploaded_data, $filter_by_ext, $result, $processed);

    return $result;
}

/**
 * Remove temporary files
 */
function fn_remove_temp_data()
{
    $fs_data = Registry::get('temp_fs_data');
    if (!empty($fs_data)) {
        foreach ($fs_data as $file) {
            fn_rm($file);
        }
    }
}

/**
 * Create temporary file
 *
 * @return string temporary file
 */
function fn_create_temp_file()
{
    $prefix = fn_get_cache_path(false);
    fn_mkdir($prefix . 'tmp');
    $tmpnam = fn_normalize_path(tempnam($prefix . 'tmp/', 'tmp_'));

    return $tmpnam;
}

/**
 * Returns correct path from url "path" component
 *
 * @param string $path
 * @return correct path
 */
function fn_get_url_path($path)
{
    $dir = dirname($path);

    if ($dir == '.' || $dir == '/') {
        return '';
    }

    return (defined('WINDOWS')) ? str_replace('\\', '/', $dir) : $dir;
}

/**
 * Check path to file
 *
 * @param string $path
 * @return bool
 */
function fn_check_path($path)
{
    $real_path = realpath($path);

    return str_replace('\\', '/', $real_path) == $path ? true : false;
}

/**
 * Gets line from file pointer and parse for CSV fields
 *
 * @param resource $f a valid file pointer to a file successfully opened by fopen(), popen(), or fsockopen().
 * @param int $length maximum line length
 * @param string $d field delimiter
 * @param string $q the field enclosure character
 * @return array structured data
 */
function fn_fgetcsv($f, $length, $d = ',', $q = '"')
{
    $list = array();
    $st = fgets($f, $length);
    if ($st === false || $st === null) {
        return $st;
    }

    if (trim($st) === '') {
        return array('');
    }

    $st = rtrim($st, "\n\r");
    if (substr($st, -strlen($d)) == $d) {
        $st .= '""';
    }

    while ($st !== '' && $st !== false) {
        if ($st[0] !== $q) {
            // Non-quoted.
            list ($field) = explode($d, $st, 2);
            $st = substr($st, strlen($field) + strlen($d));
        } else {
            // Quoted field.
            $st = substr($st, 1);
            $field = '';
            while (1) {
                // Find until finishing quote (EXCLUDING) or eol (including)
                preg_match("/^((?:[^$q]+|$q$q)*)/sx", $st, $p);
                $part = $p[1];
                $partlen = strlen($part);
                $st = substr($st, strlen($p[0]));
                $field .= str_replace($q . $q, $q, $part);
                if (strlen($st) && $st[0] === $q) {
                    // Found finishing quote.
                    list ($dummy) = explode($d, $st, 2);
                    $st = substr($st, strlen($dummy) + strlen($d));
                    break;
                } else {
                    // No finishing quote - newline.
                    $st = fgets($f, $length);
                }
            }
        }

        $list[] = $field;
    }

    return $list;
}

/**
 * Wrapper for rename with chmod
 *
 * @param string $oldname The old name. The wrapper used in oldname must match the wrapper used in newname.
 * @param string $newname The new name.
 * @param resource $context Note: Context support was added with PHP 5.0.0. For a description of contexts, refer to Stream Functions.
 *
 * @return boolean Returns TRUE on success or FALSE on failure.
 */
function fn_rename($oldname, $newname, $context = null)
{
    $result = ($context === null) ? rename($oldname, $newname) : rename($oldname, $newname, $context);
    if ($result !== false) {
        @chmod($newname, is_dir($newname) ? DEFAULT_DIR_PERMISSIONS : DEFAULT_FILE_PERMISSIONS);
    }

    return $result;
}

/*
 * Returns pathinfo with using UTF characters.
 *
 * @param string $path
 * @param string $encoding
 * @return array
 */
function fn_pathinfo($path, $encoding = 'UTF-8')
{
    $path = fn_unified_path($path);
    $basename = explode("/", $path);
    $basename = end($basename);

    if (strpos($path, '/') === false) {
        $path = './' . $path;
    }

    $dirname = rtrim(fn_substr($path, 0, fn_strlen($path, $encoding) - fn_strlen($basename, $encoding) - 1, $encoding), '/');
    $dirname .= empty($dirname) ? '/' : '';

    if (strpos($basename, '.') !== false) {
        $_name_components = explode('.', $basename);
        $extension = array_pop($_name_components);
        $filename = implode('.', $_name_components);
    } else {
        $extension = '';
        $filename = $basename;
    }

    return array (
        'dirname' => $dirname,
        'basename' => $basename,
        'extension' => $extension,
        'filename' => $filename
    );
}

/*
 * Returns basename with using UTF characters.
 *
 * @param string $path
 * @param string $suffix
 * @param string $encoding
 * @return string
 */
function fn_basename($path, $suffix = '', $encoding = 'UTF-8')
{
    $basename = explode("/", $path);
    $basename = end($basename);

    if (!empty($suffix) && fn_substr($basename, (0 - fn_strlen($suffix, $encoding)), fn_strlen($basename, $encoding), $encoding) == $suffix) {
        $basename = fn_substr($basename, 0, (0 - fn_strlen($suffix, $encoding)), $encoding);
    }

    /* Remove query params
        Original: http://somehost.com/images/test.jpg?12345678
        Bad result: test.jpg?12345678
        Correct result: test.jpg
    */

    list($basename) = explode('?', $basename);

    return $basename;
}

/**
 * Replace backslashes in windows-style path
 *
 * @param string $path path
 * @return string filtered path
 */
function fn_unified_path($path)
{
    if (defined('IS_WINDOWS')) {
        $path = str_replace('\\', '/', $path);
    }

    return $path;
}

/**
 * Connects to ftp server
 *
 * @param array $settings options
 * @param array $settings options
 * @return boolean true if connected successfully and working directory is correct, false - otherwise
 */
function fn_ftp_connect($settings, $show_notifications = false)
{
    $result = true;

    if (function_exists('ftp_connect')) {
        if (!empty($settings['ftp_hostname'])) {
            $ftp_port = !empty($settings['ftp_port']) ? $settings['ftp_port'] : '21';
            if (substr_count($settings['ftp_hostname'], ':') > 0) {
                $start_pos = strrpos($settings['ftp_hostname'], ':');
                $ftp_port = substr($settings['ftp_hostname'], $start_pos + 1);
                $settings['ftp_hostname'] = substr($settings['ftp_hostname'], 0, $start_pos);
            }

            $ftp = @ftp_connect($settings['ftp_hostname'], $ftp_port);
            if (!empty($ftp)) {
                if (@ftp_login($ftp, $settings['ftp_username'], $settings['ftp_password'])) {

                    ftp_pasv($ftp, true);

                    if (!empty($settings['ftp_directory'])) {
                        @ftp_chdir($ftp, $settings['ftp_directory']);
                    }

                    $files = ftp_nlist($ftp, '.');
                    if (!empty($files) && in_array('config.php', $files)) {
                        Registry::set('ftp_connection', $ftp);
                    } else {
                        if ($show_notifications) {
                            fn_set_notification('E', __('error'), __('text_uc_ftp_cart_directory_not_found'));
                        }
                        $result = false;
                    }
                } else {
                    if ($show_notifications) {
                        fn_set_notification('E', __('error'), __('text_uc_ftp_login_failed'));
                    }
                    $result = false;
                }
            } else {
                if ($show_notifications) {
                    fn_set_notification('E', __('error'), __('text_uc_ftp_connect_failed'));
                }
                $result = false;
            }
        }
    } else {
        if ($show_notifications) {
            fn_set_notification('E', __('error'), __('text_uc_no_ftp_module'));
        }
        $result = false;
    }

    return $result;
}

function fn_ftp_chmod_file($filename, $perm = DEFAULT_FILE_PERMISSIONS, $recursive = false)
{
    $result = false;

    $ftp = Registry::get('ftp_connection');
    if (is_resource($ftp)) {
        $filename = rtrim($filename, '/');

        $parent_directory = dirname($filename);
        $parent_directory = rtrim($parent_directory, '/') . '/'; // force adding trailing slash to path

        $rel_path = str_replace(Registry::get('config.dir.root') . '/', '', $parent_directory);
        $cdir = ftp_pwd($ftp);

        if (empty($rel_path)) { // if rel_path is empty, assume it's root directory
            $rel_path = $cdir;
        }

        if (@ftp_chdir($ftp, $rel_path)) {
            $ftp_chmod_command = 'CHMOD ' . sprintf('0%o', $perm) . ' ' . fn_basename($filename);
            $result = @ftp_site($ftp, $ftp_chmod_command);

            if ($recursive) {
                $path = fn_normalize_path($cdir . '/' . $rel_path . fn_basename($filename));

                if (is_dir($path)) {
                    $_files = fn_get_dir_contents($path, true, true, '', '', true);

                    if (!empty($_files)) {
                        foreach ($_files as $_file) {
                            fn_ftp_chmod_file($path . '/' . $_file, $perm, false);
                        }
                    }

                }
            }

            ftp_chdir($ftp, $cdir);
        }
    }

    return $result;
}

/**
 * Gets path user is allowed to put files to
 * @return string files path
 */
function fn_get_files_dir_path()
{
    $path = Registry::get('config.dir.files');
    $company_id = Registry::get('runtime.simple_ultimate') ? Registry::get('runtime.forced_company_id') : Registry::get('runtime.company_id');

    if (!empty($company_id)) {
        $path .=  $company_id . '/';
    }

    return $path;
}

/**
 * Gets HTTP path user is allowed to put files to
 * @return string files path
 */
function fn_get_http_files_dir_path()
{
    $path = fn_get_rel_dir(fn_get_files_dir_path());
    $path = Registry::get('config.http_location') . '/' . $path;

    return $path;
}

/**
 * Gets path to user public files
 * @return string public files path
 */
function fn_get_public_files_path()
{
    $path = Storage::instance('images')->getAbsolutePath('');
    $company_id = Registry::get('runtime.simple_ultimate') ? Registry::get('runtime.forced_company_id') : Registry::get('runtime.company_id');

    if (!empty($company_id)) {
        $path .=  'companies/' . $company_id . '/';
    }

    return $path;
}

/**
 * Gets directory path relative to root directory
 * @param string $dir absolute directory path
 * @return string relative directory path
 */
function fn_get_rel_dir($dir)
{
    return str_replace(
        rtrim(Registry::get('config.dir.root'), '\\/') . '/',
        '',
        $dir
    );
}

/**
 * Checks if folders/files can be copied to destination dir
 *
 * @param string $path path to Root add-on path
 * @return array List if non-writable directories
 */
function fn_check_copy_ability($source, $destination)
{
    $struct_files = fn_get_dir_contents($source, true, true, '', '', true);

    $non_writable = array();

    foreach ($struct_files as $file) {
        if (is_file($source . $file)) {
            $res = fn_check_writable_path_permissions(dirname($destination . '/' . $file));

            if ($res !== true) {
                $non_writable[$res] = true;
            }
        }
    }

    return $non_writable;
}

/**
 * Check if specified file path can be rewritten.
 *
 * Example:
 *      Base struct
 *          app                         r-x
 *              /addons                 r-x
 *                  /widget             rwx
 *                      addon.xml       rw-
 *              /core                   r-x
 *                  /functions          r-x
 *                      fn.addons.php   r--
 *          design                      rwx
 *              /index.tpl              rw-
 *
 * fn_check_writable_path_permissions(app/addons/widget/addon.xml)          true
 * fn_check_writable_path_permissions(app/core/functions/fn.addons.php)     app/core/functions/
 * fn_check_writable_path_permissions(app/core/functions/not_a_file.php)    app/core/functions/
 * fn_check_writable_path_permissions(design/index.tpl)                     true
 * fn_check_writable_path_permissions(design/test_file.tpl)                 true
 *
 * @param string $path Path to file
 * @return bool true of path is writable or (string) path to parent non-writable directory
 *
 */
function fn_check_writable_path_permissions($path)
{
    if (is_writable($path)) {
        $result = true;

    } elseif (is_dir($path)) {
        $result = $path;

    } else {
        $result = call_user_func(__FUNCTION__, dirname($path));
    }

    return $result;
}

/**
 * Copies files using FTP access
 *
 * @param string $source Absolute path (non-ftp) to source dir/file
 * @param string $destination Absolute path (non-ftp) to destination dir/file
 * @param array $ftp_access
 *      array(
 *          'hostname',
 *          'username',
 *          'password',
 *          'directory'
 *      )
 * @return bool true if all files were copied or (string) Error message
 */
function fn_copy_by_ftp($source, $destination, $ftp_access)
{
    try {
        $ftp = new Ftp;

        $ftp->connect($ftp_access['hostname']);
        $ftp->login($ftp_access['username'], $ftp_access['password']);
        $ftp->chdir($ftp_access['directory']);

        $files = $ftp->nlist('');
        if (!empty($files) && in_array('config.php', $files)) {
            $ftp_destination = str_replace(Registry::get('config.dir.root'), '', $destination);

            if (is_file($source)) { // File

                try {
                    $file = ltrim($ftp_destination, '/');
                    $ftp->put($file, $source, FTP_BINARY);
                } catch (FtpException $e) {
                    throw new FtpException('ftp_access_denied' . ':' . $e->getMessage());
                }

            } else { // Dir

                $ftp->chdir($ftp_access['directory'] . $ftp_destination);

                $struct = fn_get_dir_contents($source, false, true, '', '', true);

                foreach ($struct as $file) {
                    $dir = dirname($file);

                    if (!$ftp->isDir($dir)) {
                        try {
                            $ftp->mkDirRecursive($dir);
                        } catch (FtpException $e) {
                            throw new FtpException('ftp_access_denied' . ':' . $e->getMessage());
                        }
                    }

                    try {
                        $ftp->put($file, $source . $file, FTP_BINARY);
                    } catch (FtpException $e) {
                        throw new FtpException('ftp_access_denied' . ':' . $e->getMessage());
                    }
                }
            }

            return true;

        } else {
            throw new FtpException('ftp_directory_is_incorrect');
        }

    } catch (FtpException $e) {
        return __('invalid_ftp_access') . ': ' . $e->getMessage();
    }

    return false;
}

/**
 * Checks if path to directory/file is under base directory
 * @param string $base_dir base directory
 * @param string $path path to be checked
 * @return boolean true if path is valid, false - otherwise
 */
function fn_is_valid_path($base_dir, $path)
{
    $base_dir = rtrim($base_dir, '/') . '/';

    if (strpos($path, $base_dir) !== 0) {
        // relative path
        $path = fn_normalize_path($base_dir . $path);
    }

    if (strpos($path, $base_dir) !== 0) {
        return false;
    }

    return true;
}

/**
 * @param string $file_path Path to file
 *
 * @return string File's permissions, group and owner in format "drwxrwxrwx www-data:www-data"
 */
function fn_get_file_perms_info($file_path)
{
    clearstatcache(true, $file_path);

    return sprintf('%s %s:%s',
        fn_get_readable_file_perms(fileperms($file_path)),
        fn_get_server_username_by_id(fileowner($file_path)),
        fn_get_server_group_name_by_id(filegroup($file_path))
    );
}

/**
 * Converts file permissions to human-readable format.
 *
 * @param int $perms fileperms() function call result
 *
 * @return string Human-readable file permissions (drwxrwxrwx)
 */
function fn_get_readable_file_perms($perms)
{
    if (($perms & 0xC000) == 0xC000) {
        // Socket
        $info = 's';
    } elseif (($perms & 0xA000) == 0xA000) {
        // Symbolic link
        $info = 'l';
    } elseif (($perms & 0x8000) == 0x8000) {
        // Usual
        $info = '-';
    } elseif (($perms & 0x6000) == 0x6000) {
        // Special block
        $info = 'b';
    } elseif (($perms & 0x4000) == 0x4000) {
        // Dir
        $info = 'd';
    } elseif (($perms & 0x2000) == 0x2000) {
        // Special symbol
        $info = 'c';
    } elseif (($perms & 0x1000) == 0x1000) {
        // FIFO stream
        $info = 'p';
    } else {
        // Unknown
        $info = 'u';
    }

    // Owner
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040)
        ? (($perms & 0x0800) ? 's' : 'x')
        : (($perms & 0x0800) ? 'S' : '-'));

    // Group
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008)
        ? (($perms & 0x0400) ? 's' : 'x')
        : (($perms & 0x0400) ? 'S' : '-'));

    // World
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001)
        ? (($perms & 0x0200) ? 't' : 'x')
        : (($perms & 0x0200) ? 'T' : '-'));

    return $info;
}

/**
 * @param string $unix_user_id UNIX user ID
 *
 * @return string
 */
function fn_get_server_username_by_id($unix_user_id)
{
    if (function_exists('posix_getpwuid')) {
        $user_info = posix_getpwuid($unix_user_id);
        if (is_array($user_info) && isset($user_info['name'])) {
            return $user_info['name'];
        }
    }

    return $unix_user_id;
}

/**
 * @param string $unix_group_id UNIX group ID
 *
 * @return string
 */
function fn_get_server_group_name_by_id($unix_group_id)
{
    if (function_exists('posix_getgrgid')) {
        $group_info = posix_getgrgid($unix_group_id);
        if (is_array($group_info) && isset($group_info['name'])) {
            return $group_info['name'];
        }
    }

    return $unix_group_id;
}

/**
 * @return string Name of user that owns current PHP process
 */
function fn_get_process_owner_name()
{
    if (function_exists('posix_getuid')) {
        return fn_get_server_username_by_id(posix_getuid());
    } else {
        return (string)(getenv('USERNAME') ?: getenv('USER'));
    }
}

/**
 * Allows to fetch a list of parent directories for given path. This functions doesn't checks real filesystem
 * and operates only using given path string.
 *
 * @param string $path Path to file or directory
 *
 * @return array List of paths of parent directories
 */
function fn_get_parent_directory_stack($path)
{
    $directories = array();
    while ($path = dirname($path)) {
        if (!empty($path) && $path !== '.' && $path !== DIRECTORY_SEPARATOR) {
            $directories[] = rtrim($path, '\\/') . DIRECTORY_SEPARATOR;
        } else {
            break;
        }
    }

    return $directories;
}

/**
 * Checks if file or directory is writable.
 *
 * @param string $file_path Path to file or directory
 * @return bool
 */
function fn_is_writable($file_path)
{
    clearstatcache(true, $file_path);
    $is_writable = is_writable($file_path);

    // is_writable() is not always a reliable way to determine whether
    // file or directory are really writable for current PHP process,
    // so we should perform an additional check
    if ($is_writable) {
        if (is_dir($file_path)) { // For directories we try to create an empty file into it
            $test_filepath = $file_path . DIRECTORY_SEPARATOR . uniqid(mt_rand(0, 10000));

            if (@touch($test_filepath)) {
                @unlink($test_filepath);
            } else {
                $is_writable = false;
            }
        } elseif (is_file($file_path)) { // For files we try to modify the file by appending "nothing" to it
            if (false === @file_put_contents($file_path, null, FILE_APPEND)) {
                $is_writable = false;
            }
        }
    }

    return $is_writable;
}

/**
 * Finds file and return real path to it
 *
 * @param string $prefix path to search in
 * @param string $file Filename, can be URL, absolute or relative path
 * @return mixed String path to the file or false if file is not found.
 */
function fn_find_file($prefix, $file)
{
    $file = Bootstrap::stripSlashes($file);

    // Url
    if (strpos($file, '://') !== false) {
        return $file;
    }

    $prefix = fn_normalize_path(rtrim($prefix, '/'));
    $file = fn_normalize_path($file);
    $files_path = fn_get_files_dir_path();

    // Absolute path
    if (is_file($file) && strpos($file, $files_path) === 0) {
        return $file;
    }

    // Path is relative to files directory
    if (is_file($files_path . $file)) {
        return $files_path . $file;
    }

    // Path is relative to prefix inside files directory
    if (is_file($files_path . $prefix . '/' . $file)) {
        return $files_path . $prefix . '/' . $file;
    }

    // Prefix is absolute path
    if (strpos($prefix, $files_path) === 0 && is_file($prefix . '/' . $file)) {
        return $prefix . '/' . $file;
    }

    return false;
}
