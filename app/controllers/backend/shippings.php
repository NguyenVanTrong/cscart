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
use Tygh\Shippings\Shippings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$_REQUEST['shipping_id'] = empty($_REQUEST['shipping_id']) ? 0 : $_REQUEST['shipping_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $suffix = '';

    fn_trusted_vars (
        'shipping_data'
    );

    //
    // Update shipping method
    //
    if ($mode == 'update') {
        if ((!empty($_REQUEST['shipping_id']) && fn_check_company_id('shippings', 'shipping_id', $_REQUEST['shipping_id'])) || empty($_REQUEST['shipping_id'])) {
            fn_set_company_id($_REQUEST['shipping_data']);
            $_REQUEST['shipping_id'] = fn_update_shipping($_REQUEST['shipping_data'], $_REQUEST['shipping_id']);

        }

        $_extra = empty($_REQUEST['destination_id']) ? '' : '&destination_id=' . $_REQUEST['destination_id'];
        $suffix = '.update?shipping_id=' . $_REQUEST['shipping_id'] . $_extra;
    }

    // Delete selected rates
    if ($mode == 'delete_rate_values') {
        if (fn_check_company_id('shippings', 'shipping_id', $_REQUEST['shipping_id'])) {
            foreach ($_REQUEST['delete_rate_data'] as $destination_id => $rates) {
                fn_delete_rate_values($rates, $_REQUEST['shipping_id'], $destination_id);
            }
        }

        $suffix = '.update?shipping_id=' . $_REQUEST['shipping_id'];
    }

    if ($mode == 'apply_to_vendors' && fn_allowed_for('MULTIVENDOR')) {
        if (!Registry::get('runtime.company_id') && !empty($_REQUEST['shipping_id'])) {
            $companies = fn_apply_shipping_to_vendors($_REQUEST['shipping_id']);
            fn_set_notification('N', __('notice'), __('shipping_applied_to_vendors', array('[vendors]' => $companies)));
            $suffix = '.update?shipping_id=' . $_REQUEST['shipping_id'];
        }
    }

    //
    // Update shipping methods
    //
    if ($mode == 'm_update') {

        if (!empty($_REQUEST['shipping_data']) && is_array($_REQUEST['shipping_data'])) {
            foreach ($_REQUEST['shipping_data'] as $k => $v) {
                if (empty($v)) {
                    continue;
                }

                if (fn_check_company_id('shippings', 'shipping_id', $k)) {
                    fn_update_shipping($v, $k);
                }
            }
        }

        $suffix .= '.manage';
    }

    if ($mode == 'test') {

        $shipping_data = $_REQUEST['shipping_data'];

        if (!empty($shipping_data['service_id']) && !empty($_REQUEST['shipping_id'])) {
            // Set package information (weight is only needed)
            $weight = floatval($shipping_data['test_weight']);
            $weight = !empty($weight) ? sprintf("%.3f", $weight) : '0.001';

            $package_info = array(
                'W' => $weight,
                'C' => 100,
                'I' => 1,
                'packages' => array(
                    array(
                        'products' => array(),
                        'amount' => 1,
                        'weight' => $weight,
                        'cost' => 100
                    )
                ),
                'origination' => array(
                    'name' => Registry::get('settings.Company.company_name'),
                    'address' => Registry::get('settings.Company.company_address'),
                    'city' => Registry::get('settings.Company.company_city'),
                    'country' => Registry::get('settings.Company.company_country'),
                    'state' => Registry::get('settings.Company.company_state'),
                    'zipcode' => Registry::get('settings.Company.company_zipcode'),
                    'phone' => Registry::get('settings.Company.company_phone'),
                    'fax' => Registry::get('settings.Company.company_fax'),
                )
            );

            // Set default location
            $location = $package_info['location'] = fn_get_customer_location(array('user_id' => 0), array());
            $service_params = !empty($shipping_data['service_params']) ? $shipping_data['service_params'] : array();

            $shipping = Shippings::getShippingForTest($_REQUEST['shipping_id'], $shipping_data['service_id'], $service_params, $package_info);
            $rates = Shippings::calculateRates(array($shipping));

            Tygh::$app['view']->assign('data', $rates[0]);
            Tygh::$app['view']->assign('weight', $weight);
            Tygh::$app['view']->assign('service', db_get_field("SELECT description FROM ?:shipping_service_descriptions WHERE service_id = ?i AND lang_code = ?s", $shipping_data['service_id'], DESCR_SL));
        }

        Tygh::$app['view']->display('views/shippings/components/test.tpl');
        exit;

    }

    //
    // Delete shipping methods
    //
    //TODO make security check for company_id
    if ($mode == 'm_delete') {

        if (!empty($_REQUEST['shipping_ids'])) {
            foreach ($_REQUEST['shipping_ids'] as $id) {
                if (fn_check_company_id('shippings', 'shipping_id', $id)) {
                    fn_delete_shipping($id);
                }
            }
        }

        $suffix = '.manage';
    }

    // Delete shipping method
    if ($mode == 'delete') {

        if (!empty($_REQUEST['shipping_id']) && fn_check_company_id('shippings', 'shipping_id', $_REQUEST['shipping_id'])) {
            fn_delete_shipping($_REQUEST['shipping_id']);
        }

        $suffix = '.manage';
    }

    return array(CONTROLLER_STATUS_OK, 'shippings' . $suffix);
}

