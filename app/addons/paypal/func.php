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
use Tygh\Settings;
use Tygh\Http;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

require_once dirname(__FILE__) . "/paypal_express.functions.php";

function fn_paypal_delete_payment_processors()
{
    db_query("DELETE FROM ?:payment_descriptions WHERE payment_id IN (SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('paypal.php', 'paypal_pro.php', 'payflow_pro.php', 'paypal_express.php', 'paypal_advanced.php')))");
    db_query("DELETE FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('paypal.php', 'paypal_pro.php', 'payflow_pro.php', 'paypal_express.php', 'paypal_advanced.php'))");
    db_query("DELETE FROM ?:payment_processors WHERE processor_script IN ('paypal.php', 'paypal_pro.php', 'payflow_pro.php', 'paypal_express.php', 'paypal_advanced.php')");
}

function fn_paypal_get_checkout_payment_buttons(&$cart, &$cart_products, &$auth, &$checkout_buttons, &$checkout_payments, &$payment_id)
{
    $processor_data = fn_get_processor_data($payment_id);
    if (empty($processor_data['processor_script']) || $processor_data['processor_script'] !== 'paypal_express.php') {
        return;
    }
    $form_url = fn_url('paypal_express.express');
    if (!empty($processor_data) && empty($checkout_buttons[$payment_id]) && Registry::get('runtime.mode') == 'cart') {
        $merchant_id = $processor_data['processor_params']['merchant_id'];
        if (isset($processor_data['processor_params']['in_context']) && $processor_data['processor_params']['in_context'] == 'Y' && $merchant_id && !\Tygh\Embedded::isEnabled()) {
            $environment = ($processor_data['processor_params']['mode'] == 'live')? 'production' : 'sandbox';
            if ($environment == 'sandbox') {
                fn_set_cookie('PPDEBUG', true);
            }
            $checkout_buttons[$payment_id] = '
                <form name="pp_express" id="pp_express_'.$payment_id.'" action="'. $form_url . '" method="post">
                    <input name="payment_id" value="' . $payment_id . '" type="hidden" />
                </form>
                <script type="text/javascript">
                    (function(_, $) {
                        if (window.paypalCheckoutReady) {
                            $.redirect(_.current_url);
                        } else {
                            window.paypalCheckoutReady = function() {
                                paypal.checkout.setup("'.$merchant_id.'", {
                                    environment: "'.$environment.'",
                                    container: "pp_express_'.$payment_id.'",
                                    click: function(e) {
                                        e.preventDefault();
                                        paypal.checkout.initXO();

                                        $.ceAjax("request", "'.$form_url.'", {
                                            method: "post",
                                            data: {
                                                in_context: 1,
                                                payment_id: "'.$payment_id.'"
                                            },
                                            callback: function(response) {
                                                var data = JSON.parse(response.text);
                                                if (data.token) {
                                                    var url = paypal.checkout.urlPrefix + data.token;
                                                    paypal.checkout.startFlow(url);
                                                }
                                                if (data.error) {
                                                    paypal.checkout.closeFlow();
                                                }
                                            }
                                        });
                                    }
                                });
                            };
                        }
                        $.getScript("//www.paypalobjects.com/api/checkout.js");
                    })(Tygh, Tygh.$);
                </script>
            ';
        } else {
            $checkout_buttons[$payment_id] = '
                <form name="pp_express" id="pp_express" action="'. $form_url . '" method="post">
                    <input name="payment_id" value="' . $payment_id . '" type="hidden" />
                    <input src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/checkout-logo-small.png" type="image" />
                </form>
            ';
        }
    }
}

function fn_paypal_payment_url(&$method, &$script, &$url, &$payment_dir)
{
    if (strpos($script, 'paypal_express.php') !== false) {
        $payment_dir = '/app/addons/paypal/payments/';
    }
}

function fn_update_paypal_settings($settings)
{
    if (isset($settings['pp_statuses'])) {
        $settings['pp_statuses'] = serialize($settings['pp_statuses']);
    }

    foreach ($settings as $setting_name => $setting_value) {
        Settings::instance()->updateValue($setting_name, $setting_value);
    }

    //Get company_ids for which we should update logos. If root admin click 'update for all', get all company_ids
    if (isset($settings['pp_logo_update_all_vendors']) && $settings['pp_logo_update_all_vendors'] == 'Y') {
        $company_ids = db_get_fields('SELECT company_id FROM ?:companies');
        $company_id = array_shift($company_ids);
    } elseif (!Registry::get('runtime.simple_ultimate')) {
        $company_id = Registry::get('runtime.company_id');
    } else {
        $company_id = 1;
    }
    //Use company_id as pair_id
    fn_attach_image_pairs('paypal_logo', 'paypal_logo', $company_id);
    if (isset($company_ids)) {
        foreach ($company_ids as $logo_id) {
            fn_clone_image_pairs($logo_id, $company_id, 'paypal_logo');
        }
    }
}

function fn_get_paypal_settings($lang_code = DESCR_SL)
{
    $pp_settings = Settings::instance()->getValues('paypal', 'ADDON');
    if (!empty($pp_settings['general']['pp_statuses'])) {
        $pp_settings['general']['pp_statuses'] = unserialize($pp_settings['general']['pp_statuses']);
    }

    $pp_settings['general']['main_pair'] = fn_get_image_pairs(fn_paypal_get_logo_id(), 'paypal_logo', 'M', false, true, $lang_code);

    return $pp_settings['general'];
}

