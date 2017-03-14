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

    fn_trusted_vars('call_requests');

    if ($mode == 'm_update') {

        if (!empty($_REQUEST['call_requests'])) {
            foreach ($_REQUEST['call_requests'] as $request_id => $request) {
                fn_update_call_request($request, $request_id);
            }
        }

    }

    if ($mode == 'm_delete') {

        if (!empty($_REQUEST['request_ids'])) {
            foreach ($_REQUEST['request_ids'] as $request_id) {
                fn_delete_call_request($request_id);
            }
        }

    }

    if ($mode == 'delete') {

        if ($_REQUEST['request_id']) {
            fn_delete_call_request($_REQUEST['request_id']);
        }
    }

    if ($mode == 'update_status') {

        if (!empty($_REQUEST['id']) && !empty($_REQUEST['status'])) {
            db_query("UPDATE ?:call_requests SET status = ?s WHERE request_id = ?i", $_REQUEST['status'], $_REQUEST['id']);
            fn_set_notification('N', __('notice'), __('status_changed'));
        }

        if (empty($_REQUEST['return_url'])) {
            exit;
        } else {
            return array(CONTROLLER_STATUS_REDIRECT, $_REQUEST['return_url']);
        }
    }

    return array(CONTROLLER_STATUS_OK, 'call_requests.manage');
}

if ($mode == 'manage') {

    $params = array_merge(
        array('items_per_page' => Registry::get('settings.Appearance.admin_elements_per_page')),
        $_REQUEST
    );

    $params['company_id'] = Registry::get('runtime.company_id');

    list($call_requests, $search) = fn_get_call_requests($params, DESCR_SL);

    $statuses = db_get_list_elements('call_requests', 'status', true, DESCR_SL, 'call_requests.status.');
    $order_statuses = fn_get_statuses(STATUSES_ORDER);
    $responsibles = fn_call_requests_get_responsibles();

    Tygh::$app['view']
        ->assign('call_requests', $call_requests)
        ->assign('search', $search)
        ->assign('call_request_statuses', $statuses)
        ->assign('order_statuses', $order_statuses)
        ->assign('responsibles', $responsibles);
}
