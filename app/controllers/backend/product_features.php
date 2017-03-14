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
use Tygh\Enum\ProductFeatures;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_define('KEEP_UPLOADED_FILES', true);
fn_define('NEW_FEATURE_GROUP_ID', 'OG');

$navigation_sections = array(
    'features' => array(
        'title' => __('features'),
        'href' => fn_url('product_features.manage'),
    ),
    'groups' => array(
        'title' => __('feature_groups'),
        'href' => fn_url('product_features.groups'),
    ),
);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    fn_trusted_vars ('feature_data');
    $return_url = 'product_features.manage';

    // Update features
    if ($mode == 'update') {
        $feature_id = fn_update_product_feature($_REQUEST['feature_data'], $_REQUEST['feature_id'], DESCR_SL);

        return array(CONTROLLER_STATUS_OK, 'product_features.update?feature_id=' . $feature_id);
    }

    if ($mode == 'update_status') {

        fn_tools_update_status($_REQUEST);

        if (!empty($_REQUEST['status']) && in_array($_REQUEST['status'], array('D', 'H'), true)) {
            fn_disable_product_feature_filters($_REQUEST['id']);
        }

        exit;
    }

    if ($mode == 'delete') {

        if (!empty($_REQUEST['feature_id'])) {
            fn_delete_feature($_REQUEST['feature_id']);
        }

        if(!empty($_REQUEST['return_url'])) {
            $return_url = $_REQUEST['return_url'];
        }
    }

    if ($mode == 'm_delete') {
        if (!empty($_REQUEST['feature_ids'])) {
            $feature_ids = (array) $_REQUEST['feature_ids'];

            foreach ($feature_ids as $feature_id) {
                fn_delete_feature($feature_id);
            }
        }

        if(!empty($_REQUEST['return_url'])) {
            $return_url = $_REQUEST['return_url'];
        }
    }

    return array(CONTROLLER_STATUS_OK, $return_url);
}