function fn_paypal_get_logo_id()
{
    if (Registry::get('runtime.simple_ultimate')) {
        $logo_id = 1;
    } elseif (Registry::get('runtime.company_id')) {
        $logo_id = Registry::get('runtime.company_id');
    } else {
        $logo_id = 0;
    }

    return $logo_id;
}

function fn_paypal_update_payment_pre(&$payment_data, &$payment_id, &$lang_code, &$certificate_file, &$certificates_dir)
{
    if (!empty($payment_data['processor_id']) && fn_is_paypal_processor($payment_data['processor_id'])) {
        $p_surcharge = floatval($payment_data['p_surcharge']);
        $a_surcharge = floatval($payment_data['a_surcharge']);
        if (!empty($p_surcharge) || !empty($a_surcharge)) {
            fn_set_notification('W', __('attention'), __('addons.paypal.surcharge_policy_notice'), 'K');
        }

        if (!empty($payment_data['processor_params']['layout']) && $payment_data['processor_params']['layout'] == 'minLayout') {
            $payment_data['processor_params']['iframe_mode'] = 'Y';
        }

        // when no icon specified, use default paypal logo
        $img_key = 'payment_image';
        $src_type = !empty($_REQUEST["type_{$img_key}_image_icon"][0]) ? $_REQUEST["type_{$img_key}_image_icon"][0] : 'local';
        if (empty($payment_id)
            && (
                $src_type == 'local' && empty($_FILES["file_{$img_key}_image_icon"]['name'][0])
                || $src_type == 'server' && empty($_REQUEST["file_{$img_key}_image_icon"][0])
            )
        ) {
            $_REQUEST["file_{$img_key}_image_icon"][0] = Registry::get('config.current_location') . fn_get_theme_path('/[relative]/media/images/addons/paypal/logo.png');
            $_REQUEST["type_{$img_key}_image_icon"][0] = 'url';
        }
    }
}

function fn_paypal_rma_update_details_post(&$data, &$show_confirmation_page, &$show_confirmation, &$is_refund, &$_data, &$confirmed)
{
    $change_return_status = $data['change_return_status'];
    if (($show_confirmation == false || ($show_confirmation == true && $confirmed == 'Y')) && $is_refund == 'Y' && !empty($change_return_status['paypal_perform_refund'])) {
        $order_info = fn_get_order_info($change_return_status['order_id']);
        $amount = 0;
        $st_inv = fn_get_statuses(STATUSES_RETURN);
        if ($change_return_status['status_to'] != $change_return_status['status_from'] && $st_inv[$change_return_status['status_to']]['params']['inventory'] != 'D') {
            if (!empty($order_info['payment_method']['processor_id']) && fn_is_paypal_processor($order_info['payment_method']['processor_id']) &&
                !empty($order_info['payment_info']['transaction_id']) &&
                !empty($order_info['payment_method']['processor_params']['username']) && !empty($order_info['payment_method']['processor_params']['password']) &&
                !fn_is_paypal_refund_performed($change_return_status['return_id'])
            ) {
                $return_data = fn_get_return_info($change_return_status['return_id']);
                
                $request_data = array(
                    'METHOD' => 'RefundTransaction',
                    'VERSION' => '94',
                    'TRANSACTIONID' => $order_info['payment_info']['transaction_id']
                );
                if (!empty($order_info['returned_products'])) {
                    foreach ($order_info['returned_products'] as $cart_id => $product) {
                        if (isset($return_data['items']['A'][$cart_id])) {
                            $amount += $product['subtotal'];
                        }
                    }
                } elseif (!empty($order_info['products'])) {
                    foreach ($order_info['products'] as $cart_id => $product) {
                        if (isset($product['extra']['returns']) && isset($return_data['items']['A'][$cart_id])) {
                            foreach ($product['extra']['returns'] as $return_id => $return_data)  {
                                $amount += $return_data['amount'] * $product['subtotal'];
                            }
                        }
                    }
                }

                if ($amount != $order_info['subtotal'] || fn_allowed_for('MULTIVENDOR')) {
                    $request_data['REFUNDTYPE'] = 'Partial';
                    $request_data['AMT'] = $amount;
                    $request_data['CURRENCYCODE'] = isset($order_info['payment_method']['processor_params']['currency']) ? $order_info['payment_method']['processor_params']['currency'] : 'USD';
                    $request_data['NOTE'] = !empty($_REQUEST['comment']) ? $_REQUEST['comment'] : '';
                } else {
                    $request_data['REFUNDTYPE'] = 'Full';
                }
                fn_paypal_build_request($order_info['payment_method'], $request_data, $post_url, $cert_file);
                $result = fn_paypal_request($request_data, $post_url, $cert_file);
                if (fn_paypal_ack_success($result)) {
                    $extra = empty($return_data['extra'])? array() : unserialize($return_data['extra']);
                    $extra['paypal_refund_transaction_id'] = $result['REFUNDTRANSACTIONID'];
                    Tygh::$app['db']->query("UPDATE ?:rma_returns SET extra = ?s WHERE return_id = ?i", serialize($extra), $change_return_status['return_id']);
                    
                    fn_set_notification('N', __('notice'), __('addons.paypal.rma.refund_performed'));
                } else {
                    fn_paypal_get_error($result);
                }
            }
        }
    }
}

