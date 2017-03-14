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
use Tygh\Registry;
use Tygh\Storage;
use Tygh\Tools\Url;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// Set line endings autodetection
ini_set('auto_detect_line_endings', true);
set_time_limit(0);
fn_define('DB_LIMIT_SELECT_ROW', 30);

if (empty(Tygh::$app['session']['export_ranges'])) {
    Tygh::$app['session']['export_ranges'] = array();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $suffix = '';

    $layout_data = !empty($_REQUEST['layout_data']) ? $_REQUEST['layout_data'] : array();
    //
    // Select layout
    //
    if ($mode == 'set_layout') {
        db_query("UPDATE ?:exim_layouts SET active = 'N' WHERE pattern_id = ?s", $layout_data['pattern_id']);
        db_query("UPDATE ?:exim_layouts SET active = 'Y' WHERE layout_id = ?i", $layout_data['layout_id']);

        return array(CONTROLLER_STATUS_OK, 'exim.export?section=' . $_REQUEST['section'] . '&pattern_id=' . $layout_data['pattern_id']);
    }

    //
    // Store layout
    //
    if ($mode == 'store_layout') {

        if (!empty($layout_data['cols'])) {
            $layout_data['cols'] = implode(',', $layout_data['cols']);

            // Update current layout
            if ($action == 'save_as') {
                unset($layout_data['layout_id']);
                if (!empty($layout_data['name'])) {
                    $layout_data['active'] = 'Y';
                    $layout_data['options'] = serialize($_REQUEST['export_options']);
                    db_query("UPDATE ?:exim_layouts SET active = 'N' WHERE pattern_id = ?s", $layout_data['pattern_id']);
                    db_query("INSERT INTO ?:exim_layouts ?e", $layout_data);

                    return array(CONTROLLER_STATUS_OK, 'exim.export?section=' . $_REQUEST['section'] . '&pattern_id=' . $layout_data['pattern_id']);
                }
            } else {
                if (!empty($layout_data['layout_id'])) {
                    unset($layout_data['name']);
                    db_query("UPDATE ?:exim_layouts SET ?u WHERE layout_id = ?i", $layout_data, $layout_data['layout_id']);
                }
            }
        }

        return array(CONTROLLER_STATUS_OK, 'exim.export?section=' . $_REQUEST['section'] . '&pattern_id=' . $layout_data['pattern_id']);
    }

    //
    // Delete layout
    //
    if ($mode == 'delete_layout') {
        db_query("DELETE FROM ?:exim_layouts WHERE layout_id = ?i", $layout_data['layout_id']);

        return array(CONTROLLER_STATUS_OK, 'exim.export?section=' . $_REQUEST['section'] . '&pattern_id=' . $layout_data['pattern_id']);
    }

    //
    // Perform export
    //
    if ($mode == 'export') {
        $_suffix = '';
        if (!empty($layout_data['cols'])) {
            $pattern = fn_exim_get_pattern_definition($layout_data['pattern_id'], 'export');

            if (empty($pattern)) {
                fn_set_notification('E', __('error'), __('error_exim_pattern_not_found'));
                exit;
            }

            if (!empty(Tygh::$app['session']['export_ranges'][$pattern['section']])) {
                if (empty($pattern['condition']['conditions'])) {
                    $pattern['condition']['conditions'] = array();
                }

                $pattern['condition']['conditions'] = fn_array_merge($pattern['condition']['conditions'], Tygh::$app['session']['export_ranges'][$pattern['section']]['data']);
            }

            if (fn_export($pattern, $layout_data['cols'], $_REQUEST['export_options']) == true) {

                fn_set_notification('N', __('notice'), __('text_exim_data_exported'));

                // Direct download
                if ($_REQUEST['export_options']['output'] == 'D') {
                    $url = fn_url("exim.get_file?filename=" . rawurlencode($_REQUEST['export_options']['filename']), 'A', 'current');

                // Output to screen
                } elseif ($_REQUEST['export_options']['output'] == 'C') {
                    $url = fn_url("exim.get_file?to_screen=Y&filename=" . rawurlencode($_REQUEST['export_options']['filename']), 'A', 'current');
                }

                if (defined('AJAX_REQUEST') && !empty($url)) {
                    Tygh::$app['ajax']->assign('force_redirection', $url);

                    exit;
                }

                $url = empty($url) ? fn_url('exim.export?section=' . $_REQUEST['section']) : $url;

                return array(CONTROLLER_STATUS_OK, $url);

            } else {
                $delete_range_url = fn_url("exim.delete_range?section=$pattern[section]&pattern_id=$pattern[pattern_id]");
                fn_set_notification('E', __('error'), __('error_exim_no_data_exported_new', array("[url]" => $delete_range_url)));
            }
        } else {
            fn_set_notification('E', __('error'), __('error_exim_fields_not_selected'));
        }

        exit;
    }

    //
    // Perform import
    //
    if ($mode == 'import') {
        $file = fn_filter_uploaded_data('csv_file');

        if (!empty($file)) {
            if (empty($_REQUEST['pattern_id'])) {
                fn_set_notification('E', __('error'), __('error_exim_pattern_not_found'));
            } else {
                $pattern = fn_exim_get_pattern_definition($_REQUEST['pattern_id'], 'import');

                if (($data = fn_exim_get_csv($pattern, $file[0]['path'], $_REQUEST['import_options'])) != false) {

                    fn_import($pattern, $data, $_REQUEST['import_options']);
                }
            }
        } else {
            fn_set_notification('E', __('error'), __('error_exim_no_file_uploaded'));
        }

        return array(CONTROLLER_STATUS_OK, 'exim.import?section=' . $_REQUEST['section'] . '&pattern_id=' . $_REQUEST['pattern_id']);
    }

    if ($mode == 'delete_file' && !empty($_REQUEST['filename'])) {
        $file = fn_basename($_REQUEST['filename']);
        fn_rm(fn_get_files_dir_path() . $file);

        return array(CONTROLLER_STATUS_REDIRECT);

    }

    if ($mode == 'delete_range') {
        unset(Tygh::$app['session']['export_ranges'][$_REQUEST['section']]);

        return array(CONTROLLER_STATUS_REDIRECT, 'exim.export?section=' . $_REQUEST['section'] . '&pattern_id=' . $_REQUEST['pattern_id']);
    }

    exit;
}