if ($mode == 'configure') {

    $shipping_id = !empty($_REQUEST['shipping_id']) ? $_REQUEST['shipping_id'] : 0;

    if (Registry::get('runtime.company_id')) {
        $shipping = db_get_row("SELECT company_id, service_params FROM ?:shippings WHERE shipping_id = ?i", $shipping_id);
        if ($shipping['company_id'] != Registry::get('runtime.company_id')) {
            exit;
        }
    }

    $module = !empty($_REQUEST['module']) ? basename($_REQUEST['module']) : '';
    if (!empty($module)) {
        $view = Tygh::$app['view'];
        $service_template = '';

        $tpl = 'views/shippings/components/services/' . $module . '.tpl';
        if ($view->templateExists($tpl)) {
            $service_template = $tpl;
        } else {
            $addons = Registry::get('addons');
            foreach ($addons as $addon => $data) {
                $tpl = 'addons/' . $addon .'/views/shippings/components/services/' . $module . '.tpl';
                if ($view->templateExists($tpl)) {
                    $service_template = $tpl;
                    break;
                }
            }
        }

        if (!empty($service_template)) {

            if (isset($shipping['service_params'])) {
                $shipping['service_params'] = unserialize($shipping['service_params']);
                if (empty($shipping['service_params'])) {
                    $shipping['service_params'] = array();
                }
            } else {
                $shipping['service_params'] = fn_get_shipping_params($shipping_id);
            }
        }

        Tygh::$app['view']->assign('shipping', $shipping);
        Tygh::$app['view']->assign('service_template', $service_template);

        $code = !empty($_REQUEST['code']) ? $_REQUEST['code'] : '';
        Tygh::$app['view']->assign('code', $code);
    }
// Add new shipping method
} elseif ($mode == 'add') {

    $rate_data = array(
        'rate_value' => array(
            'C' => array(),
            'W' => array(),
            'I' => array(),
        )
    );

    $services = fn_get_shipping_services();
    Tygh::$app['view']->assign('services', $services);
    Tygh::$app['view']->assign('carriers', fn_get_carriers_from_services($services));
    Tygh::$app['view']->assign('rate_data', $rate_data);
    Tygh::$app['view']->assign('taxes', fn_get_taxes());
    Tygh::$app['view']->assign('usergroups', fn_get_usergroups(array('type' => 'C', 'status' => array('A', 'H')), DESCR_SL));

// Collect shipping methods data
} elseif ($mode == 'update') {
    $shipping = fn_get_shipping_info($_REQUEST['shipping_id'], DESCR_SL);

    if (empty($shipping)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    if (Registry::get('runtime.company_id') && !fn_allowed_for('ULTIMATE')) {
        $company_data = Registry::get('runtime.company_data');

        if ((!in_array($_REQUEST['shipping_id'], explode(',', $company_data['shippings'])) && $shipping['company_id'] != Registry::get('runtime.company_id')) || ($shipping['company_id'] != Registry::get('runtime.company_id') && $shipping['company_id'] != 0)) {
            return array(CONTROLLER_STATUS_DENIED);
        }
    }

    if ($shipping['rate_calculation'] == 'M') {
        $rates_defined = db_get_hash_array("SELECT destination_id, IF(rate_value = '', 0, 1) as defined FROM ?:shipping_rates WHERE shipping_id = ?i", 'destination_id', $_REQUEST['shipping_id']);
        foreach ($shipping['rates'] as $rate_key => $rate) {
            if (!empty($rates_defined[$rate['destination_id']]['defined'])) {
                $shipping['rates'][$rate_key]['rate_defined'] = true;
            }
        }
    }

    Tygh::$app['view']->assign('shipping', $shipping);

    $tabs = array (
        'general' => array (
            'title' => __('general'),
            'js' => true
        ),
        'configure' => array (
            'title' => __('configure'),
            'ajax' => true,
        ),
        'shipping_charges' => array (
            'title' => __('shipping_charges'),
            'js' => true
        ),
    );

    $services = fn_get_shipping_services();
    if (!empty($shipping['rate_calculation']) && $shipping['rate_calculation'] == 'R' && !empty($services[$shipping['service_id']]['module'])) {
        $tabs['configure']['href'] = 'shippings.configure?shipping_id=' . $shipping['shipping_id'] . '&module=' . $services[$shipping['service_id']]['module'] . '&code=' . urlencode($services[$shipping['service_id']]['code']);
        $tabs['configure']['hidden'] = 'N';
    } else {
        $tabs['configure']['hidden'] = 'Y';
    }

    if (Registry::get('runtime.company_id') && Registry::get('runtime.company_id') != $shipping['company_id']) {
        unset($tabs['configure']);
        Tygh::$app['view']->assign('hide_for_vendor', true);
    }

    Registry::set('navigation.tabs', $tabs);

    Tygh::$app['view']->assign('services', $services);
    Tygh::$app['view']->assign('carriers', fn_get_carriers_from_services($services));
    Tygh::$app['view']->assign('taxes', fn_get_taxes());
    Tygh::$app['view']->assign('usergroups', fn_get_usergroups(array('type' => 'C', 'status' => array('A', 'H')), DESCR_SL));

// Show all shipping methods
} elseif ($mode == 'manage') {

    $company_id = Registry::ifGet('runtime.company_id', null);
    Tygh::$app['view']->assign('shippings', fn_get_available_shippings($company_id));

    Tygh::$app['view']->assign('usergroups', fn_get_usergroups(array('type' => 'C', 'status' => array('A', 'H')), DESCR_SL));
}

function fn_delete_rate_values($delete_rate_data, $shipping_id, $destination_id)
{
    $rate_values = db_get_field("SELECT rate_value FROM ?:shipping_rates WHERE shipping_id = ?i AND destination_id = ?i", $shipping_id, $destination_id);

    if (!empty($rate_values)) {
        $rate_values = unserialize($rate_values);
    }

    foreach ((array) $rate_values as $rate_type => $rd) {
        foreach ((array) $rd as $amount => $data) {
            if (isset($delete_rate_data[$rate_type][$amount]) && $delete_rate_data[$rate_type][$amount] == 'Y') {
                unset($rate_values[$rate_type][$amount]);
            }
        }
    }

    if (is_array($rate_values)) {
        foreach ($rate_values as $k => $v) {
            if ((count($v)==1) && (floatval($v[0]['value'])==0)) {
                unset($rate_values[$k]);
                continue;
            }
        }
    }

    if (fn_is_empty($rate_values)) {
        db_query("DELETE FROM ?:shipping_rates WHERE shipping_id = ?i AND destination_id = ?i", $shipping_id, $destination_id);
    } else {
        db_query("UPDATE ?:shipping_rates SET ?u WHERE shipping_id = ?i AND destination_id = ?i", array('rate_value' => serialize($rate_values)), $shipping_id, $destination_id);
    }
}

function fn_get_shipping_services($lang_code = DESCR_SL)
{
    return db_get_hash_array(
        "SELECT ?:shipping_services.service_id, ?:shipping_services.code, ?:shipping_services.module, ?:shipping_service_descriptions.description " .
        "FROM ?:shipping_services " .
        "LEFT JOIN ?:shipping_service_descriptions ON " .
            "?:shipping_service_descriptions.service_id = ?:shipping_services.service_id AND ?:shipping_service_descriptions.lang_code = ?s " .
        "ORDER BY ?:shipping_service_descriptions.description, ?:shipping_services.module",
    'service_id', $lang_code);
}

function fn_get_carriers_from_services($services)
{
    $carriers = array();
    foreach ($services as $service) {
        if (!isset($carriers[$service['module']])) {
            $carrier = Shippings::getCarrierInfo($service['module']);

            if ($carrier) {
                $carriers[$service['module']] = $carrier['name'];
            }
        }
    }

    return $carriers;
}
