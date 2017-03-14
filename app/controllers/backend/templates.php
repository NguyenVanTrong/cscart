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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

Tygh::$app['session']['current_path'] = empty(Tygh::$app['session']['current_path']) ? '' : preg_replace('/^\//', '', Tygh::$app['session']['current_path']);
$current_path = Tygh::$app['session']['current_path'];

// Disable debug console
Tygh::$app['view']->debugging = false;
$message = array();

$root_dir = fn_te_get_root('full');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'edit') {
        fn_trusted_vars('file_content');

        $file_path = fn_te_normalize_path($_REQUEST, $root_dir);

        $is_forbidden_ext = in_array(fn_strtolower(fn_get_file_ext($file_path)), Registry::get('config.forbidden_file_extensions'));

        if (fn_te_check_path($file_path) && @is_writable($file_path) && !$is_forbidden_ext) {
            fn_put_contents($file_path, $_REQUEST['file_content']);

            fn_set_notification('N', __('notice'), __('text_file_saved', array(
                '[file]' => fn_basename($file_path)
            )));

            Tygh::$app['ajax']->assign('saved', true);

            // Clear template cache of updated template for the customer front-end
            $view = Tygh::$app['view'];
            $view->setArea('C', '', Registry::get('runtime.company_id'));

            $updated_template_path = str_replace($view->getTemplateDir(0), '', $file_path);
            $view->clearCompiledTemplate($updated_template_path);

            $view->setArea(AREA, '', Registry::get('runtime.company_id'));

        } else {
            fn_set_notification('E', __('error'), __('cannot_write_file', array(
                '[file]' => fn_get_rel_dir($file_path)
            )));
        }

        exit;
    }

    if ($mode == 'upload_file') {
        $uploaded_data = fn_filter_uploaded_data('uploaded_data');
        $pname = fn_normalize_path($root_dir . $_REQUEST['path'] . '/');

        foreach ((array) $uploaded_data as $udata) {
            if (fn_te_check_path($pname)) {
                if (!fn_copy($udata['path'], $pname . $udata['name'])) {
                    fn_set_notification('E', __('error'), __('cannot_write_file', array(
                        '[file]' => fn_get_rel_dir($pname . $udata['name'])
                    )));
                }
            }
        }

        return array(CONTROLLER_STATUS_OK, "templates.manage");
    }

}