if ($mode == 'export') {

    if (empty($_REQUEST['section'])) {
        $_REQUEST['section'] = 'products';
    }

    list($sections, $patterns) = fn_exim_get_patterns($_REQUEST['section'], 'export');

    if (empty($sections) && empty($patterns) || (isset($_REQUEST['section']) && empty($sections[$_REQUEST['section']]))) {
        return array(CONTROLLER_STATUS_DENIED);
    }

    $pattern_id = (empty($_REQUEST['pattern_id']) || empty($patterns[$_REQUEST['pattern_id']])) ? key($patterns) : $_REQUEST['pattern_id'];

    foreach ($patterns as $p_id => $p) {
        Registry::set('navigation.tabs.' . $p_id, array (
            'title' => $p['name'],
            'href' => "exim.export?pattern_id=" . $p_id . '&section=' . $_REQUEST['section'],
            'ajax' => true
        ));
    }

    if (!empty(Tygh::$app['session']['export_ranges'][$_REQUEST['section']])) {
        $key = key(Tygh::$app['session']['export_ranges'][$_REQUEST['section']]['data']);
        if (!empty($key)) {
            Tygh::$app['view']->assign('export_range', count(Tygh::$app['session']['export_ranges'][$patterns[$pattern_id]['section']]['data'][$key]));
            Tygh::$app['view']->assign('active_tab', Tygh::$app['session']['export_ranges'][$_REQUEST['section']]['pattern_id']);
        }
    }

    // Get available layouts
    $layouts = db_get_array("SELECT * FROM ?:exim_layouts WHERE pattern_id = ?s", $pattern_id);

    // Extract columns information
    foreach ($layouts as $k => $v) {
        $layouts[$k]['cols'] = explode(',', $v['cols']);
        $layouts[$k]['options'] = unserialize($v['options']);

        if ($v['active'] == 'Y' && !empty($v['options'])) {
            foreach ($layouts[$k]['options'] as $option => $value) {
                if (isset($patterns[$pattern_id]['options'][$option])) {
                    $patterns[$pattern_id]['options'][$option]['default_value'] = $value;
                }
            }
        }
    }

    // Get export files
    $export_files = fn_get_dir_contents(fn_get_files_dir_path(), false, true);
    $result = array();

    foreach ($export_files as $file) {
        $result[] = array (
            'name' => $file,
            'size' => filesize(fn_get_files_dir_path() . $file),
        );
    }

    // Export languages
    foreach (fn_get_translation_languages() as $lang_code => $lang_data) {
        $export_langs[$lang_code] = $lang_data['name'];
    }

    Registry::set('navigation.dynamic.sections', $sections);
    Registry::set('navigation.dynamic.active_section', $_REQUEST['section']);

    Tygh::$app['view']->assign('export_files', $result);
    Tygh::$app['view']->assign('files_rel_dir', fn_get_rel_dir(fn_get_files_dir_path()));

    Tygh::$app['view']->assign('pattern', $patterns[$pattern_id]);
    Tygh::$app['view']->assign('layouts', $layouts);

    Tygh::$app['view']->assign('export_langs', $export_langs);

} elseif ($mode == 'import') {

    if (empty($_REQUEST['section'])) {
        $_REQUEST['section'] = 'products';
    }

    list($sections, $patterns) = fn_exim_get_patterns($_REQUEST['section'], 'import');

    if (empty($sections) && empty($patterns) || (isset($_REQUEST['section']) && empty($sections[$_REQUEST['section']]))) {
        return array(CONTROLLER_STATUS_DENIED);
    }

    $pattern_id = empty($_REQUEST['pattern_id']) ? key($patterns) : $_REQUEST['pattern_id'];

    foreach ($patterns as $p_id => $p) {
        Registry::set('navigation.tabs.' . $p_id, array (
            'title' => $p['name'],
            'href' => "exim.import?pattern_id=" . $p_id . '&section=' . $_REQUEST['section'],
            'ajax' => true
        ));
    }

    Registry::set('navigation.dynamic.sections', $sections);
    Registry::set('navigation.dynamic.active_section', $_REQUEST['section']);

    unset($patterns[$pattern_id]['options']['lang_code']);
    Tygh::$app['view']->assign('pattern', $patterns[$pattern_id]);
    Tygh::$app['view']->assign('sections', $sections);

} elseif ($mode == 'get_file' && !empty($_REQUEST['filename'])) {
    $file = fn_basename($_REQUEST['filename']);

    if (!empty($_REQUEST['to_screen'])) {
        header("Content-type: text/plain");
        readfile(fn_get_files_dir_path() . $file);
        exit;
    } else {
        fn_get_file(fn_get_files_dir_path() . $file);
    }

} elseif ($mode == 'select_range') {
    Tygh::$app['session']['export_ranges'][$_REQUEST['section']] = array (
        'pattern_id' => $_REQUEST['pattern_id'],
        'data' => array(),
    );
    $pattern = fn_exim_get_pattern_definition($_REQUEST['pattern_id']);

    return array(CONTROLLER_STATUS_REDIRECT, $pattern['range_options']['selector_url']);

}

