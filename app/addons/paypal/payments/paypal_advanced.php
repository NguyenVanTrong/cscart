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

use Tygh\Http;
use Tygh\Registry;

/**
 * @var array $order_info
 * @var array $processor_data
 * @var string $mode
 */

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {

    $order_id = 0;
    $order_nonce = 0;
    if (!empty($_REQUEST['order_id'])) {
        $order_id = (int) $_REQUEST['order_id'];

    } elseif (!empty($_REQUEST['sid'])) {
        $order_nonce = $_REQUEST['order_nonce'];
        $session_id = base64_decode($_REQUEST['sid']);

        Tygh::$app['session']->resetID($session_id);
        $cart = & Tygh::$app['session']['cart'];
        $auth = & Tygh::$app['session']['auth'];

        list($order_id, $process_payment) = fn_place_order($cart, $auth);

        // store additional order data
        db_query('REPLACE INTO ?:order_data ?m', array(
            array('order_id' => $order_id,  'type' => 'S', 'data' => TIME),
        ));
    }

    fn_pp_save_mode(fn_get_order_info($order_id));
    if ($mode == 'return') {
        if (fn_check_payment_script('paypal_advanced.php', $order_id)) {
            $pp_response['order_status'] = $_REQUEST['RESULT'] === '0' ? 'P' : 'F';
            $pp_response["reason_text"] = 'Reason : ' . $_REQUEST['RESULT'] . ' / ' . urldecode($_REQUEST['RESPMSG']);
            fn_finish_payment($order_id, $pp_response, false);

            $url = fn_url("payment_notification.finish?payment=paypal_advanced&order_id=$order_id");
            Tygh::$app['view']->assign('onload', 'javascript: top.location = ' . "'$url'" . ';');
            Tygh::$app['view']->assign('order_action', __('text_paypal_processing_payment'));
            Tygh::$app['view']->display('views/orders/components/placing_order.tpl');
            fn_flush();
        }
    } elseif ($mode == 'cancel') {
        $pp_response['order_status'] = 'N';
        $pp_response['reason_text'] = __('text_transaction_cancelled');
        fn_finish_payment($order_id, $pp_response, false);
        fn_order_placement_routines('route', $order_id);
    } elseif ($mode == 'finish') {
        fn_order_placement_routines('route', $order_id);
    }
    exit;
} else {
    $currency = fn_paypal_get_valid_currency($processor_data['processor_params']['currency']);
    $paypal_total = fn_format_price_by_currency($order_info['total'], CART_PRIMARY_CURRENCY, $currency['code']);

    if (defined('IFRAME_MODE')) {
        $session_id = Tygh::$app['session']->getID();
        $url = "payment=paypal_advanced&order_nonce=$order_id&security_hash=" . fn_generate_security_hash() . '&sid=' . base64_encode($session_id);
    } else {
        $url = "payment=paypal_advanced&order_id=$order_id&security_hash=" . fn_generate_security_hash();
    }

    $post_data = array(
        'VENDOR'            => $processor_data['processor_params']['merchant_login'],
        'PARTNER'           => $processor_data['processor_params']['api_partner'],
        'USER'              => $processor_data['processor_params']['api_user'],
        'PWD'               => $processor_data['processor_params']['api_password'],
        'TRXTYPE'           => 'S',
        'BUTTONSOURCE'      => 'ST_ShoppingCart_DP_US',
        'AMT'               => $paypal_total,
        'TENDER' 	    	=> 'C',
        'CREATESECURETOKEN' => 'Y',
        'SECURETOKENID'     => uniqid(rand()),
        'DISABLERECEIPT'    => 'TRUE',
        'RETURNURL'         => fn_url("payment_notification.return?$url"),
        'CANCELURL'         => fn_url("payment_notification.cancel?$url"),
        'ERRORURL'          => fn_url("payment_notification.return?$url"),
        'URLMETHOD'         => 'POST',
        'TEMPLATE'          => $processor_data['processor_params']['layout'],
        'PAGECOLLAPSEBGCOLOR' => $processor_data['processor_params']['collapse_bg_color'],
        'PAGECOLLAPSETEXTCOLOR' => $processor_data['processor_params']['collapse_text_color'],
        'PAGEBUTTONBGCOLOR' => $processor_data['processor_params']['button_bgcolor'],
        'PAGEBUTTONTEXTCOLOR' => $processor_data['processor_params']['button_text_color'],
        'BUTTONTEXT' => $processor_data['processor_params']['label_text_color'],
        'PAYFLOWCOLOR' => $processor_data['processor_params']['payflowcolor'],
        'HDRIMG' => $processor_data['processor_params']['header_image'],
        'BILLTOFIRSTNAME' => $order_info['b_firstname'],
        'BILLTOLASTNAME' => $order_info['b_lastname'],
        'BILLTOSTREET' => $order_info['b_address'],
        'BILLTOCITY' => $order_info['b_city'],
        'BILLTOSTATE' => fn_pp_get_state($order_info, 'b_'),
        'BILLTOZIP' => $order_info['b_zipcode'],
        'BILLTOCOUNTRY' => $order_info['b_country'],
        'SHIPTOFIRSTNAME' => $order_info['s_firstname'],
        'SHIPTOLASTNAME' => $order_info['s_firstname'],
        'SHIPTOSTREET' => $order_info['s_address'],
        'SHIPTOCITY' => $order_info['s_city'],
        'SHIPTOSTATE' => fn_pp_get_state($order_info, 's_'),
        'SHIPTOZIP' => $order_info['s_zipcode'],
        'SHIPTOCOUNTRY' => $order_info['s_country'],
        'EMAIL' => $order_info['email'],
        'PHONENUM' => (!empty($order_info['phone'])) ? $order_info['phone'] : '',
        'CURRENCY' => $currency['code']
    );

    $result = fn_pp_request($post_data, $processor_data['processor_params']['testmode']);

    if ($result['RESULT'] == '0') {
        $query_data = array(
            'SECURETOKEN' => $result['SECURETOKEN'],
            'SECURETOKENID' => $result['SECURETOKENID'],
            'MODE' => ($processor_data['processor_params']['testmode'] == 'Y' ? 'TEST' : '')
        );

        fn_create_payment_form('https://payflowlink.paypal.com', $query_data, 'PayPal Advanced');

        exit;
    } else {
        $pp_response['order_status'] = 'F';
        $pp_response["reason_text"] = 'RESULT:' . $result['RESULT'] . '; RESPMSG:' . $result['RESPMSG'];
    }
}