if ($mode == 'manage') {

    if (!empty($_REQUEST['selected_path'])) {
        Tygh::$app['view']->assign('selected_path', '/' . fn_te_form_path($_REQUEST['selected_path']));
    }

    Tygh::$app['view']->assign('rel_path', fn_te_get_root('rel'));

} elseif ($mode == 'init_view') {
    $dir = $_REQUEST['dir'];
    $tpath = fn_normalize_path($root_dir . $dir);

    if (fn_te_check_path($tpath) === false || !file_exists($tpath)) {
        $tpath = $root_dir;
        $current_path = '';
        $dir = '';
    }

    @clearstatcache();

    if (is_file($tpath)) {
        $content_filename = fn_basename($tpath);
    }

    if (is_file($tpath) || !is_dir($tpath)) {
        $tpath = dirname($tpath);
    }

    if (file_exists($tpath)) {
        $files_list = '';

        $show_path = '/' . fn_te_form_path(str_replace(rtrim($root_dir, '/'), '', $tpath));
        if ($show_path == '/') {
            $show_path = array('');
        } else {
            $show_path = explode('/', $show_path);
        }

        $base_path = rtrim($root_dir, '/');

        foreach ($show_path as $id => $path) {

            if ($path !== '') {
                $base_path .= '/' . fn_te_form_path($path);
            }
            $last_object = false;

            $items = fn_te_read_dir($base_path, $root_dir);

            Tygh::$app['view']->assign('current_path', fn_normalize_path(str_replace($root_dir, '', $base_path)));
            Tygh::$app['view']->assign('items', $items);

            $current_path = empty($dir) ? '' : ($dir . '/');
            $current_path = fn_normalize_path($current_path);

            if (isset($show_path[$id + 1])) {
                Tygh::$app['view']->assign('active_object', $show_path[$id + 1]);
            }

            if (!empty($content_filename)) {
                if (!isset($show_path[$id + 1])) {
                    foreach ($items as $item) {
                        if ($item['name'] == $content_filename) {
                            Tygh::$app['view']->assign('active_object', $content_filename);
                            $last_object = true;
                        }
                    }
                }
            } elseif (!isset($show_path[$id + 2])) {
                $last_object = true;
            }

            Tygh::$app['view']->assign('last_object', $last_object);

            $_list = Tygh::$app['view']->fetch('views/templates/components/file_list.tpl');
            if (!empty($files_list)) {
                $files_list = str_replace('<!--render_place-->', $_list, $files_list);
            } else {
                $files_list = $_list;
            }
        }

        Tygh::$app['ajax']->assign('files_list', $files_list);
    }
    exit;

} elseif ($mode == 'browse') {
    $dir = empty($_REQUEST['dir']) ? '' : '/' . $_REQUEST['dir'];
    $tpath = fn_normalize_path($root_dir . $dir);

    if (fn_te_check_path($tpath) === false) {
        $tpath = $root_dir;
        $current_path = '';
        $dir = '';
    }

    $current_path = empty($dir) ? '' : ($dir . '/');
    $current_path = fn_normalize_path($current_path);

    $items = fn_te_read_dir($tpath, $root_dir);

    Tygh::$app['view']->assign('current_path', str_replace($root_dir, '', $tpath));
    Tygh::$app['view']->assign('items', $items);

    Tygh::$app['ajax']->assign('current_path', str_replace($root_dir, '', $tpath));
    Tygh::$app['ajax']->assign('files_list', Tygh::$app['view']->fetch('views/templates/components/file_list.tpl'));
    exit;

} elseif ($mode == 'delete_file') {
    $fname = fn_te_normalize_path($_REQUEST, $root_dir);
    $fn_name = @is_dir($fname) ? 'fn_rm': 'unlink';
    $object = @is_dir($fname) ? 'directory' : 'file';

    if ($object == 'directory' && empty($_REQUEST['file_path'])) {
        // Delete theme
        fn_delete_theme(fn_basename($_REQUEST['file']));

    } elseif (!in_array(fn_strtolower(fn_get_file_ext($fname)), Registry::get('config.forbidden_file_extensions'))) {
        if (fn_te_check_path($fname) && @$fn_name($fname)) {
            fn_set_notification('N', __('notice'), __("text_{$object}_deleted", array(
                "[{$object}]" => fn_basename($fname)
            )));
        } else {
            fn_set_notification('E', __('error'), __("text_cannot_delete_{$object}", array(
                "[{$object}]" => fn_basename($fname)
            )));
        }

    } else {
        fn_set_notification('E', __('error'), __('you_have_no_permissions'));
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'templates.init_view?dir=' . $_REQUEST['file_path']);

} elseif ($mode == 'rename_file') {

    $pname = fn_te_normalize_path($_REQUEST, $root_dir);
    $pname_to = dirname($pname) . '/' . fn_basename($_REQUEST['rename_to']);

    $object = @is_dir($pname) ? 'directory' : 'file';
    $ext_from = fn_get_file_ext($pname);
    $ext_to = fn_get_file_ext($pname_to);

    if (in_array(fn_strtolower($ext_from), Registry::get('config.forbidden_file_extensions')) || in_array(fn_strtolower($ext_to), Registry::get('config.forbidden_file_extensions'))) {
        fn_set_notification('E', __('error'), __('text_forbidden_file_extension', array(
            '[ext]' => $ext_to
        )));
    } elseif (fn_te_check_path($pname) && fn_rename($pname, $pname_to)) {
        fn_set_notification('N', __('notice'), __("text_{$object}_renamed", array(
            "[{$object}]" => fn_basename($pname),
            "[to_{$object}]" => fn_basename($pname_to)
        )));
    } else {
        fn_set_notification('E', __('error'), __("text_cannot_rename_{$object}", array(
            "[{$object}]" => fn_basename($pname),
            "[to_{$object}]" => fn_basename($pname_to)
        )));
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'templates.init_view?dir=' . $_REQUEST['file_path']);

} elseif ($mode == 'create_file') {

    $file_path = fn_te_normalize_path($_REQUEST, $root_dir);
    $file_info = fn_pathinfo($file_path);

    if (in_array(fn_strtolower($file_info['extension']), Registry::get('config.forbidden_file_extensions')) || empty($file_info['filename'])) {
        fn_set_notification('E', __('error'), __('text_forbidden_file_extension', array(
            '[ext]' => $file_info['extension']
        )));

    } elseif (fn_te_check_path($file_path) && @touch($file_path)) {
        fn_te_chmod($file_path, DEFAULT_FILE_PERMISSIONS, false);

        fn_set_notification('N', __('notice'), __('text_file_created', array(
            '[file]' => fn_basename($file_path),
        )));

    } else {

        fn_set_notification('E', __('error'), __('text_cannot_create_file', array(
            '[file]' => fn_basename($file_path),
        )));
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'templates.init_view?dir=' . $_REQUEST['file_path']);

} elseif ($mode == 'create_folder') {

    $folder_path = fn_te_normalize_path($_REQUEST, $root_dir);

    if (fn_te_check_path($folder_path) && fn_mkdir($folder_path)) {

        fn_set_notification('N', __('notice'), __('text_directory_created', array(
            '[directory]' => fn_basename($folder_path)
        )));

    } else {

        fn_set_notification('E', __('error'), __('text_cannot_create_directory', array(
            '[directory]' => fn_basename($folder_path)
        )));
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'templates.init_view?dir=' . $_REQUEST['file_path']);

} elseif ($mode == 'get_file') {

    $pname = fn_te_normalize_path($_REQUEST, $root_dir);

    if (fn_te_check_path($pname)) {
        if (is_file($pname) && !in_array(fn_strtolower(fn_get_file_ext($pname)), Registry::get('config.forbidden_file_extensions'))) {
            fn_get_file($pname);
        }
    }

    exit;

} elseif ($mode == 'edit') {

    $fname = fn_te_normalize_path($_REQUEST, $root_dir);

    if (fn_te_check_path($fname) && !in_array(fn_strtolower(fn_get_file_ext($fname)), Registry::get('config.forbidden_file_extensions'))) {
        Tygh::$app['ajax']->assign('content', fn_get_contents($fname));
    } else {
        fn_set_notification('E', __('error'), __('you_have_no_permissions'));
    }

    exit;

} elseif ($mode == 'restore') {
    $copied = false;
    $file_path = fn_te_normalize_path($_REQUEST, $root_dir);

    if (fn_te_check_path($file_path)) {

        $repo_path = str_replace($root_dir, fn_te_get_root('repo'), $file_path);

        if (!file_exists($repo_path) && fn_get_theme_path('[theme]') != Registry::get('config.base_theme') && is_dir(fn_get_theme_path('[repo]/[theme]'))) {
            $repo_path = preg_replace("/\/themes_repository\/(\w+)\//", "/themes_repository/" . Registry::get('config.base_theme') . "/", $repo_path);
        }

        $object_base = is_file($repo_path) ? 'file' : (is_dir($repo_path) ? 'directory' : '');

        if (!empty($object_base) && fn_copy($repo_path, $file_path)) {

            fn_set_notification('N', __('notice'), __("text_{$object_base}_restored", array(
                "[{$object_base}]" => fn_basename($file_path),
            )));

            Tygh::$app['ajax']->assign('content', fn_get_contents($file_path));

            $copied = true;
        }
    }

    if (!$copied) {
        $object_base = empty($object_base) ? 'file' : $object_base;

        fn_set_notification('E', __('error'), __("text_cannot_restore_{$object_base}", array(
            "[{$object_base}]" => fn_basename($file_path),
        )));
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'templates.init_view?dir=' . $_REQUEST['file_path'] . '/' . $_REQUEST['file']);
}

