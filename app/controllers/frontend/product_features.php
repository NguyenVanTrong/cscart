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

$_REQUEST['variant_id'] = empty($_REQUEST['variant_id']) ? 0 : $_REQUEST['variant_id'];

if (empty($action)) {
    $action = 'show_all';
}

$list = 'features';

if (empty(Tygh::$app['session']['excluded_features'])) {
    Tygh::$app['session']['excluded_features'] = array();
}

if (empty(Tygh::$app['session']['excluded_features'])) {
    Tygh::$app['session']['excluded_features'] = array();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Add feature to comparison list
    if ($mode == 'add_feature') {
        if (!empty($_REQUEST['add_features'])) {
            Tygh::$app['session']['excluded_features'] = array_diff(Tygh::$app['session']['excluded_features'], $_REQUEST['add_features']);
        }
    }

    return array(CONTROLLER_STATUS_OK);
}

// Add product to comparison list
if ($mode == 'add_product') {
    if (empty(Tygh::$app['session']['comparison_list'])) {
        Tygh::$app['session']['comparison_list'] = array();
    }

    $p_id = $_REQUEST['product_id'];

    if (!in_array($p_id, Tygh::$app['session']['comparison_list'])) {
        array_unshift(Tygh::$app['session']['comparison_list'], $p_id);
        $added_products = array();
        $added_products[$p_id]['product_id'] = $p_id;
        $added_products[$p_id]['display_price'] = fn_get_product_price($p_id, 1, Tygh::$app['session']['auth']);
        $added_products[$p_id]['amount'] = 1;
        $added_products[$p_id]['main_pair'] = fn_get_cart_product_icon($p_id);
        Tygh::$app['view']->assign('added_products', $added_products);

        $title = __('product_added_to_cl');
        $msg = Tygh::$app['view']->fetch('views/product_features/components/product_notification.tpl');
        fn_set_notification('I', $title, $msg, 'I');
    } else {
        fn_set_notification('W', __('notice'), __('product_in_comparison_list'));
    }

    return array(CONTROLLER_STATUS_REDIRECT);

} elseif ($mode == 'clear_list') {
    unset(Tygh::$app['session']['comparison_list']);
    unset(Tygh::$app['session']['excluded_features']);

    if (defined('AJAX_REQUEST')) {
        Tygh::$app['view']->assign('compared_products', array());
        Tygh::$app['view']->display('blocks/static_templates/feature_comparison.tpl');
        exit;
    }

    return array(CONTROLLER_STATUS_REDIRECT);

} elseif ($mode == 'delete_product' && !empty($_REQUEST['product_id'])) {
    $key = array_search ($_REQUEST['product_id'], Tygh::$app['session']['comparison_list']);
    unset(Tygh::$app['session']['comparison_list'][$key]);

    return array(CONTROLLER_STATUS_REDIRECT);

} elseif ($mode == 'delete_feature') {
    Tygh::$app['session']['excluded_features'][] = $_REQUEST['feature_id'];

    return array(CONTROLLER_STATUS_REDIRECT);

} elseif ($mode == 'compare') {
    fn_add_breadcrumb(__('feature_comparison'));
    if (!empty(Tygh::$app['session']['comparison_list'])) {
        Tygh::$app['view']->assign('comparison_data', fn_get_product_data_for_compare(Tygh::$app['session']['comparison_list'], $action));
        Tygh::$app['view']->assign('total_products', count(Tygh::$app['session']['comparison_list']));
    }
    Tygh::$app['view']->assign('list', $list);
    Tygh::$app['view']->assign('action', $action);

    if (!empty(Tygh::$app['session']['continue_url'])) {
        Tygh::$app['view']->assign('continue_url', Tygh::$app['session']['continue_url']);
    }
}

if ($mode == 'view_all') {

    $filter_id = !empty($_REQUEST['filter_id']) ? $_REQUEST['filter_id'] : 0;

    if (empty($filter_id)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    list($filters) = fn_get_filters_products_count($_REQUEST);

    if (empty($filters[$filter_id]) || $filters[$filter_id]['feature_type'] != ProductFeatures::EXTENDED) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    fn_add_breadcrumb($filters[$filter_id]['filter']);

    $variants = array();
    if (!empty($filters[$filter_id]['variants'])) {
        foreach ($filters[$filter_id]['variants'] as $variant) {
            $variants[fn_substr($variant['variant'], 0, 1)][] = $variant;
        }
    }
    ksort($variants);

    Tygh::$app['view']->assign('variants', $variants);

} elseif ($mode == 'view') {

    $variant_data = fn_get_product_feature_variant($_REQUEST['variant_id']);

    if (empty($variant_data)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    } else {
        $feature_data = fn_get_product_feature_data($variant_data['feature_id']);
        if (empty($feature_data)) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }
    }

    Tygh::$app['view']->assign('variant_data', $variant_data);

    fn_add_breadcrumb($variant_data['variant']);

    // Override meta description/keywords
    if (!empty($variant_data['meta_description']) || !empty($variant_data['meta_keywords'])) {
        Tygh::$app['view']->assign('meta_description', $variant_data['meta_description']);
        Tygh::$app['view']->assign('meta_keywords', $variant_data['meta_keywords']);
    }

    // Override page title
    if (!empty($variant_data['page_title'])) {
        Tygh::$app['view']->assign('page_title', $variant_data['page_title']);
    }

    $params = $_REQUEST;
    $params['extend'] = array('description');

    list($products, $search) = fn_get_products($params, Registry::get('settings.Appearance.products_per_page'));

    if (defined('AJAX_REQUEST') && (!empty($params['features_hash']) && !$products)) {
        fn_filters_not_found_notification();
        exit;
    }

    fn_gather_additional_products_data($products, array(
        'get_icon' => true,
        'get_detailed' => true,
        'get_options' => true,
        'get_discounts' => true,
        'get_features' => false
    ));

    $selected_layout = fn_get_products_layout($_REQUEST);

    Tygh::$app['view']->assign('products', $products);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('selected_layout', $selected_layout);
}

