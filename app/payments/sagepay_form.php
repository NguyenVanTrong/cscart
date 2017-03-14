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

require_once(Registry::get('config.dir.payments') . 'sagepay_files/sagepay.functions.php');

if (defined('PAYMENT_NOTIFICATION')) {

    // Get the password
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $_REQUEST['order_id']);
    $processor_data = fn_get_payment_method_data($payment_id);

    $result = decryptAes($_REQUEST['crypt'], $processor_data["processor_params"]["password"]);
    preg_match("/Status=(.+)&/U", $result, $a);

    if (trim($a[1]) == "OK") {
        $pp_response['order_status'] = ($processor_data["processor_params"]["transaction_type"] == 'PAYMENT') ? 'P' : 'O';

        if (preg_match("/TxAuthNo=(.+)&/U", $result, $_authno)) {
            $pp_response["reason_text"] = "AuthNo: " . $_authno[1];
        }

        if (preg_match("/VPSTxID={(.+)}/U", $result, $transaction_id)) {
            $pp_response["transaction_id"] = $transaction_id[1];
        }

    } else {
        $pp_response['order_status'] = 'F';
        if (preg_match("/StatusDetail=(.+)&/U", $result, $stat)) {
            $pp_response["reason_text"] = "Status: " . trim($stat[1]) . " (" . trim($a[1]) . ") ";
        }
    }

    if (preg_match("/AVSCV2=(.*)&/U", $result, $avs)) {
        $pp_response['descr_avs'] = $avs[1];
    }

    fn_finish_payment($_REQUEST['order_id'], $pp_response, false);
    fn_order_placement_routines('route', $_REQUEST['order_id']);

} else {
    $pp_curr = $processor_data['processor_params']['currency'];

    if ($processor_data['processor_params']['testmode'] == 'Y') {
        $post_address = "https://test.sagepay.com/gateway/service/vspform-register.vsp";
    } elseif ($processor_data['processor_params']['testmode'] == 'N') {
        $post_address = "https://live.sagepay.com/gateway/service/vspform-register.vsp";
    } elseif ($processor_data['processor_params']['testmode'] == 'S') {
        $post_address = "https://test.sagepay.com/Simulator/VSPFormGateway.asp";
    }

    $post["VPSProtocol"] = "3.0";
    $post["TxType"] = $processor_data["processor_params"]["transaction_type"];
    $post["Vendor"] = htmlspecialchars($processor_data["processor_params"]["vendor"]);

    $post_encrypted = 'VendorTxCode=' . $processor_data['processor_params']['order_prefix'] . (($order_info['repaid']) ? ($order_id . '_' . $order_info['repaid']) : $order_id) . '-' . fn_date_format(time(), '%H_%M_%S') . "&";
    $post_encrypted .= 'Amount=' . fn_format_price_by_currency($order_info['total'], CART_PRIMARY_CURRENCY, $pp_curr) . '&';
    $post_encrypted .= 'Currency=' . $pp_curr . '&';
    $post_encrypted .= 'Description=Payment for Order ' . $order_id . '&';
    $post_encrypted .= 'SuccessURL=' . fn_url("payment_notification.notify?payment=sagepay_form&order_id=$order_id", AREA, 'http') . '&';
    $post_encrypted .= 'FailureURL=' . fn_url("payment_notification.notify?payment=sagepay_form&order_id=$order_id", AREA, 'http') . '&';
    $post_encrypted .= 'CustomerEMail=' . $order_info['email'] . '&';
    $post_encrypted .= 'VendorEmail=' . Registry::get('settings.Company.company_orders_department') . '&';
    $post_encrypted .= 'CustomerName=' . $order_info['firstname'] . ' ' . $order_info['lastname'] . '&';
    $post_encrypted .= 'ContactNumber=' . $order_info['phone'] . '&';
    $post_encrypted .= 'ContactFax=' . $order_info['fax'] . '&';

    // Billing address
    $post_encrypted .= 'BillingAddress1=' . $order_info['b_address'] . '&';
    if (!empty($order_info['b_address_2'])) {
        $post_encrypted .= 'BillingAddress2=' . $order_info['b_address_2'] . '&';
    }
    $post_encrypted .= 'BillingPostCode=' . $order_info['b_zipcode'] . '&';
    $post_encrypted .= 'BillingCountry=' . $order_info['b_country'] . '&';
    if ($order_info['b_country'] == 'US') {
        $post_encrypted .= 'BillingState=' . $order_info['b_state'] . '&';
    }
    $post_encrypted .= 'BillingCity=' . $order_info['b_city'] . '&';
    $post_encrypted .= 'BillingFirstnames=' . $order_info['b_firstname'] . '&';
    $post_encrypted .= 'BillingSurname=' . $order_info['b_lastname'] . '&';

    // Shipping Address
    $post_encrypted .= 'DeliveryAddress1=' . $order_info['s_address'] . '&';
    if (!empty($order_info['s_address_2'])) {
        $post_encrypted .= 'DeliveryAddress2=' . $order_info['s_address_2'] . '&';
    }
    $post_encrypted .= 'DeliveryPostCode=' . $order_info['s_zipcode'] . '&';
    $post_encrypted .= 'DeliveryCountry=' . $order_info['s_country'] . '&';
    if ($order_info['s_country'] == 'US') {
        $post_encrypted .= 'DeliveryState=' . $order_info['s_state'] . '&';
    }
    $post_encrypted .= 'DeliveryCity=' . $order_info['s_city'] . '&';
    $post_encrypted .= 'DeliveryFirstnames=' . $order_info['s_firstname'] . '&';
    $post_encrypted .= 'DeliverySurname=' . $order_info['s_lastname'] . '&';

    // affiliate code
    $post_encrypted .= 'ReferrerID=7B356DCA-BDB1-42EF-B4CB-FEC07D977879' . '&';

    $post_encrypted .= "Basket=" . fn_sagepay_get_basket($order_info, CART_PRIMARY_CURRENCY, $pp_curr);

    $post["Crypt"] = encryptAes($post_encrypted, $processor_data["processor_params"]["password"]);
    fn_create_payment_form($post_address, $post, 'SagePay server');
}

exit;
