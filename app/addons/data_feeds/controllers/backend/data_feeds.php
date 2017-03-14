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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $suffix = ".manage";

    if ($mode == 'update') {
        $datafeed_id = fn_data_feeds_update_feed($_REQUEST['datafeed_data'], $_REQUEST['datafeed_id'], DESCR_SL);

        $suffix = ".update?datafeed_id=$datafeed_id";

    } elseif ($mode == 'm_update') {
        if (!empty($_REQUEST['datafeed_data'])) {
            foreach ($_REQUEST['datafeed_data'] as $datafeed_id => $data) {
                db_query("UPDATE ?:data_feeds SET ?u WHERE datafeed_id = ?i", $data, $datafeed_id);
                db_query("UPDATE ?:data_feed_descriptions SET ?u WHERE datafeed_id = ?i AND lang_code = ?s", $data, $datafeed_id, DESCR_SL);
            }
        }

        $suffix = ".manage";

    } elseif ($mode == 'm_delete') {
        if (!empty($_REQUEST['datafeed_ids'])) {
            db_query('DELETE FROM ?:data_feeds WHERE datafeed_id IN (?n)', $_REQUEST['datafeed_ids']);
            db_query('DELETE FROM ?:data_feed_descriptions WHERE datafeed_id IN (?n)', $_REQUEST['datafeed_ids']);
        }

        $suffix = ".manage";
    }

    if ($mode == 'set_layout') {
        $layout_id = $_REQUEST['datafeed_data']['layout_id'];

        if (!empty($_REQUEST['datafeed_data']['fields'])) {
            foreach ($_REQUEST['datafeed_data']['fields'] as $key => $field) {
                if (empty($field['export_field_name'])) {
                    unset($_REQUEST['datafeed_data']['fields'][$key]);
                }
            }
        }
        $data_feeds = serialize($_REQUEST['datafeed_data']);

        return array(CONTROLLER_STATUS_OK, $_REQUEST['url_data_feeds'] . '&layout_id=' . $layout_id . '&datafeed_data=' . $data_feeds);
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'data_feeds' . $suffix);
}

if ($mode == 'manage') {
    $datafeeds = fn_data_feeds_get_data(array(), DESCR_SL);
    Tygh::$app['view']->assign('datafeeds', $datafeeds);
    Tygh::$app['view']->assign('cron_password', Registry::get('cron_password'));

} elseif ($mode == 'add') {
    $layouts = db_get_hash_array("SELECT * FROM ?:exim_layouts WHERE pattern_id = 'data_feeds'", "layout_id");
    Tygh::$app['view']->assign('layouts', $layouts);

    if (!empty($_REQUEST['layout_id'])) {
        $layout['layout_id'] = $_REQUEST['layout_id'];
    } else {
        $layout = reset($layouts);
    }
    Tygh::$app['view']->assign('layout_id', $layout['layout_id']);

    $name_schema = 'general';
    if (!empty($layouts[$layout['layout_id']])) {
        $name_schema = $layouts[$layout['layout_id']]['name'];
    }

    $pattern = fn_get_schema('exim_data_feeds', $name_schema);
    Tygh::$app['view']->assign('pattern', $pattern);

    if (!empty($_REQUEST['datafeed_data'])) {
        $datafeed_data = unserialize($_REQUEST['datafeed_data']);
        Tygh::$app['view']->assign('datafeed_data', $datafeed_data);
    }

    // Export languages
    foreach (fn_get_translation_languages() as $lang_code => $lang_data) {
        $datafeed_langs[$lang_code] = $lang_data['name'];
    }
    Tygh::$app['view']->assign('datafeed_langs', $datafeed_langs);

    $export_options = array();
    foreach ($pattern['export_fields'] as $name_field => $export_field) {
        if (!empty($export_field['option_field']) && $export_field['option_field'] == 'Y') {
            $export_options[$name_field] = $export_field;
            unset($pattern['export_fields'][$name_field]);
        }
    }

    Tygh::$app['view']->assign('export_fields', $pattern['export_fields']);
    Tygh::$app['view']->assign('feature_fields', fn_data_feeds_get_features_fields());
    Tygh::$app['view']->assign('export_options', $export_options);

    // [Page sections]
    Registry::set('navigation.tabs', array (
        'detailed' => array (
            'title' => __('general'),
            'js' => true
        ),
        'exported_items' => array (
            'title' => __('exported_items'),
            'js' => true
        ),
        'fields' => array (
            'title' => __('map_fields'),
            'js' => true
        ),
    ));
    // [/Page sections]

} elseif ($mode == 'update') {
    $layouts = db_get_hash_array("SELECT * FROM ?:exim_layouts WHERE pattern_id = 'data_feeds'", "layout_id");
    Tygh::$app['view']->assign('layouts', $layouts);

    if (!empty($_REQUEST['layout_id'])) {
        $layout_id = $_REQUEST['layout_id'];
    } else {
        $layout_id = db_get_field("SELECT layout_id FROM ?:data_feeds WHERE datafeed_id = ?i", $_REQUEST['datafeed_id']);
    }
    Tygh::$app['view']->assign('layout_id', $layout_id);

    $params['datafeed_id'] = $_REQUEST['datafeed_id'];
    $params['single'] = true;

    $datafeed_data = fn_data_feeds_get_data($params, DESCR_SL);

    Tygh::$app['view']->assign('datafeed_data', $datafeed_data);

    // Export languages
    foreach (fn_get_translation_languages() as $lang_code => $lang_data) {
        $datafeed_langs[$lang_code] = $lang_data['name'];
    }
    Tygh::$app['view']->assign('datafeed_langs', $datafeed_langs);

    $name_schema = 'general';
    if (!empty($layouts[$layout_id])) {
        $name_schema = $layouts[$layout_id]['name'];
    }

    $pattern = fn_get_schema('exim_data_feeds', $name_schema);
    Tygh::$app['view']->assign('pattern', $pattern);

    if (empty($datafeed_data['datafeed_id'])) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $export_options = array();
    if (!empty($pattern['export_fields'])) {
        foreach ($pattern['export_fields'] as $name_field => $export_field) {
            if (!empty($export_field['option_field']) && $export_field['option_field'] == 'Y') {
                $export_options[$name_field] = $export_field;
                unset($pattern['export_fields'][$name_field]);
            }
        }

        Tygh::$app['view']->assign('export_fields', $pattern['export_fields']);
    }

    Tygh::$app['view']->assign('feature_fields', fn_data_feeds_get_features_fields());
    Tygh::$app['view']->assign('export_options', $export_options);

    // [Page sections]
    Registry::set('navigation.tabs', array (
        'detailed' => array (
            'title' => __('general'),
            'js' => true
        ),
        'exported_items' => array (
            'title' => __('exported_items'),
            'js' => true
        ),
        'fields' => array (
            'title' => __('map_fields'),
            'js' => true
        ),
    ));
    // [/Page sections]

} elseif ($mode == 'download') {
    $params['datafeed_id'] = $_REQUEST['datafeed_id'];
    $params['single'] = true;

    $datafeed_data = fn_data_feeds_get_data($params, DESCR_SL);
    $filename = fn_get_files_dir_path() . $datafeed_data['file_name'];

    if (file_exists($filename)) {
        fn_get_file($filename);
    }

    exit();
}

