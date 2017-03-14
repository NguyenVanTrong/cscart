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

error_reporting(E_ALL);
ini_set('display_errors', 'on');
set_time_limit(0);

// Do not delete comments below
//[params]

//[/params]


if (empty($_REQUEST['uak']) || !isset($uak) || $_REQUEST['uak'] != $uak) {
    die('Access denied');
}

if (empty($_POST['confirm_restore']) || $_POST['confirm_restore'] != 'Y') {
    $confirm_form = <<<HTML
<!DOCTYPE html>
<head>
<title>Confirm revert process</title>
</head>
<body>
<form action="restore.php" method="post">
<input type="hidden" name="confirm_restore" value="Y">
<input type="hidden" name="uak" value="{$uak}">
<h2 style="color: red;">Attention! Your store will be reverted to the backup created before the upgrade process.</h2>
If you are ready to proceed click the continue link: <button type="submit">Continue</button>
</form>
</body>
HTML;

    echo $confirm_form;
    exit;
}

define('DIR_ROOT', $config['dir']['root']);
define('DEFAULT_FILE_PERMISSIONS', 0644);
define('DEFAULT_DIR_PERMISSIONS', 0755);
define('AREA', 'A');

Registry::set('config.dir.backups', $config['dir']['backups']);
Registry::set('config.dir.cache_misc', $config['dir']['cache_misc']);
Registry::set('config.dir.root', $config['dir']['root']);
Registry::set('config.resources.updates_server', $config['resources']['updates_server']);
Registry::set('settings.Upgrade_center', $uc_settings);

if (!empty($_REQUEST['ftp_hostname'])) {
  $uc_settings['ftp_hostname'] = $_REQUEST['ftp_hostname'];
}
if (!empty($_REQUEST['ftp_username'])) {
  $uc_settings['ftp_username'] = $_REQUEST['ftp_username'];
}
if (!empty($_REQUEST['ftp_password'])) {
  $uc_settings['ftp_password'] = $_REQUEST['ftp_password'];
}
if (!empty($_REQUEST['ftp_directory'])) {
  $uc_settings['ftp_directory'] = $_REQUEST['ftp_directory'];
}

include_once($config['dir']['root'] . '/app/Tygh/DataKeeper.php');
include_once($config['dir']['root'] . '/app/Tygh/Validators.php');

db_connect($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']);

// Set auto-scroller
$scroller =<<<SCR
  <script type="text/javascript">
    interval_id = window.setInterval(function(){
      window.scrollTo(0,document.body.scrollHeight);
    }, 300);
  </script>
SCR;

echo $scroller;

db_query('SET NAMES UTF8, sql_mode = ""');

if (\Tygh\DataKeeper::restore($backup_filename)) {
    fn_rm($config['dir']['cache_templates']);

    $url = $config['http_location'] . '/' . $config['admin_index'];

    fn_echo('<br><strong>Restore completed</strong><br><br>');
    fn_echo('<a href="' . $url . '">Return to the administrator area</a>');
} else {
  fn_echo('<br><strong>Unable to restore</strong>');
}

// Collect statictics
$stats_data = '%STATS_DATA%';

$revert_stats_url = $config['resources']['updates_server'] . "/index.php?dispatch=product_updates.reverted&" . http_build_query($stats_data);
$ch = curl_init();
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPGET, 1);
curl_setopt($ch, CURLOPT_URL, $revert_stats_url);
curl_exec($ch);
curl_close($ch);

// Stop auto-scroller
$scroller =<<<SCR
  <script type="text/javascript">
    window.scrollTo(0,document.body.scrollHeight);
    clearInterval(interval_id);
  </script>
SCR;

echo $scroller;

die;

/**
 * Copy file taking into account accessibility via php/ftp
 *
 * @param string $source source file
 * @param string $dest destination file/directory
 * @return boolean true if directory copied correctly, false - otherwise
 */