/**
 * Checks if working path is inside root directory
 * @param string $path working path
 * @return boolean true of success, false - otherwise
 */
function fn_te_check_path($path)
{
    $path = fn_normalize_path($path);
    $base_dir = fn_te_get_root('full');

    return (strpos($path, $base_dir) === 0 && !fn_te_filter_path($path));
}

/**
 * Gets root directory
 * @param string $type path type: full - full path, rel - relative path from root directory, repo - repository path
 * @return string path
 */
function fn_te_get_root($type)
{
    if (fn_allowed_for('MULTIVENDOR') || Registry::get('runtime.company_id')) {
        $extra_path = '[theme]/';
    } else {
        $extra_path = '';
    }

    if ($type == 'full') {
        $path = fn_get_theme_path('[themes]/' . $extra_path, 'C');
    } elseif ($type == 'rel') {
        $path = fn_get_theme_path('/[relative]/' . $extra_path, 'C');
    } elseif ($type == 'repo') {
        $path = fn_get_theme_path('[repo]/' . $extra_path, 'C');
    }

    fn_set_hook('te_get_root', $type, $path);

    return $path;
}

/**
 * Reads directory
 * @param string $path path to directory
 * @param string $base_path path to root directory
 * @return array list of directories/files
 */
function fn_te_read_dir($path, $base_path)
{
    $items = array();
    clearstatcache();
    $path = rtrim($path, '/');

    if ($dh = @opendir($path)) {
        $dirs = array();
        $files = array();

        while (($file = readdir($dh)) !== false) {
            if ($file == '.' || $file == '..' || fn_te_filter_path($path . '/' . $file)) {
                continue;
            }

            if (is_dir($path . '/' . $file)) {
                $dirs[$file] = array(
                    'name' => $file,
                    'type' => 'D',
                    'full_path' => fn_te_form_path(str_replace($base_path, '', $path . '/' . $file)),
                    'path' => fn_te_form_path(str_replace($base_path, '', $path . '/'))
                );
            }

            if (is_file($path . '/' .$file)) {
                $files[$file] = array(
                    'name' => $file,
                    'type' => 'F',
                    'ext' => fn_get_file_ext($file),
                    'full_path' => fn_te_form_path(str_replace($base_path, '', $path . '/' . $file)),
                    'path' => fn_te_form_path(str_replace($base_path, '', $path . '/'))
                );
            }
        }

        closedir($dh);

        ksort($dirs, SORT_STRING);
        ksort($files, SORT_STRING);

        $items = fn_array_merge($dirs, $files, false);
    }

    return $items;
}