function fn_validate_paypal_order_info($data, $order_info)
{
    if (empty($data) || empty($order_info)) {
        return false;
    }

    $errors = array();
    $currency_code = null;
    $total = isset($order_info['total']) ? $order_info['total'] : null;

    if (!empty($order_info['payment_method']['processor_params']['currency'])) {
        $currency = fn_paypal_get_valid_currency($order_info['payment_method']['processor_params']['currency']);
        $currency_code = $currency['code'];

        if ($total && $currency_code != CART_PRIMARY_CURRENCY) {
            $total = fn_format_price_by_currency($total, CART_PRIMARY_CURRENCY, $currency_code);
        }
    }

    if (!isset($data['num_cart_items']) || count($order_info['products']) != $data['num_cart_items']) {
        if (
            isset($order_info['payment_method'])
            && isset($order_info['payment_method']['processor_id'])
            && 'paypal.php' == db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i", $order_info['payment_method']['processor_id'])
        ) {
            list(, $count) = fn_pp_standart_prepare_products($order_info);

            if ($count != $data['num_cart_items']) {
                $errors[] = __('pp_product_count_is_incorrect');
            }
        }
    }

    if (!isset($data['mc_currency']) || $data['mc_currency'] != $currency_code) {
        //if cureency defined in paypal settings do not match currency in IPN
        $errors[] = __('pp_currency_is_incorrect');
    } elseif (!isset($data['mc_gross']) || !isset($total) || (float) $data['mc_gross'] != (float) $total) {
        //if currency is ok, check totals
        $errors[] = __('pp_total_is_incorrect');
    }

    if (!empty($errors)) {
        $pp_response['ipn_errors'] = implode('; ', $errors);
        fn_update_order_payment_info($order_info['order_id'], $pp_response);
        return false;
    }
    return true;
}

function fn_paypal_get_customer_info($data)
{
    $user_data = array();
    if (!empty($data['address_street'])) {
        $user_data['b_address'] = $user_data['s_address'] = $data['address_street'];
    }
    if (!empty($data['address_city'])) {
        $user_data['b_city'] = $user_data['s_city'] = $data['address_city'];
    }
    if (!empty($data['address_state'])) {
        $user_data['b_state'] = $user_data['s_state'] = $data['address_state'];
    }
    if (!empty($data['address_country'])) {
        $user_data['b_country'] = $user_data['s_country'] = $data['address_country'];
    }
    if (!empty($data['address_zip'])) {
        $user_data['b_zipcode'] = $user_data['s_zipcode'] = $data['address_zip'];
    }
    if (!empty($data['contact_phone'])) {
        $user_data['b_phone'] = $user_data['s_phone'] = $data['contact_phone'];
    }
    if (!empty($data['address_country_code'])) {
        $user_data['b_country'] = $user_data['s_country'] = $data['address_country_code'];
    }
    if (!empty($data['first_name'])) {
        $user_data['firstname'] = $data['first_name'];
    }
    if (!empty($data['last_name'])) {
        $user_data['lastname'] = $data['last_name'];
    }
    if (!empty($data['address_name'])) {
        //When customer set a shipping name we should use it
        $_address_name = explode(' ', $data['address_name']);
        $user_data['s_firstname'] = $_address_name[0];
        $user_data['s_lastname'] = $_address_name[1];
    }
    if (!empty($data['payer_business_name'])) {
        $user_data['company'] = $data['payer_business_name'];
    }
    if (!empty($data['payer_email'])) {
        $user_data['email'] = $data['payer_email'];
    }
    if (!empty($user_data) && isset($data['charset'])) {
        array_walk($user_data, 'fn_pp_convert_encoding', $data['charset']);
    }

    return $user_data;
}

function fn_pp_convert_encoding(&$value, $key, $enc_from = 'windows-1252')
{
    $value = fn_convert_encoding($enc_from, 'UTF-8', $value);
}

