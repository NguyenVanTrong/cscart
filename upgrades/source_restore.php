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

namespace Restore;

error_reporting(E_ALL);
ini_set('display_errors', 'on');
@set_time_limit(0);

$uc_settings = '%UC_SETTINGS%';
$config = '%CONFIG%';
$backup_filename = '%BACKUP_FILENAME%';
$uak = '%RESTORE_KEY%';
$stats_data = '%STATS_DATA%';
$restore_data = '%RESTORE_DATA%';

define('DIR_ROOT', $config['dir']['root']);
define('DEFAULT_FILE_PERMISSIONS', 0644);
define('DEFAULT_DIR_PERMISSIONS', 0755);


/**
 * Class RestoreAction
 * @package Restore
 */
class RestoreAction
{
    private $uak;
    private $config;
    private $restore_data;
    private $backup_filename;
    private $stats_data;

    public function __construct($config, $restore_data, $stats_data, $uc_settings, $backup_filename, $uak)
    {
        $this->config = $config;
        $this->restore_data = $restore_data;
        $this->stats_data = $stats_data;
        $this->uc_settings = $uc_settings;
        $this->backup_filename = $config['dir']['backups'] . $backup_filename;
        $this->uak = $uak;
    }

    public function checkAccess()
    {
        if (empty($_REQUEST['uak']) || $this->uak == "%RESTORE_KEY%" || $_REQUEST['uak'] != $this->uak) {
            echo $this->accessDeniedMessage();
            return false;
        }

        if (empty($_POST['confirm_restore']) || $_POST['confirm_restore'] != 'Y') {
            echo $this->confirmForm();
            return false;
        }

        return true;
    }

    public function run()
    {
        if (!empty($_REQUEST['ftp_hostname'])) {
            $this->uc_settings['ftp_hostname'] = $_REQUEST['ftp_hostname'];
        }
        if (!empty($_REQUEST['ftp_username'])) {
            $this->uc_settings['ftp_username'] = $_REQUEST['ftp_username'];
        }
        if (!empty($_REQUEST['ftp_password'])) {
            $this->uc_settings['ftp_password'] = $_REQUEST['ftp_password'];
        }
        if (!empty($_REQUEST['ftp_directory'])) {
            $this->uc_settings['ftp_directory'] = $_REQUEST['ftp_directory'];
        }
        $ftp = null;

        try {
            $output = new Output();
            $database = new Database($this->config['db_host'], $this->config['db_user'], $this->config['db_password'], $this->config['db_name']);

            $restore = new Restore(DIR_ROOT, $this->backup_filename, $this->getExtractPath(), $database);
            $restore->setCacheDirs(array(
                $this->config['dir']['cache_misc'],
                $this->config['dir']['cache_static'],
                $this->config['dir']['cache_registry'],
                $this->config['dir']['cache_templates'],
            ));
            $restore->setOutput($output);
            $restore->setFilePermission(DEFAULT_FILE_PERMISSIONS);
            $restore->setDirPermission(DEFAULT_DIR_PERMISSIONS);

            if (!empty($this->uc_settings['ftp_hostname'])) {
                $ftp = new FtpConnection(
                    DIR_ROOT,
                    $this->uc_settings['ftp_hostname'],
                    $this->uc_settings['ftp_username'],
                    $this->uc_settings['ftp_password'],
                    $this->uc_settings['ftp_directory']
                );

                $restore->setFtpConnection($ftp);
            }

            $this->sendStat();

            if ($restore->execute()) {
                echo $this->successMessage();
            }
        } catch (FtpConnectionException $e) {
            echo $this->errorMessage($e->getMessage());
            echo $this->ftpSettingsForm();
        } catch (FilePermissionException $e) {
            if ($ftp === null) {
                echo $this->errorMessage($e->getMessage());
                echo $this->errorMessage("Set the writable permissions manually or re-enter the FTP access to your server:<br>");
                echo $this->ftpSettingsForm();
            } else {
                echo $this->errorMessage("<strong>Unable to restore</strong>");
                echo $this->errorMessage($e->getMessage());
                echo $this->errorMessage("Set the writable permissions manually.");
            }
        } catch (\Exception $e) {
            echo $this->errorMessage("<strong>Unable to restore</strong>");
            echo $this->errorMessage($e->getMessage());
        }
    }

    private function getExtractPath()
    {
        $path = $this->config['dir']['cache_misc'] . 'tmp/backup/';
        return $path;
    }