function fn_pp_get_state($order_info, $prefix = 's_')
{
    if ($order_info[$prefix . 'state']) {
        $state = $order_info[$prefix . 'state'];
    } else {
        $state = 'Other';
    }

    return $state;
}

function fn_pp_request($data, $mode)
{
    $_post = array();
    if (!empty($data)) {
        foreach ($data as $index => $value) {
            $_post[] = $index . '[' . strlen($value) . ']='. $value;
        }
    }
    $_post = implode('&', $_post);
    $url = 'https://' . ($mode == 'Y' ? 'pilot-payflowpro.paypal.com' : 'payflowpro.paypal.com');
    $response = Http::post($url, $_post, array(
        'headers' => array(
            'Content-type: application/x-www-form-urlencoded',
            'Connection: close'
        ),
    ));
    $result = fn_pp_get_result($response);

    return $result;
}

function fn_pp_get_result($data)
{
    if (!$data || !is_string($data)) {
        return false;
    }

    parse_str($data);
    $res = array(
        'RESULT' => isset($RESULT) ? $RESULT : '',
        'SECURETOKENID' => isset($SECURETOKENID) ? $SECURETOKENID : '',
        'SECURETOKEN' => isset($SECURETOKEN) ? $SECURETOKEN : '',
        'RESPMSG' => isset($RESPMSG) ? $RESPMSG : '',
    );

    return $res;
}
