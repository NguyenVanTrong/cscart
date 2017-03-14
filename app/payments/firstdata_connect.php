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
 * @var array $processor_data
 * @var array $order_info
 */

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {
    $order_id = (int) $_REQUEST['order_id'];
    $order_info = fn_get_order_info($order_id);
    $approval_code = isset($_REQUEST['approval_code']) ? $_REQUEST['approval_code'] : '';
    $txndatetime = isset($_REQUEST['txndatetime']) ? $_REQUEST['txndatetime'] : '';
    $currency = isset($_REQUEST['currency']) ? $_REQUEST['currency'] : '';
    $response_hash = isset($_REQUEST['response_hash']) ? $_REQUEST['response_hash'] : '';
    $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : 'FAILED';
    $refnumber = isset($_REQUEST['refnumber']) ? $_REQUEST['refnumber'] : null;

    if (empty($order_info)) {
        throw new Exception("Error: order not found");
    }

    $processor_data = $order_info['payment_method'];
    $hash = $processor_data['processor_params']['secret'] . $approval_code;
    $hash .= $order_info['total'] . $currency . $txndatetime;
    $hash .= $processor_data['processor_params']['store'];

    if ($response_hash !== fn_fd_create_hash($hash)) {
        throw new Exception("Error: invalid response hash");
    }

    $code = $approval_code;

    if ($refnumber !== null) {
        $code = str_replace(array($refnumber . ':', $refnumber), '', $code);
    }

    $code_part = explode(':', $code);
    $code_part = array_filter($code_part);

    if (count($code_part) > 1)  {
        $pp_response['transaction_id'] = $code_part[1];
    } else {
        $pp_response['transaction_id'] = $code;
    }

    $pp_response['order_status'] = ($status === 'APPROVED') ? 'P' : 'F';
    $pp_response['reason_text'] = $approval_code;

    if (!empty($_REQUEST['failReason'])) {
        $pp_response['reason_text'] .= " Error: " . $_REQUEST['failReason'];
    }

    if (fn_check_payment_script('firstdata_connect.php', $order_id)) {
        fn_finish_payment($order_id, $pp_response, false);
        fn_order_placement_routines('route', $order_id);
    }
} else {
    if ($processor_data['processor_params']['test'] == 'LIVE') {
        $post_address = "https://connect.firstdataglobalgateway.com/IPGConnect/gateway/processing";
    } else {
        $post_address = "https://connect.merchanttest.firstdataglobalgateway.com/IPGConnect/gateway/processing";
    }
    $_order_id = (($order_info['repaid']) ? ($order_id .'_'. $order_info['repaid']) : $order_id) . '_' . fn_date_format(time(), '%H_%M_%S');

    $time = new DateTime('now', new DateTimeZone('GMT'));
    $date = $time->format('Y:m:d-H:i:s');


    $success_url = fn_url("payment_notification.success?payment=firstdata_connect&order_id={$order_id}&txndatetime={$date}", AREA, 'current');
    $fail_url = fn_url("payment_notification.fail?payment=firstdata_connect&order_id={$order_id}&txndatetime={$date}", AREA, 'current');

    $post_data = array(
        'txntype' => $processor_data['processor_params']['transaction_type'],
        'timezone' => 'GMT',
        'txndatetime' => $date,
        'hash' => fn_fd_create_hash($processor_data['processor_params']['store'] . $date . $order_info['total'] . $processor_data['processor_params']['secret']),
        'storename' => $processor_data['processor_params']['store'],
        'mode' => 'payonly',
        'chargetotal' => $order_info['total'],
        'oid' => $processor_data['processor_params']['prefix'] . $_order_id,
        'subtotal' => $order_info['total'],
        'trxOrigin' => 'eci',
        'bname' => $order_info['firstname'] . ' ' . $order_info['lastname'],
        'baddr1' => $order_info['b_address'],
        'baddr2' => $order_info['b_address_2'],
        'bcity' => $order_info['b_city'],
        'bstate' => $order_info['b_state'],
        'bcountry' => $order_info['b_country'],
        'bzip' => $order_info['b_zipcode'],
        'phone' => $order_info['phone'],
        'fax' => $order_info['fax'],
        'email' => $order_info['email'],
        'sname' => $order_info['firstname'] . ' ' . $order_info['lastname'],
        'saddr1' => $order_info['s_address'],
        'saddr2' => $order_info['s_address_2'],
        'scity' => $order_info['s_city'],
        'sstate' => $order_info['s_state'],
        'scountry' => $order_info['s_country'],
        'szip' => $order_info['s_zipcode'],
        'responseSuccessURL' => $success_url,
        'responseFailURL' => $fail_url
    );

    fn_create_payment_form($post_address, $post_data, 'FirstData');
}
exit;

function fn_fd_create_hash($str)
{
    $hex_str = '';
    for ($i = 0; $i < strlen($str); $i++) {
        $hex_str .= dechex(ord($str[$i]));
    }
    return hash('sha256', $hex_str);
}