function fn_process_paypal_ipn($order_id, $data)
{
    $order_info = fn_get_order_info($order_id);

    if (!empty($order_info)
        && !empty($data['txn_id'])
        && (empty($order_info['payment_info']['txn_id'])
            || $data['payment_status'] != PAYPAL_ORDER_STATUS_COMPLETED
            || ($data['payment_status'] == PAYPAL_ORDER_STATUS_COMPLETED
                && $order_info['payment_info']['txn_id'] !== $data['txn_id']
            )
        )
    ) {
        //Can't check refund transactions.
        if (isset($data['txn_type']) && !fn_validate_paypal_order_info($data, $order_info)) {
            return false;
        }

        $pp_settings = fn_get_paypal_settings();
        fn_clear_cart($cart, true);
        $customer_auth = fn_fill_auth(array(), array(), false, 'C');
        fn_form_cart($order_id, $cart, $customer_auth);

        if ($pp_settings['override_customer_info'] == 'Y') {
            $cart['user_data'] = fn_paypal_get_customer_info($data);
        }

        $cart['order_id'] = $order_id;
        $cart['payment_info'] = $order_info['payment_info'];
        $cart['payment_info']['protection_eligibility'] = !empty($data['protection_eligibility']) ? $data['protection_eligibility'] : '';
        $cart['payment_id'] = $order_info['payment_id'];
        $cart['notes'] = empty($cart['notes']) ? array() : (array)$cart['notes'];

        $forced_status = false;

        if (!empty($data['memo'])) {
            //Save customer notes
            $cart['notes'][] = $data['memo'];
        }
        if ($data['payment_status'] == PAYPAL_ORDER_STATUS_COMPLETED) {
            //save uniq ipn id to avoid double ipn processing
            $cart['payment_info']['txn_id'] = $data['txn_id'];
        } elseif ($data['payment_status'] == PAYPAL_ORDER_STATUS_REFUNDED) {
            // partial refunds are reported with "Refunded" status
            $ipn_currency = fn_paypal_get_valid_currency($data['mc_currency']);
            $ipn_currency = $ipn_currency['code'];
            $refunded_amount = fn_format_price_by_currency($data['mc_gross'], $ipn_currency, CART_PRIMARY_CURRENCY);

            if ($order_info['total'] + $refunded_amount > 0) {
                $cart['notes'][] = __("addons.paypal.refund_message", array(
                    '[amount]'   => fn_format_price(abs($data['mc_gross'])),
                    '[currency]' => $data['mc_currency'],
                    '[date]'     => fn_date_format(TIME, Registry::get('settings.Appearance.date_format')),
                    '[time]'     => fn_date_format(TIME, Registry::get('settings.Appearance.time_format'))
                ));

                if ($pp_settings['partial_refund_action'] == PAYPAL_PARTIAL_REFUND_IGNORE) {
                    $forced_status = fn_pp_get_order_status($order_info);
                } else {
                    $forced_status = $pp_settings['partial_refund_action'];
                }
            }

            if (fn_allowed_for('MULTIVENDOR')) {
                // multiple refunds can be issued - create payout for each one
                $payout_data = array(
                    'order_id' => $order_id,
                    'company_id' => $order_info['company_id'],
                    'payout_type' => \Tygh\Enum\VendorPayoutTypes::ORDER_REFUNDED,
                    'order_amount' => $refunded_amount,
                    'approval_status' => \Tygh\Enum\VendorPayoutApprovalStatuses::COMPLETED,
                );

                /**
                 * Executes before creating a payout based on the return request, allows to modify the payout data.
                 *
                 * @param int   $order_id    Order ID
                 * @param array $data        IPN request parameters
                 * @param array $order_info  Order info from ::fn_get_order_info()
                 * @param array $payout_data Payout data to be stored in the DB
                 */
                fn_set_hook('process_paypal_ipn_create_payout', $order_id, $data, $order_info, $payout_data);

                \Tygh\VendorPayouts::instance()->update($payout_data);
            }
        }
        // store IPN receiving time
        $cart['payment_info']['addons.paypal.ipn_receiving_time'] = fn_date_format(
            TIME,
            Registry::get('settings.Appearance.date_format'). ' ' . Registry::get('settings.Appearance.time_format')
        );
        if (!empty($data['payer_email'])) {
            $cart['payment_info']['customer_email'] = $data['payer_email'];
        }
        if (!empty($data['payer_id'])) {
            $cart['payment_info']['client_id'] = $data['payer_id']; 
        }
        if (!empty($order_info['payment_surcharge'])) {
            $cart['payment_surcharge'] = $order_info['payment_surcharge'];
        }
        $cart['notes'] = implode(PHP_EOL, $cart['notes']);

        // mark order as incomplete to increase inventory
        fn_change_order_status($order_id, STATUS_INCOMPLETED_ORDER);
        //Sometimes, for some reasons cart_id in product products calculated incorrectly, so we need recalculate it.
        $cart['change_cart_products'] = true;
        // Store shipping rates
        fn_store_shipping_rates($order_id, $cart, $customer_auth);

        fn_calculate_cart_content($cart, $customer_auth);

        if ($forced_status) {
            $cart['payment_info']['order_status'] = $forced_status;
        } else {
            $cart['payment_info']['order_status'] = $pp_settings['pp_statuses'][strtolower($data['payment_status'])];
        }
        $cart['payment_info']['reason_text'] = __('paypal_ipn_transaction_status') . ': ' . $data['payment_status'];
        list($order_id, ) = fn_update_order($cart, $order_id);

        if ($order_id) {
            fn_change_order_status($order_id, $cart['payment_info']['order_status']);
            if (in_array($cart['payment_info']['order_status'], fn_get_order_paid_statuses())) {
                db_query('DELETE FROM ?:user_session_products WHERE order_id = ?i AND type = ?s', $order_id, 'C');
            }
            if (fn_allowed_for('MULTIVENDOR')) {
                $child_order_ids = db_get_fields("SELECT order_id FROM ?:orders WHERE parent_order_id = ?i", $order_id);
                if (!empty($child_order_ids)) {
                    foreach ($child_order_ids as $child_order_id) {
                        fn_update_order_payment_info($child_order_id, $cart['payment_info']);
                        fn_pp_set_customer_notes($child_order_id, $cart['notes']);
                    }
                }
            }
        }

        return true;
    }
}

function fn_pp_get_ipn_order_ids($data)
{
    $order_ids = (array)(int)$data['custom'];
    fn_set_hook('paypal_get_ipn_order_ids', $data, $order_ids);

    return $order_ids;
}

function fn_paypal_prepare_checkout_payment_methods(&$cart, &$auth, &$payment_groups)
{
    if (isset($cart['payment_id'])) {
        foreach ($payment_groups as $tab => $payments) {
            foreach ($payments as $payment_id => $payment_data) {
                if (isset(Tygh::$app['session']['pp_express_details'])) {
                    if ($payment_id != $cart['payment_id']) {
                        unset($payment_groups[$tab][$payment_id]);
                    } else {
                        $_tab = $tab;
                    }
                }
            }
        }
        if (isset($_tab)) {
            $_payment_groups = $payment_groups[$_tab];
            $payment_groups = array();
            $payment_groups[$_tab] = $_payment_groups;
        }
    }
}