/**
 * Filters path/files to exclude from list
 * @param string $path path to check
 * @return boolean true to exclude, false - otherwise
 */
function fn_te_filter_path($path)
{
    $filter = array();

    $fileext_filter = Registry::get('config.forbidden_file_extensions');

    $filename = basename($path);

    fn_set_hook('te_filter_path', $filter, $path, $fileext_filter);

    if (in_array(fn_get_file_ext($filename), $fileext_filter)) {
        return true;
    }

    if (!empty($filter)) {
        foreach ($filter as $f) {
            if (strpos($path, $f) === 0) {
                return true;
            }
        }
    }

    return false;
}

function fn_te_form_path($path)
{
    return trim($path, '/');
}

function fn_te_normalize_path($request, $base_path)
{
    $file = $request['file'];
    $file_path = $request['file_path'];

    return fn_normalize_path($base_path . $file_path . '/' . $file);
}

function fn_te_chmod($source, $perms = DEFAULT_DIR_PERMISSIONS, $recursive = false)
{
    // Simple copy for a file
    if (is_file($source) || $recursive == false) {
        $res = @chmod($source, $perms);

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

            if (fn_te_chmod($source . '/' . $entry, $perms, true) == false) {
                return false;
            }
        }
        // Clean up
        $dir->close();

        return @chmod($source, $perms);
    } else {
        return false;
    }
}