if ($mode == 'update') {

    $selected_section = (empty($_REQUEST['selected_section']) ? 'detailed' : $_REQUEST['selected_section']);

    $feature = fn_get_product_feature_data($_REQUEST['feature_id'], false, false, DESCR_SL);

    if (empty($feature)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    Tygh::$app['view']->assign('feature', $feature);
    list($group_features) = fn_get_product_features(array('feature_types' => ProductFeatures::GROUP), 0, DESCR_SL);
    Tygh::$app['view']->assign('group_features', $group_features);

    if (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.company_id')) {
        Tygh::$app['view']->assign('picker_selected_companies', fn_ult_get_controller_shared_companies($_REQUEST['feature_id']));
    }

    $params = array(
        'feature_id' => $feature['feature_id'],
        'feature_type' => $feature['feature_type'],
        'get_images' => true,
        'page' => !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1,
        'items_per_page' => !empty($_REQUEST['items_per_page']) ? $_REQUEST['items_per_page'] : Registry::get('settings.Appearance.admin_elements_per_page'),
    );

    list($variants, $search) = fn_get_product_feature_variants($params, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);

    Tygh::$app['view']->assign('feature_variants', $variants);
    Tygh::$app['view']->assign('search', $search);

    if ($feature['feature_type'] == ProductFeatures::EXTENDED) {
        Tygh::$app['view']->assign('in_popup', false);
    } else {
        Tygh::$app['view']->assign('in_popup', defined('AJAX_REQUEST'));
    }

} elseif ($mode == 'manage') {
    Registry::set('navigation.dynamic.sections', $navigation_sections);
    Registry::set('navigation.dynamic.active_section', 'features');

    $params = $_REQUEST;
    $params['get_descriptions'] = true;
    $params['search_in_subcats'] = true;
    $params['exclude_group'] = true;

    list($features, $search) = fn_get_product_features($params, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);
    list($group_features) = fn_get_product_features(array('feature_types' => ProductFeatures::GROUP, 'get_descriptions' => true), 0, DESCR_SL);

    Tygh::$app['view']->assign('features', $features);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('group_features', $group_features);

    if (empty($features) && defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('force_redirection', fn_url('product_features.manage'));
    }
} elseif ($mode == 'groups') {
    Registry::set('navigation.dynamic.sections', $navigation_sections);
    Registry::set('navigation.dynamic.active_section', 'groups');

    $params = $_REQUEST;
    $params['get_descriptions'] = true;
    $params['search_in_subcats'] = true;
    $params['exclude_group'] = true;
    $params['feature_types'] = ProductFeatures::GROUP;

    list($features, $search) = fn_get_product_features($params, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);
    list($group_features) = fn_get_product_features(array('feature_types' => ProductFeatures::GROUP), 0, DESCR_SL);

    Tygh::$app['view']->assign('features', $features);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('group_features', $group_features);

    if (empty($features) && defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('force_redirection', fn_url('product_features.manage'));
    }
} elseif ($mode == 'get_feature_variants_list') {
    if (empty($_REQUEST['feature_id'])) {
        exit;
    }

    $pattern = !empty($_REQUEST['pattern']) ? $_REQUEST['pattern'] : '';
    $start = !empty($_REQUEST['start']) ? $_REQUEST['start'] : 0;
    $limit = (!empty($_REQUEST['limit']) ? $_REQUEST['limit'] : 10) + 1;
    $sorting = db_quote("?:product_feature_variants.position, ?:product_feature_variant_descriptions.variant");

    $join = db_quote(" LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variant_descriptions.variant_id = ?:product_feature_variants.variant_id AND ?:product_feature_variant_descriptions.lang_code = ?s", DESCR_SL);
    $condition = db_quote(" AND ?:product_feature_variants.feature_id = ?i", $_REQUEST['feature_id']);

    fn_set_hook('get_feature_variants_list', $condition, $join, $pattern, $start, $limit);

    $objects = db_get_hash_array("SELECT SQL_CALC_FOUND_ROWS ?:product_feature_variants.variant_id AS value, ?:product_feature_variant_descriptions.variant AS name FROM ?:product_feature_variants $join WHERE 1 $condition AND ?:product_feature_variant_descriptions.variant LIKE ?l ORDER BY ?p LIMIT ?i, ?i", 'value', '%' . $pattern . '%', $sorting, $start, $limit);

    if (defined('AJAX_REQUEST') && sizeof($objects) < $limit) {
        Tygh::$app['ajax']->assign('completed', true);
    } else {
        array_pop($objects);
    }

    if (empty($_REQUEST['enter_other']) || $_REQUEST['enter_other'] != 'N') {
        $total = db_get_found_rows();
        if (!Registry::get('runtime.company_id') || (fn_allowed_for('ULTIMATE') && fn_check_company_id('product_features', 'feature_id', $_REQUEST['feature_id']))) {
            if ($start + $limit >= $total + 1) {
                $objects[] = array('value' => 'disable_select', 'name' => '-' . __('enter_other') . '-');
            }
        }
    }

    if (!$start) {
        array_unshift($objects, array('value' => '', 'name' => '-' . __('none') . '-'));
    }

    Tygh::$app['view']->assign('objects', $objects);

    Tygh::$app['view']->assign('id', $_REQUEST['result_ids']);
    Tygh::$app['view']->display('common/ajax_select_object.tpl');

    exit;
}
/**
 * This mode renders list of the feature variants in Select2 JSON format.
 *
 * @TODO: Class that implements common methods for Select2 controller modes.
 */
elseif ($mode == 'get_variants_list') {
    if (isset($_REQUEST['feature_id'])) {
        $feature_id = (int) $_REQUEST['feature_id'];
    } else {
        exit;
    }

    $page_number = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;
    $page_size = isset($_REQUEST['page_size']) ? (int) $_REQUEST['page_size'] : 10;
    $search_query = isset($_REQUEST['q']) ? $_REQUEST['q'] : null;
    $lang_code = isset($_REQUEST['lang_code']) ? $_REQUEST['lang_code'] : CART_LANGUAGE;

    $search = array(
        'page' => $page_number,
        'feature_id' => $feature_id,
        'search_query' => $search_query,
        'get_images' => true
    );

    if (isset($_REQUEST['preselected'])) {
        $search['variant_id'] = $_REQUEST['preselected'];
    }

    if (isset($_REQUEST['product_id'])) {
        $search['product_id'] = (int) $_REQUEST['product_id'];
    }

    list($variants, $search) = fn_get_product_feature_variants($search, $page_size, $lang_code);

    $objects = array_values(array_map(function ($feature_variant) {
        $image_url = null;

        if (isset($feature_variant['image_pair'])) {
            $image_data = fn_image_to_display(
                $feature_variant['image_pair'],
                isset($_REQUEST['image_width']) ? (int)$_REQUEST['image_width'] : 50,
                isset($_REQUEST['image_height']) ? (int)$_REQUEST['image_height'] : 50
            );
            if (!empty($image_data['image_path'])) {
                $image_url = $image_data['image_path'];
            }
        }

        return array(
            'id' => $feature_variant['variant_id'],
            'text' => $feature_variant['variant'],
            'image_url' => $image_url
        );
    }, $variants));

    Tygh::$app['ajax']->assign('objects', $objects);
    Tygh::$app['ajax']->assign('total_objects', isset($search['total_items']) ? $search['total_items'] : count($objects));

    exit;
}
/**
 * This mode renders list of the features in Select2 JSON format.
 *
 * @TODO: Class that implements common methods for Select2 controller modes.
 */
elseif ($mode == 'get_features_list') {
    $page_number = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;
    $page_size = isset($_REQUEST['page_size']) ? (int) $_REQUEST['page_size'] : 10;
    $search_query = isset($_REQUEST['q']) ? $_REQUEST['q'] : null;
    $lang_code = isset($_REQUEST['lang_code']) ? $_REQUEST['lang_code'] : CART_LANGUAGE;

    $search = array(
        'page' => $page_number,
        'search_query' => $search_query,
        'get_descriptions' => true,
    );

    if (isset($_REQUEST['preselected'])) {
        $search['feature_id'] = $_REQUEST['preselected'];
        $search['plain'] = true;
        $search['exclude_group'] = true;
    }

    list($features, $search) = fn_get_product_features($search, $page_size, $lang_code);

    // This weird recursive functional code is efficient, but is not nicely readable.
    // It will be refactored along with implementing Select2 controller mode base class.
    $feature_converter = function ($feature_list) use (&$feature_converter, $lang_code) {
        return array_values(array_map(function ($feature) use (&$feature_converter, $lang_code) {
            $return = array(
                'id' => (int) $feature['feature_id'],
                'text' => $feature['description'],
                'object' => array(
                    'feature_type' => $feature['feature_type'],
                ),
            );
            if ($feature['feature_type'] == ProductFeatures::SINGLE_CHECKBOX) {
                $return['object']['variants'] = array(
                    array('id' => 'Y', 'text' => __('yes')),
                    array('id' => 'N', 'text' => __('no')),
                );
            } elseif ($feature['feature_type'] == ProductFeatures::GROUP) {
                $return['children'] = array();

                if (!empty($feature['subfeatures'])) {
                    // Recursive call that fills subfeatures list
                    $return['children'] = $feature_converter($feature['subfeatures']);
                }
            } elseif (in_array($feature['feature_type'], array(
                ProductFeatures::TEXT_SELECTBOX,
                ProductFeatures::NUMBER_SELECTBOX,
                ProductFeatures::EXTENDED,
                ProductFeatures::MULTIPLE_CHECKBOX
            ))) {
                $return['object']['variants'] = fn_url(
                    "product_features.get_variants_list?feature_id={$feature['feature_id']}&lang_code={$lang_code}"
                );
            }

            return $return;
        }, $feature_list));
    };

    $objects = $feature_converter($features);

    Tygh::$app['ajax']->assign('objects', $objects);
    Tygh::$app['ajax']->assign('total_objects', $search['total_items']);

    exit;
}