function fn_pp_standart_prepare_products($order_info, $paypal_currency = '', $max_pp_products = MAX_PAYPAL_PRODUCTS)
{
    if (empty($paypal_currency)) {
        $paypal_currency = !empty($order_info['payment_method']['processor_params']['currency']) ? $order_info['payment_method']['processor_params']['currency'] : CART_PRIMARY_CURRENCY;
    }

    $currency = fn_paypal_get_valid_currency($paypal_currency);
    $post_data = array();
    $product_count = 1;
    $paypal_currency = $currency['code'];

    if ($paypal_currency != CART_PRIMARY_CURRENCY) {
        $post_data['item_name_1'] = __('total_product_cost');
        $post_data['amount_1'] = fn_format_price_by_currency($order_info['total'], CART_PRIMARY_CURRENCY, $paypal_currency);
        $post_data['quantity_1'] = '1';

        return array($post_data, 1);
    }

    $paypal_shipping = fn_order_shipping_cost($order_info);
    $paypal_total = fn_format_price($order_info['total'] - $paypal_shipping, $paypal_currency);

    if (empty($order_info['use_gift_certificates']) && !floatval($order_info['subtotal_discount']) && empty($order_info['points_info']['in_use']) && count($order_info['products']) < MAX_PAYPAL_PRODUCTS) {
        $i = 1;
        if (!empty($order_info['products'])) {
            foreach ($order_info['products'] as $k => $v) {
                $suffix = '_'.($i++);
                $v['product'] = htmlspecialchars(strip_tags($v['product']));
                $v['price'] = fn_format_price(($v['subtotal'] - fn_external_discounts($v)) / $v['amount'], $paypal_currency);
                $post_data["item_name$suffix"] = $v['product'];
                $post_data["amount$suffix"] = $v['price'];
                $post_data["quantity$suffix"] = $v['amount'];
                if (!empty($v['product_options'])) {
                    foreach ($v['product_options'] as $_k => $_v) {
                        $_v['option_name'] = htmlspecialchars(strip_tags($_v['option_name']));
                        $_v['variant_name'] = htmlspecialchars(strip_tags($_v['variant_name']));
                        $post_data["on$_k$suffix"] = $_v['option_name'];
                        $post_data["os$_k$suffix"] = $_v['variant_name'];
                    }
                }
            }
        }

        if (!empty($order_info['taxes']) && Registry::get('settings.General.tax_calculation') == 'subtotal') {
            foreach ($order_info['taxes'] as $tax_id => $tax) {
                if ($tax['price_includes_tax'] == 'Y') {
                    continue;
                }
                $suffix = '_' . ($i++);
                $item_name = htmlspecialchars(strip_tags($tax['description']));
                $item_price = fn_format_price($tax['tax_subtotal'], $paypal_currency);
                $post_data["item_name$suffix"] = $item_name;
                $post_data["amount$suffix"] = $item_price;
                $post_data["quantity$suffix"] = '1';
            }
        }

        // Gift Certificates
        if (!empty($order_info['gift_certificates'])) {
            foreach ($order_info['gift_certificates'] as $k => $v) {
                $suffix = '_' . ($i++);
                $v['gift_cert_code'] = htmlspecialchars($v['gift_cert_code']);
                $v['amount'] = (!empty($v['extra']['exclude_from_calculate'])) ? 0 : fn_format_price($v['amount'], $paypal_currency);
                $post_data["item_name$suffix"] = $v['gift_cert_code'];
                $post_data["amount$suffix"] = $v['amount'];
                $post_data["quantity$suffix"] = '1';
            }
        }

        if (fn_allowed_for('MULTIVENDOR') && fn_take_payment_surcharge_from_vendor('')) {
            $take_surcharge = false;
        } else {
            $take_surcharge = true;
        }

        // Payment surcharge
        if ($take_surcharge && floatval($order_info['payment_surcharge'])) {
            $suffix = '_' . ($i++);
            $name = __('surcharge');
            $payment_surcharge_amount = fn_format_price($order_info['payment_surcharge'], $paypal_currency);
            $post_data["item_name$suffix"] = $name;
            $post_data["amount$suffix"] = $payment_surcharge_amount;
            $post_data["quantity$suffix"] = '1';
        }
        $product_count = $i - 1;
    } elseif ($paypal_total <= 0) {
        $post_data['item_name_1'] = __('total_product_cost');
        $post_data['amount_1'] = fn_format_price($order_info['total'], $paypal_currency);
        $post_data['quantity_1'] = '1';
        $post_data['amount'] = fn_format_price($order_info['total'], $paypal_currency);;
        $post_data['shipping_1'] = 0;
    } else {
        $post_data['item_name_1'] = __('total_product_cost');
        $post_data['amount_1'] = $paypal_total;
        $post_data['quantity_1'] = '1';
    }

    return array($post_data, $product_count);
}

function fn_pp_save_mode($order_info)
{
    $data['pp_mode'] = 'test';
    if (!empty($order_info['payment_method']) && !empty($order_info['payment_method']['processor_params']) && !empty($order_info['payment_method']['processor_params']['mode'])) {
        $data['pp_mode'] = $order_info['payment_method']['processor_params']['mode'];
    }
    fn_update_order_payment_info($order_info['order_id'], $data);

    return true;
}