function fn_copy($source, $dest)
{
    $result = false;
    $file_name = basename($source);

    if (!file_exists($dest)) {
        if (basename($dest) == $file_name) { // if we're copying the file, create parent directory
            fn_mkdir(dirname($dest));
        } else {
            fn_mkdir($dest);
        }
    }

    fn_echo(' .');

    if (is_writable($dest) || (is_writable(dirname($dest)) && !file_exists($dest))) {
        if (is_dir($dest)) {
            $dest .= '/' . basename($source);
        }
        $result = copy($source, $dest);
        fn_uc_chmod_file($dest);
    }

    if (!$result) { // try ftp
        $result = fn_uc_ftp_copy($source, $dest);
    }

    return $result;
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
    $ftp = Registry::get('uc_ftp');
    if (is_resource($ftp)) {
        if (!is_dir($dest)) { // file
            $dest = dirname($dest);
        }
        $dest = rtrim($dest, '/') . '/'; // force adding trailing slash to path
        $rel_path = str_replace(DIR_ROOT . '/', '', $dest);
        $cdir = ftp_pwd($ftp);
        if (empty($rel_path)) { // if rel_path is empty, assume it's root directory
            $rel_path = $cdir;
        }
        if (ftp_chdir($ftp, $rel_path) && ftp_put($ftp, basename($source), $source, FTP_BINARY)) {
            $ext = fn_get_file_ext($source);
            @ftp_site($ftp, "CHMOD " . (fn_get_file_ext($source) == 'php' ? '0644' : sprintf('0%o', DEFAULT_FILE_PERMISSIONS)) . " " . basename($source));
            $result = true;
            ftp_chdir($ftp, $cdir);
        }
    }
    return $result;
}

function fn_uc_chmod_file($filename)
{
    $ext = fn_get_file_ext($filename);
    $perm = ($ext == 'php' ? 0644 : DEFAULT_FILE_PERMISSIONS);

    $result = @chmod($filename, $perm);

    if (!$result) {
        $ftp = Registry::get('uc_ftp');
        if (is_resource($ftp)) {
            $dest = dirname($filename);
            $dest = rtrim($dest, '/') . '/'; // force adding trailing slash to path

            $rel_path = str_replace(DIR_ROOT . '/', '', $dest);
            $cdir = ftp_pwd($ftp);

            if (empty($rel_path)) { // if rel_path is empty, assume it's root directory
                $rel_path = $cdir;
            }

            if (ftp_chdir($ftp, $rel_path)) {
                $result = @ftp_site($ftp, "CHMOD " . sprintf('0%o', $perm) . " " . basename($filename));
                ftp_chdir($ftp, $cdir);
            }
        }
    }

    return $result;
}

/**
 * Upgrade database
 *
 * @param string $path directory with database file
 * @param bool $track track executed queries yes/no
 * @return boolean always true
 */
function db_import_sql_file($path)
{
    $executed_queries = array();

    if (file_exists($path)) {
        $f = fopen($path, 'r');
        if ($f) {
            $ret = array();
            $rest = '';
            while (!feof($f)) {
                $str = $rest . fread($f, 1024);
                $rest = fn_parse_queries($ret, $str);

                if (!empty($ret)) {
                    foreach ($ret as $query) {
                        if (!in_array($query, $executed_queries)) {
                            fn_echo(' .');
                            db_query($query);
                        }
                    }

                    $ret = array();
                }
            }

            fclose($f);
        }
    }

    return true;
}


