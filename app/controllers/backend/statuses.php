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
    fn_trusted_vars('status_data');

    if ($mode == 'update') {
        $status_code = fn_update_status($_REQUEST['status'], $_REQUEST['status_data'], $_REQUEST['type']);
        if (!$status_code) {
            fn_set_notification('E', __('unable_to_create_status'), __('maximum_number_of_statuses_reached'));
        }
    }

    if ($mode == 'delete') {
        if (!empty($_REQUEST['status'])) {
            fn_delete_status($_REQUEST['status'], $_REQUEST['type']);
        }
    }

    return array(CONTROLLER_STATUS_OK, 'statuses.manage?type=' . $_REQUEST['type']);
}

$type = !empty($_REQUEST['type']) ? $_REQUEST['type'] : STATUSES_ORDER;

if ($mode == 'update') {

    $status_data = fn_get_status_data($_REQUEST['status'], $_REQUEST['type']);

    Tygh::$app['view']->assign('status_data', $status_data);
    Tygh::$app['view']->assign('type', $_REQUEST['type']);
    Tygh::$app['view']->assign('status_params', fn_get_status_params_definition($_REQUEST['type']));

} elseif ($mode == 'manage') {

    $section_data = array();
    $statuses = fn_get_statuses($_REQUEST['type'], array(), false, false, DESCR_SL);

    Tygh::$app['view']->assign('statuses', $statuses);

    Tygh::$app['view']->assign('type', $type);
    Tygh::$app['view']->assign('status_params', fn_get_status_params_definition($type));

    $existing_statuses = fn_array_column($statuses, 'status');
    // Orders only
    if ($type == STATUSES_ORDER) {
        Tygh::$app['view']->assign('title', __('order_statuses'));
        $existing_statuses[] = 'N';
        $existing_statuses[] = 'T';

        if (Registry::get('settings.Appearance.email_templates') == 'new') {
            $email_templates = fn_get_order_statuses_email_templates();
            Tygh::$app['view']->assign('order_email_templates', $email_templates);
        }

    } elseif ($type == STATUSES_SHIPMENT) {
        Tygh::$app['view']->assign('title', __('shipment_statuses'));
    }
    $can_create_status = !!array_diff(range('A', 'Z'), $existing_statuses);
    Tygh::$app['view']->assign('can_create_status', $can_create_status);
}

if (
    $_REQUEST['type'] == STATUSES_SHIPMENT
    || ($_REQUEST['type'] == STATUSES_ORDER && Registry::get('settings.Appearance.email_templates') == 'new')
) {
    Tygh::$app['view']->assign('hide_email', true);
}

/**
 * Functions
 */

function fn_get_status_params_definition($type)
{
    $status_params = array();

    if ($type == STATUSES_ORDER) {
        $status_params = array (
            'color' => array (
                'type' => 'color',
                'label' => 'color'
            ),
            'notify' => array (
                'type' => 'checkbox',
                'label' => 'notify_customer',
                'default_value' => 'Y'
            ),
            'notify_department' => array (
                'type' => 'checkbox',
                'label' => 'notify_orders_department'
            ),
            'notify_vendor' => array (
                'type' => 'checkbox',
                'label' => 'notify_vendor'
            ),
            'inventory' => array (
                'type' => 'select',
                'label' => 'inventory',
                'variants' => array (
                    'I' => 'increase',
                    'D' => 'decrease',
                ),
            ),
            'remove_cc_info' => array (
                'type' => 'checkbox',
                'label' => 'remove_cc_info',
                'default_value' => 'Y'
            ),
            'repay' => array (
                'type' => 'checkbox',
                'label' => 'pay_order_again'
            ),
            'appearance_type' => array (
                'type' => 'select',
                'label' => 'invoice_credit_memo',
                'variants' => array (
                    'D' => 'default',
                    'I' => 'invoice',
                    'C' => 'credit_memo',
                    'O' => 'order'
                ),
            ),
        );
        if (fn_allowed_for('MULTIVENDOR')) {
            $status_params['calculate_for_payouts'] = array(
                'type' => 'checkbox',
                'label' => 'charge_to_vendor_account'
            );
        } elseif (fn_allowed_for('ULTIMATE')) {
            unset($status_params['notify_vendor']);
        }
        if (Registry::get('settings.Appearance.email_templates') == 'new') {
            unset(
                $status_params['notify'],
                $status_params['notify_department'],
                $status_params['notify_vendor']
            );
        }
    }

    fn_set_hook('get_status_params_definition', $status_params, $type);

    return $status_params;
}

/**
 * Gets email templates for order statuses.
 *
 * @return array
 */
function fn_get_order_statuses_email_templates()
{
    /** @var \Tygh\Template\Mail\Repository $repository */
    $repository = Tygh::$app['template.mail.repository'];

    $result = array();
    $email_templates = $repository->find(array(
        array('code', 'LIKE', 'order_notification._')
    ));

    foreach ($email_templates as $template) {
        list($code, $status) = explode('.', $template->getCode(), 2);
        $status = strtoupper($status);

        $result[$status][$template->getArea()] = $template;
    }

    return $result;
}