function fn_pp_get_mode($order_id)
{
    $result = 'test';
    $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order_id);
    if (!empty($payment_info)) {
        $payment_info = unserialize(fn_decrypt_text($payment_info));
        if (!empty($payment_info['pp_mode'])) {
            $result = $payment_info['pp_mode'];
        }
    }

    return $result;
}

/**
 * Return available currencies
 * @param string $type Type of paypal (standard|express|payflow|pro|advanced|null)
 * @return array
 */
function fn_paypal_get_currencies($type = null)
{
    $paypal_currencies = array(
        'CAD' => array(
            'name' => __("currency_code_cad"),
            'code' => 'CAD',
            'id' => 124,
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'active' => true
        ),
        'EUR' => array(
            'name' => __("currency_code_eur"),
            'code' => 'EUR',
            'id' => 978,
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'active' => true
        ),
        'GBP' => array(
            'name' => __("currency_code_gbp"),
            'code' => 'GBP',
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'id' => 826,
            'active' => true
        ),
        'USD' => array(
            'name' => __("currency_code_usd"),
            'code' => 'USD',
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'id' => 840,
            'active' => true
        ),
        'JPY' => array(
            'name' => __("currency_code_jpy"),
            'code' => 'JPY',
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'id' => 392,
            'active' => true
        ),
        'RUB' => array(
            'name' => __("currency_code_rur"),
            'code' => 'RUB',
            'supports' => array('standard', 'express'),
            'id' => 643,
            'active' => true
        ),
        'AUD' => array(
            'name' => __("currency_code_aud"),
            'code' => 'AUD',
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'id' => 36,
            'active' => true
        ),
        'NZD' => array(
            'name' => __("currency_code_nzd"),
            'code' => 'NZD',
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'id' => 554,
            'active' => true
        ),
        'CHF' => array(
            'name' => __("currency_code_chf"),
            'code' => 'CHF',
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'id' => 756,
            'active' => true
        ),
        'HKD' => array(
            'name' => __("currency_code_hkd"),
            'code' => 'HKD',
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'id' => 344,
            'active' => true
        ),
        'SGD' => array(
            'name' => __("currency_code_sgd"),
            'code' => 'SGD',
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'id' => 702,
            'active' => true
        ),
        'SEK' => array(
            'name' => __("currency_code_sek"),
            'code' => 'SEK',
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'id' => 752,
            'active' => true
        ),
        'DKK' => array(
            'name' => __("currency_code_dkk"),
            'code' => 'DKK',
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'id' => 208,
            'active' => true
        ),
        'PLN' => array(
            'name' => __("currency_code_pln"),
            'code' => 'PLN',
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'id' => 985,
            'active' => true
        ),
        'NOK' => array(
            'name' => __("currency_code_nok"),
            'code' => 'NOK',
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'id' => 578,
            'active' => true
        ),
        'HUF' => array(
            'name' => __("currency_code_huf"),
            'code' => 'HUF',
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'id' => 348,
            'active' => true
        ),
        'CZK' => array(
            'name' => __("currency_code_czk"),
            'code' => 'CZK',
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'id' => 203,
            'active' => true
        ),
        'ILS' => array(
            'name' => __("currency_code_ils"),
            'code' => 'ILS',
            'supports' => array('standard', 'express', 'payflow', 'advanced'),
            'id' => 376,
            'active' => true
        ),
        'MXN' => array(
            'name' => __("currency_code_mxn"),
            'code' => 'MXN',
            'supports' => array('standard', 'express', 'payflow', 'advanced'),
            'id' => 484,
            'active' => true
        ),
        'BRL' => array(
            'name' => __("currency_code_brl"),
            'code' => 'BRL',
            'supports' => array('standard', 'express', 'payflow', 'advanced'),
            'id' => 986,
            'active' => true
        ),
        'PHP' => array(
            'name' => __("currency_code_php"),
            'code' => 'PHP',
            'supports' => array('standard', 'express', 'payflow', 'pro', 'advanced'),
            'id' => 608,
            'active' => true
        ),
        'TWD' => array(
            'name' => __("currency_code_twd"),
            'code' => 'TWD',
            'supports' => array('standard', 'express', 'payflow', 'advanced'),
            'id' => 901,
            'active' => true
        ),
        'THB' => array(
            'name' => __("currency_code_thb"),
            'code' => 'THB',
            'supports' => array('standard', 'express', 'payflow', 'advanced'),
            'id' => 764,
            'active' => true
        ),
        'TRY' => array(
            'name' => __("currency_code_try"),
            'code' => 'TRY',
            'supports' => array('standard', 'express'),
            'id' => 949,
            'active' => true
        ),
        'MYR' => array(
            'name' => __("currency_code_myr"),
            'code' => 'MYR',
            'supports' => array('standard', 'express'),
            'id' => 458,
            'active' => true
        ),
    );

    $currencies = fn_get_currencies();
    $result = array();

    foreach ($paypal_currencies as $key => &$item) {
        $item['active'] = isset($currencies[$key]);

        if ($type === null || in_array($type, $item['supports'], true)) {
            $result[$key] = $item;
        }
    }

    unset($item);

    return $result;
}

/**
 * Return currency data
 * @param string|int $id
 * @return array|false if no defined return false
 */
function fn_paypal_get_currency($id)
{
    $currencies = fn_paypal_get_currencies();

    if (is_numeric($id)) {
        foreach ($currencies as $currency) {
            if ($currency['id'] == $id) {
                return $currency;
            }
        }
    } elseif (isset($currencies[$id])) {
        return $currencies[$id];
    }

    return false;
}

