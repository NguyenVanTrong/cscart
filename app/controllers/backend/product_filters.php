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

use Tygh\Enum\ProductFeatures;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $return_url = 'product_filters.manage';

    if ($mode == 'update') {
        $filter_id = fn_update_product_filter($_REQUEST['filter_data'], $_REQUEST['filter_id'], DESCR_SL);
        $return_url = 'product_filters.update&filter_id=' . $filter_id;
    }

    if ($mode == 'delete') {
        if (!empty($_REQUEST['filter_id'])) {
            if (fn_allowed_for('ULTIMATE')) {
                if (!fn_check_company_id('product_filters', 'filter_id', $_REQUEST['filter_id'])) {
                    fn_company_access_denied_notification();

                    return array(CONTROLLER_STATUS_REDIRECT, 'product_filters.manage');
                }
            }

            fn_delete_product_filter($_REQUEST['filter_id']);
        }
    }

   

    if(!empty($_REQUEST['return_url'])) {
        $return_url = $_REQUEST['return_url'];
    }

    return array(CONTROLLER_STATUS_OK, $return_url);
}

if ($mode == 'manage' || $mode == 'picker') {

    $params = $_REQUEST;
    $params['get_descriptions'] = true;

    list($filters, $search) = fn_get_product_filters($params, Registry::get('settings.Appearance.admin_elements_per_page'));

    Tygh::$app['view']->assign('filters', $filters);
    Tygh::$app['view']->assign('search', $search);

    if ($mode == 'manage') {
        $company_id = fn_get_runtime_company_id();
        $fields = fn_get_product_filter_fields();

        if (!empty($company_id)) {
            $field_filters = db_get_fields("SELECT field_type FROM ?:product_filters WHERE field_type != '' GROUP BY field_type");

            foreach ($fields as $key => $field) {
                if (in_array($key, $field_filters)) {
                    unset($fields[$key]);
                }
            }
        }

        Tygh::$app['view']->assign('filter_fields', $fields);

        if (empty($filters) && defined('AJAX_REQUEST')) {
            Tygh::$app['ajax']->assign('force_redirection', fn_url('product_filters.manage'));
        }

        $params = array(
            'variants' => true,
            'plain' => true,
            'feature_types' => array(ProductFeatures::SINGLE_CHECKBOX, ProductFeatures::TEXT_SELECTBOX, ProductFeatures::EXTENDED, ProductFeatures::NUMBER_SELECTBOX, ProductFeatures::MULTIPLE_CHECKBOX, ProductFeatures::NUMBER_FIELD, ProductFeatures::DATE),
            'exclude_group' => true,
            'exclude_filters' => !empty($company_id)
        );

        list($filter_features) = fn_get_product_features($params, 0, DESCR_SL);

        Tygh::$app['view']->assign('filter_features', $filter_features);
    }

    if ($mode == 'picker') {
        Tygh::$app['view']->display('pickers/filters/picker_contents.tpl');
        exit;
    }

} elseif ($mode == 'update') {

    $params = $_REQUEST;
    $params['get_variants'] = true;

    $fields = fn_get_product_filter_fields();
    list($filters) = fn_get_product_filters($params);
    foreach ($filters as &$filter) {
        $filter['slider'] = fn_get_filter_is_numeric_slider($filter);
    }

    Tygh::$app['view']->assign('filter', array_shift($filters));
    Tygh::$app['view']->assign('filter_fields', $fields);
    Tygh::$app['view']->assign('in_popup', !empty($_REQUEST['in_popup']));

    if (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.company_id')) {
        Tygh::$app['view']->assign('picker_selected_companies', fn_ult_get_controller_shared_companies($_REQUEST['filter_id']));
    }

}

/**
 * Update or create product filter
 *
 * @param array $filter_data Filter data
 * @param int $filter_id Filter id
 * @param string $lang_code Language code
 * @return int|false
 */