    private function successMessage()
    {
        $url = $this->config['http_location'] . '/' . $this->config['admin_index'] . '?cc=1&ctpl=1';

        return '<br><strong>Restore completed</strong><br><br>'
        . '<a href="' . $url . '">Return to the administrator area</a><br>';
    }

    private function confirmForm()
    {
        return <<<HTML
<!DOCTYPE html>
<head>
    <title>Confirm revert process</title>
</head>
<body>
    <form action="" method="post">
        <input type="hidden" name="confirm_restore" value="Y">
        <input type="hidden" name="uak" value="{$this->uak}">

        <h2 style="color: red;">Attention! Your store files and database would be reverted to the backup created before the upgrade process.</h2>
        <p>System will be reverted to version <b>{$this->restore_data['backup']['created_on_version']}</b> using backup created at <b>{{$this->restore_data['backup']['created_at']}}</b>.</p>
        <p>If you are ready to proceed click the continue link: <button type="submit">Continue</button></p>
    </form>
</body>
HTML;
    }

    private function ftpSettingsForm()
    {
        return <<<FORM
    <form action="" method="post">
      <table>
        <tr>
          <td>FTP host:</td>
          <td><input type="text" name="ftp_hostname" value="{$this->uc_settings['ftp_hostname']}"></td>
        </tr>

        <tr>
          <td>FTP username:</td>
          <td><input type="text" name="ftp_username" value="{$this->uc_settings['ftp_username']}"></td>
        </tr>

        <tr>
          <td>FTP password:</td>
          <td><input type="text" name="ftp_password" value="{$this->uc_settings['ftp_password']}"></td>
        </tr>

        <tr>
          <td>FTP directory:</td>
          <td><input type="text" name="ftp_directory" value="{$this->uc_settings['ftp_directory']}"</td>
        </tr>
      </table>

      <input type="submit" value="Set FTP credentials">
      <input type="hidden" name="uak" value="{$this->uak}">
      <input type="hidden" name="confirm_restore" value="Y">
    </form>
FORM;
    }

    private function accessDeniedMessage()
    {
        return "Access denied";
    }

    private function errorMessage($message)
    {
        return $message . "<br>";
    }

    private function sendStat()
    {
        $revert_stats_url = $this->config['resources']['updates_server'] . "/index.php?dispatch=product_updates.reverted&" . http_build_query($this->stats_data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_URL, $revert_stats_url);
        curl_exec($ch);
        curl_close($ch);
    }
}

class Restore
{
    const SET_WRITABLE_PERMISSION = 0777;

    private $file;
    private $root_directory;
    private $extract_path;
    private $database;
    /** @var FtpConnection|null */
    private $ftp_connection;
    /** @var Output|null */
    private $output;

    private $files = null;
    private $file_permission = 0644;
    private $dir_permission = 0744;
    private $cache_dirs = array();

    public function __construct($root_directory, $file, $extract_path, Database $database)
    {
        if (!file_exists($file)) {
            throw new \Exception("Backup file not found.");
        }

        $file_ext = $this->getExt($file);

        if (!in_array($file_ext, array('zip'))) {
            throw new \Exception("Unsupported backup file type.");
        }

        $this->file = $file;
        $this->database = $database;
        $this->extract_path = $extract_path;
        $this->root_directory = rtrim($root_directory, '/') . '/';
    }

    public function execute()
    {
        if (!file_exists($this->extract_path) && !$this->mkdir($this->extract_path, self::SET_WRITABLE_PERMISSION)) {
            throw new FilePermissionException("Can not create directory \"{$this->extract_path}\".");
        }

        $this->checkWritable();
        $this->output("Extract files.");

        if ($this->extract()) {
            $items_for_delete = array(
                'app', 'design', 'var/themes_repository', 'var/langs', 'js'
            );

            $sql_dump_file = null;
            $remove_items = array();

            foreach ($this->getFiles() as $file) {
                $ext = $this->getExt($file);

                if ($ext == 'sql' && strpos($file, 'var/restore/') !== false) {
                    $sql_dump_file = $file;
                    continue;
                }

                foreach ($items_for_delete as $key => $item) {
                    if (strpos($file, $item) === 0) {
                        $remove_items[] = $file;
                        unset($items_for_delete[$key]);
                    }
                }
            }

            foreach ($remove_items as $item) {
                $result = $this->rm($this->root_directory . $item);
                $this->output("Remove file: {$item} " . ($result ? 'OK' : 'FAILED'));
            }

            foreach ($this->getFiles() as $file) {
                $this->output("Restore file: {$file}");
                $this->restoreFile($this->extract_path . $file, $this->root_directory . $file);
            }

            if ($sql_dump_file) {
                $this->restoreDatabase($this->extract_path . $sql_dump_file);
            }

            $this->rm($this->extract_path);

            foreach ($this->cache_dirs as $dir) {
                $this->rm($dir);
            }

            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
        } else {
            throw new \Exception("Unable extract files.");
        }

        return true;
    }

