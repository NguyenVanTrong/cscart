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

/**
 * @var string $mode
 */

use Tygh\Registry;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'update') {
    Registry::set('navigation.tabs.ebay', array (
        'title' => __('ebay'),
        'js' => true
    ));

    $params = array(
        'product_id' => $_REQUEST['product_id'],
    );

    list($templates, $search) = fn_get_ebay_templates($params, 0, DESCR_SL);
    Tygh::$app['view']->assign('ebay_templates', $templates);
} elseif ($mode == 'm_update') {
    $field_groups = Tygh::$app['view']->getTemplateVars('field_groups');
    $field_names = Tygh::$app['view']->getTemplateVars('field_names');
    $filled_groups = Tygh::$app['view']->getTemplateVars('filled_groups');

    if (!empty($field_names['ebay_template_id'])) {
        $params = array();
        unset($field_names['ebay_template_id']);

        $templates = array('' => '---');
        $templates += fn_get_ebay_templates($params, 0, DESCR_SL, true);

        $field_groups['S']['ebay_template_id'] = array(
            'skip_lang' => true,
            'name' => 'products_data',
            'variants' => $templates
        );

        $filled_groups['S']['ebay_template_id'] = __('ebay_templates');
    }

    if (isset($field_names['package_type'])) {
        unset($field_names['package_type']);

        $field_groups['S']['package_type'] = array(
            'name' => 'products_data',
            'variants' => array(
                'Letter' => 'Letter',
                'LargeEnvelope' => 'large_envelope',
                'PackageThickEnvelope' => 'ebay_package',
                'ExtraLargePack' => 'large_package',
            )
        );

        $filled_groups['S']['package_type'] = __('ebay_package_type');
    }

    if (isset($field_names['override'])) {
        unset($field_names['override']);
        $filled_groups['C']['override'] = __('ebay_override');
        $field_groups['C']['override'] = 'products_data';
    }

    if (isset($field_names['ebay_override_price'])) {
        unset($field_names['ebay_override_price']);
        $filled_groups['C']['ebay_override_price'] = __('override_price');
        $field_groups['C']['ebay_override_price'] = 'products_data';
    }

    if (isset($field_names['ebay_description'])) {
        unset($field_names['ebay_description']);
        $filled_groups['D']['ebay_description'] = __('ebay_description');
        $field_groups['D']['ebay_description'] = 'products_data';
    }

    Tygh::$app['view']->assign('field_groups', $field_groups);
    Tygh::$app['view']->assign('filled_groups', $filled_groups);

    Tygh::$app['view']->assign('field_names', $field_names);
} elseif ($mode == 'manage') {
    $templates = fn_get_ebay_templates(array(), 0, DESCR_SL, true);
    $statuses = \Ebay\Product::getStatuses();

    $products = Tygh::$app['view']->getTemplateVars('products');

    foreach ($products as &$product) {
        $status_id = (int) $product['ebay_status'];
        $template_id = (int) $product['ebay_template_id'];

        $product['ebay_status_name'] = (isset($statuses[$status_id])) ? $statuses[$status_id] : '';
        $product['ebay_template_name'] = (isset($templates[$template_id])) ? $templates[$template_id] : '';
    }

    unset($product);

    Tygh::$app['view']->assign('products', $products);
    Tygh::$app['view']->assign('ebay_templates', $templates);
    Tygh::$app['view']->assign('ebay_product_statuses', $statuses);
}