function fn_update_product_filter($filter_data, $filter_id, $lang_code = DESCR_SL)
{
    if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
        if (!empty($filter_id) && !fn_check_company_id('product_filters', 'filter_id', $filter_id)) {
            fn_company_access_denied_notification();

            return false;
        }
        if (!empty($filter_id)) {
            unset($filter_data['company_id']);
        }
    }

    $filter = array();

    if ($filter_id) {
        $filter = db_get_row("SELECT * FROM ?:product_filters WHERE filter_id = ?i", $filter_id);

        if (empty($filter)) {
            return false;
        }
    }

    // Parse filter type
    if (strpos($filter_data['filter_type'], 'FF-') === 0 || strpos($filter_data['filter_type'], 'RF-') === 0 || strpos($filter_data['filter_type'], 'DF-') === 0) {
        $filter_data['feature_id'] = str_replace(array('RF-', 'FF-', 'DF-'), '', $filter_data['filter_type']);
        $filter_data['feature_type'] = db_get_field("SELECT feature_type FROM ?:product_features WHERE feature_id = ?i", $filter_data['feature_id']);
        $filter_data['field_type'] = '';
    } else {
        $filter_data['field_type'] = str_replace(array('R-', 'B-'), '', $filter_data['filter_type']);
        $filter_data['feature_id'] = 0;
        $filter_fields = fn_get_product_filter_fields();
    }

    // Check exists filter
    if (empty($filter_id)
        || $filter['field_type'] != $filter_data['field_type']
        || $filter['feature_id'] != $filter_data['feature_id']
    ) {
        $runtime_company_id = Registry::get('runtime.company_id');
        $check_conditions = db_quote(
            'filter_id != ?i AND feature_id = ?i AND field_type = ?s',
            $filter_id,
            $filter_data['feature_id'],
            $filter_data['field_type']
        );

        if (fn_allowed_for('ULTIMATE')) {
            $company_id = isset($filter_data['company_id']) ? $filter_data['company_id'] : Registry::get('runtime.company_id');
            Registry::set('runtime.company_id', $company_id);
            $check_conditions .= fn_get_company_condition('?:product_filters.company_id', true, $company_id);
        }

        $check_result = db_get_field("SELECT filter_id FROM ?:product_filters WHERE {$check_conditions}");

        if (fn_allowed_for('ULTIMATE')) {
            Registry::set('runtime.company_id', $runtime_company_id);
        }

        if ($check_result) {
            if (!empty($filter_data['feature_id'])) {
                $feature_name = fn_get_feature_name($filter_data['feature_id']);
                fn_set_notification('E', __('error'), __('error_filter_by_feature_exists', array('[name]' => $feature_name)));
            } elseif (!empty($filter_fields[$filter_data['field_type']])) {
                $field_name = __($filter_fields[$filter_data['field_type']]['description']);
                fn_set_notification('E', __('error'), __('error_filter_by_product_field_exists', array('[name]' => $field_name)));
            }

            return false;
        }
    }

    if (!empty($filter_id)) {
        db_query('UPDATE ?:product_filters SET ?u WHERE filter_id = ?i', $filter_data, $filter_id);
        db_query('UPDATE ?:product_filter_descriptions SET ?u WHERE filter_id = ?i AND lang_code = ?s', $filter_data, $filter_id, $lang_code);
    } else {
        $filter_data['filter_id'] = $filter_id = db_query('INSERT INTO ?:product_filters ?e', $filter_data);
        foreach (fn_get_translation_languages() as $filter_data['lang_code'] => $_d) {
            db_query("INSERT INTO ?:product_filter_descriptions ?e", $filter_data);
        }
    }

    /**
     * Update product filter post hook
     *
     * @param array $filter_data
     * @param int $filter_id
     * @param string $lang_code
     */
    fn_set_hook('update_product_filter', $filter_data, $filter_id, $lang_code);

    return $filter_id;
}