    public function setCacheDirs(array $cache_dirs)
    {
        $this->cache_dirs = $cache_dirs;
    }

    private function restoreFile($source, $destination)
    {
        $this->output(sprintf('Restoring "%s" to "%s"... ', $source, $destination));

        if (self::checkWritableFile($destination, false)) {
            if (is_dir($source)) {
                $result = $this->mkdir($destination);
                $this->output('Creating directory... ' . ($result ? 'OK' : 'FAILED'));
            } else {
                $result = $this->copy($source, $destination);
                $this->output('Copying file... ' . ($result ? 'OK' : 'FAILED'));
            }
        } else {
            $this->output('FAILED: destination path is not writable');
        }
    }

    private function getFiles()
    {
        if ($this->files === null) {
            $this->files = $this->getArchiveReader()->getFiles();
        }

        return $this->files;
    }

    private function extract()
    {
        return $this->getArchiveReader()->extractTo($this->extract_path);
    }

    private function getArchiveReader()
    {
        return new ZipReader($this->file);
    }

    private function checkWritable($restore_perms = true)
    {
        $files = $this->getFiles();

        foreach ($files as $file) {
            $file = $this->root_directory . $file;

            if (!$this->checkWritableFile($file, $restore_perms)) {
                throw new FilePermissionException("Cannot write to the file \"{$file}\".");
            }

            $this->output("Check permission file: {$file}");
        }
    }

    private function checkWritableFile($file, $restore_perms = true)
    {
        if (file_exists($file) || is_dir($file)) {
            if (!is_writable($file)) {
                $old_perms = substr(sprintf('%o', fileperms($file)), -4);
                $this->chmod($file, self::SET_WRITABLE_PERMISSION);

                if (is_writable($file)) {
                    if ($restore_perms) {
                        $this->chmod($file, intval($old_perms, 8));
                    }

                    return true;
                }

                return false;
            }
        } else {
            return $this->checkWritableFile(dirname($file), $restore_perms);
        }

        return true;
    }

    public function setFtpConnection(FtpConnection $ftp_connection)
    {
        $this->ftp_connection = $ftp_connection;
    }

    /**
     * @param null|Output $output
     */
    public function setOutput(Output $output)
    {
        $this->output = $output;
    }

    /**
     * @param int $file_permission
     */
    public function setFilePermission($file_permission)
    {
        $this->file_permission = $file_permission;
    }

    /**
     * @param int $dir_permission
     */
    public function setDirPermission($dir_permission)
    {
        $this->dir_permission = $dir_permission;
    }

    private function output($message)
    {
        if ($this->output) {
            $this->output->writeln($message);
        }
    }

    private function restoreDatabase($file)
    {
        if (file_exists($file)) {
            $f = fopen($file, 'r');

            if ($f) {
                $ret = array();
                $rest = '';

                while (!feof($f)) {
                    $str = $rest . fread($f, 1024);
                    $rest = $this->parseSqlQuery($ret, $str);

                    if (!empty($ret)) {
                        foreach ($ret as $query) {
                            if (preg_match('/CREATE\s+TABLE\s+`?(?<name>\w+)`?/i', $query, $matches)) {
                                $this->output('Restoring table ' . $matches['name']);
                            }

                            $this->database->query($query);
                        }

                        $ret = array();
                    }
                }

                fclose($f);
            }
        }
    }