/**
 * Return valid currency data
 * @param string|int $id
 * @return array
 * ```
 * array(
 *  name => string,
 *  id => int,
 *  active => bool,
 *  code => string
 * )
 * ```
 */
function fn_paypal_get_valid_currency($id)
{
    $currency = fn_paypal_get_currency($id);

    if ($currency === false || !$currency['active']) {
        $currency = fn_paypal_get_currency(CART_PRIMARY_CURRENCY);

        if ($currency === false) {
            $currency = fn_paypal_get_currency('USD');
        }
    }

    return $currency;
}

/**
 * Overrides user existence check results for guest customers who returned from Express Checkout
 *
 * @param int $user_id User ID
 * @param array $user_data User authentication data
 * @param boolean $is_exist True if user with specified email already exists
 */
function fn_paypal_is_user_exists_post($user_id, $user_data, &$is_exist)
{
    if (!$user_id && $is_exist) {
        if (isset(Tygh::$app['session']['pp_express_details']['token']) &&
            (empty($user_data['register_at_checkout']) || $user_data['register_at_checkout'] != 'Y') &&
            empty($user_data['password1']) && empty($user_data['password2'])) {
            $is_exist = false;
        }
    }
}

/**
 * Provide token and handle errors for checkout with In-Context checkout
 *
 * @param array $cart   Cart data
 * @param array $auth   Authentication data
 * @param array $params Request parameters
 */
function fn_paypal_checkout_place_orders_pre_route(&$cart, $auth, $params)
{
    $cart = empty($cart) ? array() : $cart;
    $payment_id = (empty($params['payment_id']) ? $cart['payment_id'] : $params['payment_id']);
    $processor_data = fn_get_processor_data($payment_id);

    if (!empty($processor_data['processor_script']) && $processor_data['processor_script'] == 'paypal_express.php' &&
        isset($params['in_context_order']) && $processor_data['processor_params']['in_context'] == 'Y'
    ) {
        // parent order has the smallest identifier of all the processed orders
        $order_id = min($cart['processed_order_id']);
        $result = fn_paypal_set_express_checkout($payment_id, $order_id, array(), $cart, AREA);

        if (fn_paypal_ack_success($result) && !empty($result['TOKEN'])) {
            // set token for in-context checkout
            header('Content-type: application/json');
            echo json_encode(array('token' => $result['TOKEN']));

        } else {
            // create notification
            fn_paypal_get_error($result);
            header('Content-type: application/json');
            echo json_encode(array('error' => true));
        }
        exit;
    }
}

/**
 * Checks if payment processor is the one provided by the add-on.
 *
 * @param int $processor_id
 *
 * @return bool True if processor is PayPal-based
 */
function fn_is_paypal_processor($processor_id = 0)
{
    return (bool) db_get_field("SELECT 1 FROM ?:payment_processors WHERE processor_id = ?i AND addon = ?s", $processor_id, 'paypal');
}

/**
 * Gets IDs of PayPal payment processors
 *
 * @return array Processor IDs
 */
function fn_get_paypal_processors()
{
    static $processors = array();
    if (!$processors) {
        $processors = db_get_fields("SELECT processor_id FROM ?:payment_processors WHERE addon = ?s", 'paypal');
    }

    return $processors;
}

/**
 * Checks if return was already refunded via PayPal
 *
 * @param int $return_id Return identifier
 * @return bool True if refunded
 */
function fn_is_paypal_refund_performed($return_id)
{
    $return_data = fn_get_return_info($return_id);
    $return_data['extra'] = empty($return_data['extra'])? array() : unserialize($return_data['extra']);

    return !empty($return_data['extra']['paypal_refund_transaction_id']);
}

/**
 * Formats phone number for PayPal Standard request.
 *
 * @param string $number Phone number
 * @param string $country Country code accordingly to ISO 3166-1
 * @param array $rules Phone number validation rules
 *
 * @return array phone_a, phone_b and phone_c fields for PayPal Standard request
 */
function fn_pp_format_phone_number($number, $country, $rules = array())
{
    $number = preg_replace('/[^\d\+]/', '', $number);
    $is_international = strpos($number, '+') === 0;
    $local_number = str_replace('+', '', $number);

    $country_detected = false;
    $country_code = $phone_number = $extra = '';

    if (empty($rules)) {
        $rules = fn_get_schema('paypal', 'phone_validation_rules');
    }
    if (isset($rules[$country])) {
        $regex = fn_pp_get_phone_validation_rule($rules[$country]);
        foreach($rules[$country] as $int_code) {
            $number_to_validate = $is_international ? $number : "+{$int_code}{$local_number}";
            if (preg_match($regex, $number_to_validate)) {
                $country_code = $int_code;
                $phone_number = ltrim(substr($number_to_validate, strlen($int_code) + 1), '0');
                $country_detected = true;
                break;
            }
        }

    }
    if (!$country_detected) {
        $country_code = (string) substr($local_number, 0, 3);
        $phone_number = (string) substr($local_number, 3);
    } elseif ($country == 'US') {
        $country_code = (string) substr($phone_number, 0, 3);
        $extra = (string) substr($phone_number, 6, 4);
        $phone_number = (string) substr($phone_number, 3, 3);
    } elseif (strlen($country_code) > 3) {
        // country code sent to paypal max length is 3 digits
        $phone_number = substr($country_code, 3) . $phone_number;
        $country_code = substr($country_code, 0, 3);
    }

    return array($country_code, $phone_number, $extra);
}

