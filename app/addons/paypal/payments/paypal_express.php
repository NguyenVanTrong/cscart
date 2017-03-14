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

if (!defined('BOOTSTRAP')) {
    require './../../../payments/init_payment.php';
}

/**
 * @var array $order_info
 * @var array $processor_data
 * @var string $mode
 */

if (defined('PAYMENT_NOTIFICATION')) {

    if ($mode == 'cancel') {

        $order_info = fn_get_order_info($_REQUEST['order_id']);
        fn_pp_save_mode($order_info);
        if ($order_info['status'] == 'O' || $order_info['status'] == 'I') {
            $pp_response['order_status'] = 'I';
            $pp_response["reason_text"] = __('text_transaction_cancelled');
            fn_finish_payment($order_info['order_id'], $pp_response);
        }

        fn_order_placement_routines('route', $_REQUEST['order_id'], false);

    } else {
        $order_id = (!empty($_REQUEST['order_id'])) ? $_REQUEST['order_id'] : 0;
        $token = (!empty($_REQUEST['token'])) ? $_REQUEST['token'] : 0;

        $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
        $processor_data = fn_get_payment_method_data($payment_id);
        $processor_data['processor_script'] = 'paypal_express.php';
        $order_info = fn_get_order_info($order_id);
        fn_pp_save_mode($order_info);

        fn_paypal_complete_checkout($token, $processor_data, $order_info);
    }
}

$mode = (!empty($mode)) ? $mode : (!empty($_REQUEST['mode']) ? $_REQUEST['mode'] : '');

if ($mode == 'place_order' && !empty(Tygh::$app['session']['pp_express_details'])) {
    fn_pp_save_mode($order_info);
    $token = Tygh::$app['session']['pp_express_details']['token'];
    fn_paypal_complete_checkout($token, $processor_data, $order_info);

} elseif ($mode == 'place_order' || $mode == 'repay') {

    if (!defined('BOOTSTRAP')) {
        require './init_payment.php';
        Tygh::$app['session']['cart'] = empty(Tygh::$app['session']['cart']) ? array() : Tygh::$app['session']['cart'];
    }

    // payment script is included when using in-context on checkout page
    if (isset($_REQUEST['in_context_order'])) {
        $pp_response = array(
            'order_status' => 'N',
            'reason_text' => '',
            'is_deferred_payment' => true // payment won't be marked as finished
        );
    } else {
        $payment_id = (empty($_REQUEST['payment_id']) ? Tygh::$app['session']['cart']['payment_id'] : $_REQUEST['payment_id']);

        $result = fn_paypal_set_express_checkout($payment_id, $order_id, $order_info);
        $useraction = "commit";

        $processor_data = fn_get_payment_method_data($payment_id);

        if (fn_paypal_ack_success($result) && !empty($result['TOKEN'])) {
            fn_paypal_payment_form($processor_data, $result['TOKEN']);
        } else {
            // create notification
            fn_paypal_get_error($result);
            fn_order_placement_routines('checkout_redirect');
        }
    }
}