    private function parseSqlQuery(&$ret, $sql)
    {
        $sql_len = strlen($sql);
        $char = '';
        $string_start = '';
        $in_string = FALSE;
        $time0 = time();

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
                for (; ;) {
                    $i = strpos($sql, $string_start, $i);
                    // No end of string found -> add the current substring to the
                    // returned array
                    if (!$i) {
//                    $ret[] = $sql;
                        return $sql;
                    }
                    // Backquotes or no backslashes before quotes: it's indeed the
                    // end of the string -> exit the loop
                    else if ($string_start == '`' || $sql[$i - 1] != '\\') {
                        $string_start = '';
                        $in_string = FALSE;
                        break;
                    } // one or more Backslashes before the presumed end of string...
                    else {
                        // ... first checks for escaped backslashes
                        $j = 2;
                        $escaped_backslash = FALSE;
                        while ($i - $j > 0 && $sql[$i - $j] == '\\') {
                            $escaped_backslash = !$escaped_backslash;
                            $j++;
                        }
                        // ... if escaped backslashes: it's really the end of the
                        // string -> exit the loop
                        if ($escaped_backslash) {
                            $string_start = '';
                            $in_string = FALSE;
                            break;
                        } // ... else loop
                        else {
                            $i++;
                        }
                    } // end if...elseif...else
                } // end for
            } // end if (in string)

            // We are not in a string, first check for delimiter...
            else if ($char == ';') {
                // if delimiter found, add the parsed part to the returned array
                $ret[] = substr($sql, 0, $i);
                $sql = ltrim(substr($sql, min($i + 1, $sql_len)));
                $sql_len = strlen($sql);
                if ($sql_len) {
                    $i = -1;
                } else {
                    // The submited statement(s) end(s) here
                    return '';
                }
            } // end else if (is delimiter)

            // ... then check for start of a string,...
            else if (($char == '"') || ($char == '\'') || ($char == '`')) {
                $in_string = TRUE;
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
        if (!empty($sql) && preg_match('/[^[:space:]]+/', $sql)) {
            return $sql;
        }

        return '';
    }

    public function rm($source)
    {
        return $this->rmByPhp($source) || $this->rmByFtp($source);
    }

    public function rmByPhp($source, $delete_root = true)
    {
        if (is_file($source)) {
            return @unlink($source);
        }

        if (is_dir($source) && $dir = dir($source)) {
            while (false !== $entry = $dir->read()) {
                // Skip pointers
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                if ($this->rmByPhp($source . '/' . $entry, true) == false) {
                    return false;
                }
            }
            // Clean up
            $dir->close();

            return $delete_root == true ? @rmdir($source) : true;
        } else {
            return false;
        }
    }

    public function rmByFtp($source)
    {
        if (empty($this->ftp_connection)) {
            return false;
        }

        $connection = $this->ftp_connection->getConnection();
        ftp_chdir($connection, $this->ftp_connection->getDefaultDirectory());

        if (is_dir($source)) {
            $dir = rtrim($source, '/');
            $rel_path = $this->ftp_connection->getRealPath($dir);

            ftp_chdir($connection, $rel_path);
            $files = ftp_nlist($connection, '-a');

            if (!empty($files)) {
                foreach ($files as $file) {
                    if (in_array($file, array('.', '..'))) {
                        continue;
                    }

                    if (!$this->rmByFtp($dir . '/' . $file)) {
                        return false;
                    }
                }
            }

            ftp_chdir($connection, $this->ftp_connection->getDefaultDirectory());

            return @ftp_rmdir($connection, $rel_path);
        } else {
            $rel_path = $this->ftp_connection->getRealPath(dirname($source));

            return @ftp_delete($connection, $rel_path . basename($source));
        }
    }

    public function mkdir($directory, $permission = null)
    {
        if ($permission === null) {
            $permission = $this->dir_permission;
        }

        return $this->mkdirByPhp($directory, $permission) || $this->mkdirByFtp($directory, $permission);
    }

    public function mkdirByPhp($directory, $permission)
    {
        if (empty($directory)) {
            return false;
        }

        clearstatcache();

        if (@is_dir($directory)) {
            return true;
        }
        // Truncate the full path to related to avoid problems with some buggy hostings
        if (strpos($directory, $this->root_directory) === 0) {
            $directory = './' . substr($directory, strlen($this->root_directory));
            $old_dir = getcwd();
            chdir($this->root_directory);
        }

        $dir = $this->normalizePath($directory, '/');
        $path = '';
        $dir_arr = array();

        if (strstr($dir, '/')) {
            $dir_arr = explode('/', $dir);
        } else {
            $dir_arr[] = $dir;
        }

        $dir_arr = array_filter($dir_arr);

        foreach ($dir_arr as $k => $v) {
            $path .= (empty($k) ? '' : '/') . $v;
            clearstatcache();

            if (is_dir($path)) {
                continue;
            }

            umask(0);

            if (!@mkdir($path, $permission)) {
                $parent_dir = dirname($path);
                $parent_perms = fileperms($parent_dir);

                @chmod($parent_dir, 0777);

                if (!@mkdir($path, $permission)) {
                    return false;
                }

                @chmod($parent_dir, $parent_perms);
            }
        }

        if (!empty($old_dir)) {
            @chdir($old_dir);
        }

        return true;
    }