/**
 * Provides regex to validate phone number for PayPal Standard.
 *
 * @param array $schema Validation schema for the selected country
 *
 * @return string Regex to validate phone number
 */
function fn_pp_get_phone_validation_rule($schema)
{
    return '/\+(' . implode('|', $schema) . ')\d+$/';
}

/**
 * Returns status of the order.
 * If order is the parent order, the status of the its first child is returned.
 *
 * @param array $order_info Order info obtained from ::fn_get_order_info()
 *
 * @return array Status of the order or its first child.
 */
function fn_pp_get_order_status($order_info)
{
    if ($order_info['is_parent_order'] != 'Y') {
        return $order_info['status'];
    }

    return db_get_field(
        "SELECT status"
        . " FROM ?:orders"
        . " WHERE parent_order_id = ?i"
        . " ORDER BY order_id ASC"
        . " LIMIT 1",
        $order_info['order_id']
    );
}

/**
 * Updates customer notes of the order.
 *
 * @param int    $order_id       Order identifier
 * @param string $customer_notes Notes text
 *
 * @return bool Always true
 */
function fn_pp_set_customer_notes($order_id, $customer_notes)
{
    db_query(
        "UPDATE ?:orders"
        . " SET notes = ?s"
        . " WHERE order_id = ?i",
        $customer_notes,
        $order_id
    );

    return true;
}

/**
 * Checks if PayPal IPN for the order is received by searching for the IPN receiving time
 * in the order's payment information.
 *
 * @param int $order_id Order identifier
 *
 * @return bool True if IPN was received
 */
function fn_is_paypal_ipn_received($order_id)
{
    $order_info = fn_get_order_info($order_id);

    return !empty($order_info['payment_info']['addons.paypal.ipn_receiving_time']);
}

/**
 * Hook handler: clears the cart in the session if IPN for placed orders is already received.
 *
 * @param array $auth       Current user session data
 * @param array $user_info  User infromation obtained from ::fn_get_user_short_info
 * @param bool  $first_init True if stored in session data used to log in the user
 */
function fn_paypal_user_init(&$auth, &$user_info, &$first_init)
{
    $orders_list = array();
    if (!empty(Tygh::$app['session']['cart']['processed_order_id'])) {
        $orders_list = array_merge($orders_list, (array)Tygh::$app['session']['cart']['processed_order_id']);
    }
    if (!empty(Tygh::$app['session']['cart']['failed_order_id'])) {
        $orders_list = array_merge($orders_list, (array)Tygh::$app['session']['cart']['failed_order_id']);
    }
    foreach ($orders_list as $order_id) {
        if (fn_is_paypal_ipn_received($order_id)) {
            fn_clear_cart(Tygh::$app['session']['cart'], true, true);
            break;
        }
    }
}

/**
 * Gets list of order identifiers whose IPN is currently processing.
 *
 * @return array Order identifiers
 */
function fn_pp_get_locked_orders()
{
    $orders_ids = fn_get_storage_data('paypal_locked_orders');

    if ($orders_ids) {
        return explode(',', $orders_ids);
    }

    return array();
}

/**
 * Marks or unmarks orders as processors of IPN.
 *
 * @param array $orders_ids        Orders' identifiers
 * @param bool  $are_locked        True IPN for the orders is currently processing, false if processing is finished
 * @param array $locked_orders_ids Currently locked orders (leave empty to fetch from the DB)
 * @return array
 */
function fn_pp_set_orders_lock($orders_ids = array(), $are_locked = true, $locked_orders_ids = array())
{
    $orders_ids = (array)$orders_ids;

    if (!$locked_orders_ids) {
        $locked_orders_ids = fn_pp_get_locked_orders();
    }

    if ($are_locked) {
        $orders_ids = array_unique(array_merge($locked_orders_ids, $orders_ids));
    } else {
        $orders_ids = array_diff($locked_orders_ids, $orders_ids);
    }

    fn_set_storage_data('paypal_locked_orders', implode(',', $orders_ids), true);

    return array_values($orders_ids);
}

/**
 * Checks if IPN for the order is currently processing.
 *
 * @param int $order_id Order identifier
 *
 * @return bool True if IPN is processing, false otherwise
 */
function fn_pp_is_order_locked($order_id = 0)
{
    $locked_order_ids = fn_pp_get_locked_orders();

    return in_array($order_id, $locked_order_ids);
}

/**
 * Checks if IPN is sent by PayPal.
 *
 * @param array $data Payload
 *
 * @return array Validation result, orders processed in the IPN and payload for ::fn_process_paypal_ipn()
 */
function fn_pp_validate_ipn_payload($data)
{
    $result = '';
    $order_ids = array();

    unset($data['dispatch']);
    $data['cmd'] = '_notify-validate';
    $data = array_merge(array('cmd' => '_notify-validate'), $data);
    // the txn_type variable absent in case of refund
    if (!isset($data['txn_type']) || in_array($data['txn_type'], array('cart', 'express_checkout', 'web_accept', 'pro_api'))) {
        $order_ids = fn_pp_get_ipn_order_ids($data);
        // lock orders while processing IPN
        fn_pp_set_orders_lock($order_ids, true);
        $mode = fn_pp_get_mode(reset($order_ids));
        $url = ($mode == 'test') ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
        $result = Http::post($url, $data);
    }

    return array($result, $order_ids, $data);
}