function fn_get_product_data_for_compare($product_ids, $action)
{
    $auth = & Tygh::$app['session']['auth'];

    $comparison_data = array(
        'product_features' => array(0 => array())
    );
    $tmp = array();
    foreach ($product_ids as $product_id) {
        $product_data = fn_get_product_data($product_id, $auth, CART_LANGUAGE, '', false, true, false, false, false, false);
        list($product_data['product_features']) = fn_get_product_features(array(
            'product_id' => $product_id,
            'product_company_id' => !empty($product_data['company_id']) ? $product_data['company_id'] : 0,
            'statuses' => array('A'),
            'variants' => true,
            'plain' => false,
            'existent_only' => true,
            'variants_selected_only' => true
        ), 0);


        fn_gather_additional_product_data($product_data, false, false, false, true, false);

        if (!empty($product_data['product_features'])) {
            foreach ($product_data['product_features'] as $k => $v) {
                if ($v['display_on_product'] === 'N' && $v['display_on_catalog'] == 'N' && $v['display_on_header'] == 'N') {
                    continue;
                }

                if ($v['feature_type'] == ProductFeatures::GROUP && empty($v['subfeatures'])) {
                    continue;
                }
                $_features = ($v['feature_type'] == ProductFeatures::GROUP) ? $v['subfeatures'] : array($k => $v);
                $group_id = ($v['feature_type'] == ProductFeatures::GROUP) ? $k : 0;
                $comparison_data['feature_groups'][$k] = $v['description'];
                foreach ($_features as $_k => $_v) {
                    if (in_array($_k, Tygh::$app['session']['excluded_features'])) {
                        if (empty($comparison_data['hidden_features'][$_k])) {
                            $comparison_data['hidden_features'][$_k] = $_v['description'];
                        }
                        continue;
                    }

                    if (empty($comparison_data['product_features'][$group_id][$_k])) {
                        $comparison_data['product_features'][$group_id][$_k] = $_v['description'];
                    }
                }
            }
        }

        $comparison_data['products'][] = $product_data;
        unset($product_data);
    }

    if ($action != 'show_all' && !empty($comparison_data['product_features'])) {
        $value = '';

        foreach ($comparison_data['product_features'] as $group_id => $v) {
            foreach ($v as $feature_id => $_v) {
                unset($value);
                $c = ($action == 'similar_only') ? true : false;
                foreach ($comparison_data['products'] as $product) {
                    $features = !empty($group_id) && isset($product['product_features'][$group_id]['subfeatures']) ? $product['product_features'][$group_id]['subfeatures'] : $product['product_features'];
                    if (empty($features[$feature_id])) {
                        $c = !$c;
                        break;
                    }
                    if (!isset($value)) {
                        $value = fn_get_feature_selected_value($features[$feature_id]);
                        continue;
                    } elseif ($value != fn_get_feature_selected_value($features[$feature_id])) {
                        $c = !$c;
                        break;
                    }
                }

                if ($c == false) {
                    unset($comparison_data['product_features'][$group_id][$feature_id]);
                }
            }
        }
    }

    return $comparison_data;
}

function fn_get_feature_selected_value($feature)
{
    $value = null;

    if (strpos(ProductFeatures::getSelectable(), $feature['feature_type']) !== false) {
        if ($feature['feature_type'] == ProductFeatures::MULTIPLE_CHECKBOX) {
            foreach ($feature['variants'] as $v) {
                if ($v['selected']) {
                    $value[] = $v['variant_id'];
                }
            }
        } else {
            $value = $feature['variant_id'];
        }

    } elseif (strpos(ProductFeatures::NUMBER_FIELD . ProductFeatures::DATE, $feature['feature_type']) !== false) {
        $value = $feature['value_int'];
    } else {
        $value = $feature['value'];
    }

    return $value;
}
