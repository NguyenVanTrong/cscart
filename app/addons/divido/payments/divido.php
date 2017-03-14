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

if (defined('PAYMENT_NOTIFICATION')) {

    if (isset($_REQUEST['order_id'])) {

        $order_id = $_REQUEST['order_id'];

        if ($mode == 'notify') {
            $__status = db_get_field(
                'SELECT status'
                . ' FROM ?:orders WHERE order_id = ?i'
                    . ' AND status NOT IN (?a)',
                $order_id,
                array(STATUS_INCOMPLETED_ORDER, STATUS_PARENT_ORDER)
            );
            $pp_response['order_status'] = empty($__status) ? 'O' : $__status;
        } elseif ($mode == 'canceled') {
            $pp_response['reason_text']  = __('text_transaction_cancelled');
            $pp_response['order_status'] = 'N';
        } elseif ($mode == 'response') {

            $response = !isset($GLOBALS['HTTP_RAW_POST_DATA']) ? file_get_contents("php://input") : $GLOBALS['HTTP_RAW_POST_DATA'];

            $response = json_decode($response, true);

            if (!empty($response)) {

                if ($response['status'] == 'SIGNED') {
                    $pp_response['order_status'] = 'P';
                    $pp_response['application_id'] = $response['application'];
                } elseif ($response['status'] == 'DECLINED' || $response['status'] == 'CANCELLED') {
                    $pp_response['order_status'] = 'F';
                    $pp_response['reason_text']  = __('declined');
                } else {
                    // Divido sends notification to the store after every completed step.
                    // Order status has to be set to Open after the first notification received.
                    $__status = db_get_field(
                        'SELECT status'
                        . ' FROM ?:orders WHERE order_id = ?i'
                            . ' AND status NOT IN (?a)',
                        $order_id,
                        array(STATUS_INCOMPLETED_ORDER, STATUS_PARENT_ORDER)
                    );
                    $pp_response['order_status'] = empty($__status) ? 'O' : $__status;
                }

                $pp_response['addons.divido.order_status'] = $response['status'];

                fn_change_order_status($order_id, $pp_response['order_status'], '', false);
                fn_update_order_payment_info($order_id, $pp_response);
            }

        } else {
            $pp_response['order_status'] = 'F';
            $pp_response['reason_text'] = __('text_transaction_declined');
        }

        if (fn_check_payment_script('divido.php', $order_id) && !($mode == 'response')) {
            fn_finish_payment($order_id, $pp_response);
            fn_order_placement_routines('route', $order_id, false);
        }
    }

    exit;
} else {

    $currency = Registry::get('currencies.' . $processor_data['processor_params']['currency']);

    if (empty($currency)) {
        fn_set_notification('W', __('warning'), __('addons.divido.select_currency_undefined', array(
            '[currency]' => $processor_data['processor_params']['currency']
        )));
        fn_disable_addon('divido', '', false);
        $pp_response['order_status'] = 'F';
    }

    if (!empty($processor_data['processor_params']['api_key'])
        && !empty($order_info['payment_info']['finance_code'])
        && !empty($order_info['payment_info']['deposit_amount'])
    ) {

        require_once(Registry::get('config.dir.addons') . 'divido/lib/Divido.php');

        Divido::setMerchant($processor_data['processor_params']['api_key']);

        $total = fn_format_price_by_currency($order_info['total'], CART_PRIMARY_CURRENCY, $processor_data['processor_params']['currency']);

        $params = array(
            'country' => $order_info['b_country'],
            'finance' => $order_info['payment_info']['finance_code'],
            'deposit' => $order_info['payment_info']['deposit_amount'],
            'currency' => $processor_data['processor_params']['currency'],
            'customer' => array(
                'first_name' => $order_info['b_firstname'],
                'last_name' => $order_info['b_lastname'],
                'email' => $order_info['email'],
                'phone_number' => $order_info['b_phone'],
                'postcode' => $order_info['b_zipcode'],
                'country' => $order_info['b_country']
            ),
            'metadata' => array(
                'orderNumber' => $order_info['order_id'],
            ),
            'response_url' => fn_url("payment_notification.response?payment=divido&order_id={$order_info['order_id']}", AREA),
            'checkout_url' => fn_url("payment_notification.canceled?payment=divido&order_id={$order_info['order_id']}", AREA),
            'redirect_url' => fn_url("payment_notification.notify?payment=divido&order_id={$order_info['order_id']}", AREA)
        );

        foreach ($order_info['products'] as $product_key => $product) {
            $products['name'][] = $product['product'];
        }

        $params['products'][] = array(
            'type' => 'product',
            'text' => isset($products['name']) ? implode(', ', $products['name']) : '',
            'quantity' => '1',
            'value' => $total,
        );

        try {
            $response = Divido_CreditRequest::create($params);

            if ($response->status == 'ok') {
                fn_create_payment_form($response->url, array(), 'Divido');
            } else {
                // Replaces underscores with spaces. Example: deposit_too_low -> Deposit too low
                $answer = ucfirst(preg_replace('~[_\W\d]~', ' ', $response->error));
                $pp_response['reason_text'] = $answer;
                $pp_response['order_status'] = 'F';
            }
        } catch (Exception $e) {
            $pp_response['reason_text'] = $e->getMessage();
            $pp_response['order_status'] = 'F';
        }
    } else {
        $pp_response['order_status'] = 'F';
    }
}