function fn_data_feeds_update_feed($feed_data, $feed_id = 0, $lang_code = CART_LANGUAGE)
{
    $feed_data['file_name'] = fn_basename($feed_data['file_name']);

    if (!empty($feed_data['fields'])) {
        $_fields = array();
        $features_fields = fn_data_feeds_get_features_fields();

        foreach ($feed_data['fields'] as $key => $field) {
            if (empty($field['export_field_name'])) {
                unset($feed_data['fields'][$key]);
            } else {
                if (!empty($features_fields[$field['field']])) {
                    $feed_data['fields'][$key]['field'] = $features_fields[$field['field']]['description'];
                }

                $_fields[intval($field['position'])][] = $field;
            }
        }
    }

    if (!empty($_fields)) {
        ksort($_fields);
        unset($feed_data['fields']);

        foreach ($_fields as $fields) {
            if (is_array($fields)) {
                foreach ($fields as $field) {
                    $feed_data['fields'][] = $field;
                }
            }
        }
    }

    $feed_data['fields'] = serialize($feed_data['fields']);
    $feed_data['export_options'] = serialize(!empty($feed_data['export_options']) ? $feed_data['export_options'] : array());

    if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
        $feed_data['company_id'] = Registry::get('runtime.company_id');
    }

    if (empty($feed_id)) {
        $feed_id = db_query("INSERT INTO ?:data_feeds ?e", $feed_data);

        if (!empty($feed_id)) {
            $_data = array();
            $_data['datafeed_id'] = $feed_id;
            $_data['datafeed_name'] = $feed_data['datafeed_name'];

            foreach (fn_get_translation_languages() as $_data['lang_code'] => $_v) {
                db_query("INSERT INTO ?:data_feed_descriptions ?e", $_data);
            }
        }

    } else {
        db_query("UPDATE ?:data_feeds SET ?u WHERE datafeed_id = ?i", $feed_data, $feed_id);
        unset($feed_data['lang_code']);

        db_query("UPDATE ?:data_feed_descriptions SET ?u WHERE datafeed_id = ?i AND lang_code = ?s", $feed_data, $feed_id, $lang_code);
    }

    return $feed_id;
}