/**
 * @deprecated In favour of use fn_exim_put_csv function.
 */
function fn_put_csv($data, $options, $enclosure)
{
    return fn_exim_put_csv($data, $options, $enclosure);
}

/**
 * @deprecated In favour of use fn_exim_export_image function.
 */
function fn_export_image($image_id, $object, $backup_path = '', $include_alt = true)
{
    return fn_exim_export_image($image_id, $object, $backup_path, $include_alt);
}

/**
 * @deprecated In favour of use fn_exim_import_images function.
 */
function fn_import_images($prefix, $image_file, $detailed_file, $position, $type, $object_id, $object)
{
    return fn_exim_import_images($prefix, $image_file, $detailed_file, $position, $type, $object_id, $object);
}

/**
 * @deprecated In favour of use fn_exim_import_build_groups function.
 */
function fn_import_build_groups($type_group, $export_fields)
{
    return fn_exim_import_build_groups($type_group, $export_fields);
}

/**
 * @deprecated In favour of use fn_exim_get_csv function.
 */
function fn_get_csv($pattern, $file, $options)
{
    return fn_exim_get_csv($pattern, $file, $options);
}

/**
 * @deprecated In favour of use fn_exim_get_pattern_definition function.
 */
function fn_get_pattern_definition($pattern_id, $get_for = '')
{
    return fn_exim_get_pattern_definition($pattern_id, $get_for);
}