/**
 * Connect to ftp server
 *
 * @param array $uc_settings upgrade center options
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

// ------------------------------

class Registry
{
    private static $storage;

    public static function set($k, &$v)
    {
        self::$storage[$k] = &$v;

        return true;
    }

    public static function get($k)
    {
        return isset(self::$storage[$k])? self::$storage[$k] : NULL;
    }
}

function fn_echo($value)
{
    echo $value;
    fn_flush();
}

function fn_get_file_ext($filename)
{
    $i = strrpos($filename, '.');
    if ($i === false) {
        return '';
    }

    return substr($filename, $i + 1);
}

function fn_parse_queries(&$ret, $sql)
{
    $sql_len      = strlen($sql);
    $char         = '';
    $string_start = '';
    $in_string    = FALSE;
    $time0        = time();

    $i = -1;
    while ($i < $sql_len) {
        $i++;
        if (!isset($sql[$i])) {
            return $sql;
        }
        $char = $sql[$i];


        // We are in a string, check for not escaped end of strings except for
        // backquotes that can't be escaped
        if ($in_string) {
            for (;;) {
                $i         = strpos($sql, $string_start, $i);
                // No end of string found -> add the current substring to the
                // returned array
                if (!$i) {
//                    $ret[] = $sql;
                    return $sql;
                }
                // Backquotes or no backslashes before quotes: it's indeed the
                // end of the string -> exit the loop
                else if ($string_start == '`' || $sql[$i - 1] != '\\') {
                    $string_start      = '';
                    $in_string         = FALSE;
                    break;
                }
                // one or more Backslashes before the presumed end of string...
                else {
                    // ... first checks for escaped backslashes
                    $j                     = 2;
                    $escaped_backslash     = FALSE;
                    while ($i- $j > 0 && $sql[$i - $j] == '\\') {
                        $escaped_backslash = !$escaped_backslash;
                        $j++;
                    }
                    // ... if escaped backslashes: it's really the end of the
                    // string -> exit the loop
                    if ($escaped_backslash) {
                        $string_start  = '';
                        $in_string     = FALSE;
                        break;
                    }
                    // ... else loop
                    else {
                        $i++;
                    }
                } // end if...elseif...else
            } // end for
        } // end if (in string)

        // We are not in a string, first check for delimiter...
        else if ($char == ';') {
            // if delimiter found, add the parsed part to the returned array
            $ret[]      = substr($sql, 0, $i);
            $sql        = ltrim(substr($sql, min($i + 1, $sql_len)));
            $sql_len    = strlen($sql);
            if ($sql_len) {
                $i = -1;
            } else {
                // The submited statement(s) end(s) here
                return '';
            }
        } // end else if (is delimiter)

        // ... then check for start of a string,...
        else if (($char == '"') || ($char == '\'') || ($char == '`')) {
            $in_string    = TRUE;
            $string_start = $char;
        } // end else if (is start of string)

        // ... for start of a comment (and remove this comment if found)...
        else if ($char == '#' || ($i > 1 && $sql[$i - 2] . $sql[$i - 1] == '--')) {
            $sql = substr($sql, strpos($sql, "\n") + 1);
            $sql_len = strlen($sql);
            $i = -1;
        } // end else if (is comment)
    } // end for

    // add any rest to the returned array
    if (!empty($sql) && ereg('[^[:space:]]+', $sql)) {
        return $sql;
    }

    return '';
}

function db_query($query)
{
  $mysqli = Registry::get('mysqli');
  return $mysqli->query($query);
}

function fn_mkdir($dir, $perms = DEFAULT_DIR_PERMISSIONS)
{
    $result = false;

    // Truncate the full path to related to avoid problems with
    // some buggy hostings
    if (strpos($dir, DIR_ROOT) === 0) {
        $dir = './' . substr($dir, strlen(DIR_ROOT) + 1);
        $old_dir = getcwd();
        chdir(DIR_ROOT);
    }

    if (!empty($dir)) {
        $result = true;
        if (@!is_dir($dir)) {
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
                if (!@is_dir($path)) {
                    umask(0);
                    mkdir($path, $perms);
                }
            }
        }
    }

    if (!empty($old_dir)) {
        chdir($old_dir);
    }

    return $result;
}

function fn_normalize_path($path, $separator = '/')
{

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

    return fn_is_empty($result) ? '' : implode($separator, $result);
}

function fn_rm($source, $delete_root = true, $pattern = '')
{
    // Simple copy for a file
    if (is_file($source)) {
        $res = true;
        if (empty($pattern) || (!empty($pattern) && preg_match('/' . $pattern . '/', basename($source)))) {
            $res = @unlink($source);
        }

        return $res;
    }

    // Loop through the folder
    if (is_dir($source)) {
        $dir = dir($source);
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


function fn_is_empty($var)
{
    if (!is_array($var)) {
        return (empty($var));
    } else {
        foreach ($var as $k => $v) {
            if (empty($v)) {
                unset($var[$k]);
                continue;
            }

            if (is_array($v) && fn_is_empty($v)) {
                unset($var[$k]);
            }
        }

        return (empty($var)) ? true : false;
    }
}

function fn_get_dir_contents($dir, $get_dirs = true, $get_files = false, $extension = '', $prefix = '')
{

    $contents = array();
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {

            // $extention - can be string or array. Transform to array.
            $extension = is_array($extension) ? $extension : array($extension);

            while (($file = readdir($dh)) !== false) {
                if ($file == '.' || $file == '..' || $file{0} == '.') {
                    continue;
                }

                if ((is_dir($dir . '/' . $file) && $get_dirs == true) || (is_file($dir . '/' . $file) && $get_files == true)) {
                    if ($get_files == true && !fn_is_empty($extension)) {
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

    return $contents;
}

function db_connect($db_host, $db_user, $db_password, $db_name)
{
  $mysqli = new \mysqli($db_host, $db_user, $db_password, $db_name);

  if ($mysqli->connect_errno) {
    printf("Unable to connect: %s\n", $mysqli->connect_error);
    exit();
  }

  Registry::set('mysqli', $mysqli);
}

function fn_ftp_chmod_file($filename, $perm = DEFAULT_FILE_PERMISSIONS, $recursive = false)
{
    $result = false;

    $ftp = Registry::get('ftp_connection');
    if (is_resource($ftp)) {
        $dest = dirname($filename);
        $dest = rtrim($dest, '/') . '/'; // force adding trailing slash to path

        $rel_path = str_replace(Registry::get('config.dir.root') . '/', '', $dest);
        $cdir = ftp_pwd($ftp);

        if (empty($rel_path)) { // if rel_path is empty, assume it's root directory
            $rel_path = $cdir;
        }

        if (@ftp_chdir($ftp, $rel_path)) {
            $result = @ftp_site($ftp, 'CHMOD ' . sprintf('0%o', $perm) . ' ' . basename($filename));

            ftp_chdir($ftp, $cdir);
        }
    }

    return $result;
}

function fn_tr($l, $replacement = array())
{
  global $uc_settings, $uak; // BAD BAD BAD :(

    $form =<<<FORM
    <form action="restore.php" method="post">
      <table>
        <tr>
          <td>FTP host:</td>
          <td><input type="text" name="ftp_hostname" value="{$uc_settings['ftp_hostname']}"></td>
        </tr>

        <tr>
          <td>FTP username:</td>
          <td><input type="text" name="ftp_username" value="{$uc_settings['ftp_username']}"></td>
        </tr>

        <tr>
          <td>FTP password:</td>
          <td><input type="text" name="ftp_password" value="{$uc_settings['ftp_password']}"></td>
        </tr>

        <tr>
          <td>FTP directory:</td>
          <td><input type="text" name="ftp_directory" value="{$uc_settings['ftp_directory']}"</td>
        </tr>
      </table>

      <input type="submit" value="Set FTP credentials">
      <input type="hidden" name="uak" value="{$uak}">
      <input type="hidden" name="confirm_restore" value="Y">
    </form>
FORM;

    $lang = array(
        'text_uc_ftp_cart_directory_not_found' => 'Directory with CS-Cart was not found on your server',
        'text_uc_ftp_login_failed' => 'FTP log in failed',
        'text_uc_ftp_connect_failed' => 'FTP connection failed',
        'text_uc_no_ftp_module' => 'No FTP module was found',
        'error_select_db' => 'Database selection failed',
        'error_connect_db' => 'Database connection failed',
        'text_connecting_to_ftp' => '<br /><br /><b>Connecting to FTP...</b>&nbsp;',
        'ok' => 'OK',
        'text_copying_files' => '<br /><br /><b>Restoring files...</b>&nbsp;',
        'text_removing_obsolete_files' => '<br /><br /><b>Removing obsolete files...</b>&nbsp;',
        'text_updating_database' => '<br /><br /><b>Updating database...</b>&nbsp;',
        'done' => '<br /><br /><b>Done: your store was restored successfully</b>&nbsp;',
        'text_uc_unable_to_remove_upgrade_lock' => 'Failed to remove the upgrade lock file. Please remove the [file] file.',
        'check_permissions' => 'Check permissions',
        'restore' => 'Restore',
        'error' => 'Error',
        'datakeeper.file_cannot_be_overrided' => "Cannot write to the file <b>[file]</b>. Set the writable permissions manually or re-enter the FTP access to your server:<br>$form",
    );

    $str = $lang[$l];

    if (!empty($str)) {
      foreach ($replacement as $key => $value) {
        $str = str_replace($key, $value, $str);
      }
    }

    return $str;
}

function __($l, $replacement = array()) {
  fn_echo(fn_tr($l, $replacement));
}

function fn_decompress_files($archive_name, $dirname = '')
{
    if (empty($dirname)) {
        $dirname = Registry::get('config.dir.files');
    }

    $ext = fn_get_file_ext($archive_name);

    try {
        // We cannot use PharData for ZIP archives. All extracted data looks broken after extract.
        if ($ext == 'zip') {
            $zip = new \ZipArchive;

            $zip->open($archive_name);
            $zip->extractTo($dirname);
            $zip->close();

        } elseif ($ext == 'tgz' || $ext == 'gz') {
            if (!class_exists('PharData')) {
                fn_set_notification('E', __('error'), __('error_class_phar_data_not_found'));

                return false;
            }

            $phar = new \PharData($archive_name);
            $phar->extractTo($dirname, null, true); // extract all files, and overwrite
        }

    } catch (Exception $e) {
        fn_set_notification('E', __('error'), __('unable_to_unpack_file'));

        return false;
    }

    return true;
}

function fn_set_progress($prop, $value, $extra = null)
{
    if ($prop == 'echo') {
        fn_echo($value);
        fn_echo('<br>');
    }

    return true;
}

function fn_flush()
{
    if (function_exists('ob_flush')) {
        @ob_flush();
    }

    flush();
}

function fn_print_r()
{
    static $count = 0;
    $args = func_get_args();

    if (!empty($args)) {
        echo '<ol style="font-family: Courier; font-size: 12px; border: 1px solid #dedede; background-color: #efefef; float: left; padding-right: 20px;">';
        foreach ($args as $k => $v) {
            $v = htmlspecialchars(print_r($v, true));
            if ($v == '') {
                $v = '    ';
        }

            echo '<li><pre>' . $v . "\n" . '</pre></li>';
        }
        echo '</ol><div style="clear:left;"></div>';
    }
    $count++;
}

function fn_print_die()
{
    $args = func_get_args();
    call_user_func_array('fn_print_r', $args);
    die();
}

function fn_url($url)
{
  return $url;
}

function fn_set_notification($type, $title, $message)
{
  $message = $title . ': ' . $message;

  echo '<br>' . $message . '<br>';
}

function fn_get_cache_path($relative = true, $area = AREA, $company_id = null)
{
    $path = Registry::get('config.dir.cache_misc');

    if ($relative) {
        $path = str_replace(Registry::get('config.dir.root') . '/', '', $path);
    }

    return $path;
}