    public function mkdirByFtp($directory, $permission)
    {
        if (empty($this->ftp_connection)) {
            return false;
        }

        $directory = rtrim($directory, '/');
        $connection = $this->ftp_connection->getConnection();
        $rel_path = $this->ftp_connection->getRealPath($directory);

        $dir_arr = explode('/', $rel_path);
        $dir_arr = array_filter($dir_arr);
        $path = '';

        foreach ($dir_arr as $dir) {
            $path .= (empty($path) ? '' : '/') . $dir;

            $items = ftp_nlist($connection, dirname($path));

            if (!in_array($path, $items)) {
                if (@ftp_mkdir($connection, $path)) {
                    $this->chmodByFtp($path, $permission);
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    public function copy($source, $destination)
    {
        $file_name = basename($source);

        if (!file_exists($destination)) {
            if (basename($destination) == $file_name) { // if we're copying the file, create parent directory
                $this->mkdir(dirname($destination));
            } else {
                $this->mkdir($destination);
            }
        }

        if (is_dir($destination)) {
            $destination .= '/' . basename($source);
        }

        if ($this->copyByPhp($source, $destination) || $this->copyByFtp($source, $destination)) {
            $this->chmod($destination);
            return true;
        }

        return false;
    }

    public function copyByPhp($source, $destination)
    {
        if (is_writable($destination) || (is_writable(dirname($destination)) && !file_exists($destination))) {
            return copy($source, $destination);
        }

        return false;
    }

    public function copyByFtp($source, $destination)
    {
        if (empty($this->ftp_connection)) {
            return false;
        }

        $connection = $this->ftp_connection->getConnection();

        if (!is_dir($destination)) {
            $filename = basename($destination);
            $destination = dirname($destination);
        } else {
            $filename = basename($source);
        }

        $rel_path = $this->ftp_connection->getRealPath($destination);

        if (ftp_chdir($connection, $rel_path) && @ftp_put($connection, $filename, $source, FTP_BINARY)) {
            ftp_chdir($connection, $this->ftp_connection->getDefaultDirectory());

            return true;
        }

        return false;
    }

    public function chmod($file, $permission = null)
    {
        if ($permission === null) {
            $permission = $this->file_permission;
        }

        return $this->chmodByPhp($file, $permission) || $this->chmodByFtp($file, $permission);
    }

    public function chmodByPhp($file, $permission)
    {
        return @chmod($file, $permission);
    }

    public function chmodByFtp($file, $permission)
    {
        if (empty($this->ftp_connection)) {
            return false;
        }

        $connection = $this->ftp_connection->getConnection();
        $rel_path = $this->ftp_connection->getRealPath(dirname($file));

        if (ftp_chdir($connection, $rel_path)) {
            @ftp_site($connection, 'CHMOD ' . sprintf('0%o', $permission) . ' ' . basename($file));
            ftp_chdir($connection, $this->ftp_connection->getDefaultDirectory());

            return true;
        }

        return false;
    }

    private function normalizePath($path, $separator)
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

        $tmp = array_filter($result);

        return empty($tmp) ? '' : implode($separator, $result);
    }

    private function getExt($file)
    {
        return (string) pathinfo($file, PATHINFO_EXTENSION);
    }
}


class FtpConnection
{
    const DEFAULT_PORT = 21;

    private $hostname;
    private $port;
    private $username;
    private $password;
    private $directory;
    private $root_directory;
    private $default_directory;
    /** @var Resource  */
    private $connection;

    public function __construct($root_directory, $hostname, $username, $password, $directory, $port = null)
    {
        if (!function_exists('ftp_connect')) {
            throw new \Exception("No FTP module was found");
        }

        if ($port === null) {
            $port = self::DEFAULT_PORT;
        }

        if (strpos($hostname, ':') !== false) {
            $start_pos = strrpos($hostname, ':');
            $port = substr($hostname, $start_pos + 1);
            $hostname = substr($hostname, 0, $start_pos);
        }

        $this->root_directory = $root_directory;
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->directory = $directory;
        $this->port = $port;

        $this->connect();
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function __destruct()
    {
        $this->close();
    }

    private function connect()
    {
        $this->connection = @ftp_connect($this->hostname, $this->port);

        if (empty($this->connection)) {
            throw new FtpConnectionException("FTP connection failed");
        }

        if (@ftp_login($this->connection, $this->username, $this->password)) {
            ftp_pasv($this->connection, true);

            if (!empty($this->directory)) {
                @ftp_chdir($this->connection, $this->directory);
            }

            $files = ftp_nlist($this->connection, '.');
            $this->default_directory = ftp_pwd($this->connection);

            if (empty($files) || !in_array('config.php', $files)) {
                throw new FtpConnectionException("Directory with CS-Cart was not found on your server");
            }
        } else {
            throw new FtpConnectionException("FTP log in failed");
        }
    }

    private function close()
    {
        ftp_close($this->connection);
    }

    public function getRealPath($path)
    {
        $path = rtrim($path, '/') . '/';

        $rel_path = str_replace($this->root_directory . '/', '', $path);

        if (empty($rel_path)) { // if rel_path is empty, assume it's root directory
            $rel_path = $this->default_directory;
        }

        return $rel_path;
    }

    public function getDefaultDirectory()
    {
        return $this->default_directory;
    }
}

class FtpConnectionException extends \Exception
{

}

class Database
{
    const CONNECTION_TYPE_PDO = 'pdo';
    const CONNECTION_TYPE_MYSQLI = 'mysqli';

    private $host;
    private $user;
    private $password;
    private $dbname;
    /** @var \mysqli|\PDO */
    private $connection;
    private $connection_type;

    public function __construct($host, $user, $password, $dbname)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->dbname = $dbname;

        $this->connect();
        $this->query("SET NAMES UTF8, sql_mode = \"\"");
    }

    public function query($sql)
    {
        return $this->connection->query($sql);
    }

    private function connect()
    {
        if (class_exists('mysqli')) {
            $this->connection = new \mysqli($this->host, $this->user, $this->password, $this->dbname);

            if ($this->connection->connect_errno) {
                throw new \Exception(printf("Unable to connect: %s\n", $this->connection->connect_error));
            }
            $this->connection_type = self::CONNECTION_TYPE_MYSQLI;
        } elseif (class_exists('PDO') && in_array('mysql', \PDO::getAvailableDrivers(), true)) {
            $this->connection = new \PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->user, $this->password);
            $this->connection_type = self::CONNECTION_TYPE_PDO;
        } else {
            throw new \Exception("Required mysqli or pdo_mysql.");
        }
    }
}

class FilePermissionException extends \Exception
{

}

class ZipReader
{
    /** @var string  */
    protected $file;

    public function __construct($file)
    {
        if (!class_exists('ZipArchive')) {
            throw new \Exception("PHP extension zip is required.");
        }

        $this->file = $file;
    }

    /**
     * @inheritDoc
     */
    public function extractTo($dir)
    {
        $result = false;
        $zip = new \ZipArchive();

        if ($zip->open($this->file) === true) {
            $result = $zip->extractTo($dir);
        }
        $zip->close();
        $zip = null;

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getFiles($only_root = false)
    {
        $files = array();
        $zip = new \ZipArchive;

        if ($zip->open($this->file)) {
            $num_files = $zip->numFiles;

            $counter = 0;
            for ($i = 0; $i < $num_files; $i++) {
                $file = $zip->getNameIndex($i);
                $parent_directories = $this->getParentDirStack($file);
                if ($only_root) {
                    if (empty($parent_directories)) {
                        $files[$file] = $counter++;
                    } else {
                        $files[end($parent_directories)] = $counter++;
                    }
                } else {
                    $files[$file] = $counter++;
                    foreach ($parent_directories as $parent_dir_path) {
                        $files[$parent_dir_path] = $counter++;
                    }
                }
            }

            $files = array_flip($files);
            $zip->close();
        }
        $zip = null;
        sort($files);

        return $files;
    }

    /**
     * @param string $path
     * @return array
     */
    private function getParentDirStack($path)
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
}

class Output
{
    public function writeln($message)
    {
        echo $message . '<br>';
    }
}

$action = new RestoreAction($config, $restore_data, $stats_data, $uc_settings, $backup_filename, $uak);

if ($action->checkAccess()):
?>
<script type="text/javascript">
    interval_id = window.setInterval(function () {
        window.scrollTo(0,document.body.scrollHeight);
    }, 300);
</script>
<?php
$action->run();
?>
<script type="text/javascript">
    window.scrollTo(0,document.body.scrollHeight);
    clearInterval(interval_id);
</script>
<?php
endif;