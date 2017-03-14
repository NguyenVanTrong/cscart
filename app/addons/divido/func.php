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

/**
 * Adds the "Divido" payment processor to the list
 */
function fn_divido_install()
{
    fn_divido_uninstall();

    db_query("INSERT INTO ?:payment_processors ?e", array (
        'processor' => 'Divido',
        'processor_script' => 'divido.php',
        'processor_template' => 'addons/divido/views/orders/components/payments/divido.tpl',
        'admin_template' => 'divido.tpl',
        'callback' => 'Y',
        'type' => 'P'
    ));
}

/**
 * Removes the "Divido" payment processor from the list
 */
function fn_divido_uninstall()
{
    db_query("DELETE FROM ?:payment_processors WHERE processor_script = ?s", 'divido.php');
}

/**
 * Get divido processor params
 *
 * @return array|string
 */
function fn_divido_get_processor_params()
{
    $processor_params = db_get_field(
        'SELECT ?:payments.processor_params'
        . ' FROM ?:payments'
        . ' LEFT JOIN ?:payment_processors'
        . ' ON ?:payment_processors.processor_id = ?:payments.processor_id'
        . ' WHERE ?:payment_processors.processor_script = ?s'
        . ' AND ?:payments.status = ?s', DIVIDO_PROCESSOR_SCRIPT, 'A'
    );

    return !empty($processor_params) ? unserialize($processor_params) : '';
}

/**
 * Hook.
 * This hook is needed for divido calculator on the checkout page.
 * Changes params to get payment processors.
 *
 * @param array $params    Array of flags/data which determines which data should be gathered
 * @param array $fields    List of fields for retrieving
 * @param array $join      Array with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
 * @param array $order     Array containing SQL-query with sorting fields
 * @param array $condition Array containing SQL-query condition possibly prepended with a logical operator AND
 * @param array $having    Array containing SQL-query condition to HAVING group
 */
function fn_divido_get_payments($params, $fields, $join, $order, &$condition, $having)
{
    $mode = Registry::get('runtime.mode');

    if (($mode == 'checkout' || $mode == 'add') && !empty(Tygh::$app['session']['cart'])) {

        $processor_params = fn_divido_get_processor_params();
        $cart = Tygh::$app['session']['cart'];

        if (!empty($cart['user_data']) && !empty($processor_params['currency'])) {

            if (!empty($cart['user_data']['user_id'])) {
                $cart['user_data'] = fn_get_user_info($cart['user_data']['user_id'], true);
            }

            $total = isset($cart['payment_surcharge']) ? $cart['total'] + $cart['payment_surcharge'] : $cart['total'];
            $total = fn_format_price_by_currency($total, CART_PRIMARY_CURRENCY, $processor_params['currency']);

            if (!empty($processor_params['cart_amount_limit'])
                && $processor_params['cart_amount_limit'] > $total
                || $processor_params['currency'] != CART_SECONDARY_CURRENCY
                || $cart['user_data']['b_country'] != DIVIDO_COUNTRY
            ) {
                $condition[] = 'IF(STRCMP(?:payment_processors.processor_script, "' . DIVIDO_PROCESSOR_SCRIPT . '") = 0, 0, 1)';
                if (isset(Tygh::$app['session']['cart']['payment_id'])) {
                    unset(Tygh::$app['session']['cart']['payment_id']);
                }
            }
        }
    }
}

/**
 * Hook.
 * This hook is needed for divido calculator on the product page.
 * Adds additional data to product.
 *
 * @param array $product Product data
 * @param mixed $auth Array of user authentication data
 * @param array $params Parameteres for gathering data
 */
function fn_divido_gather_additional_product_data_post(&$product, $auth, $params)
{
    $user_data = array();
    if (!empty(Tygh::$app['session']['cart']['user_data'])) {
        $user_data = Tygh::$app['session']['cart']['user_data'];
        if (isset($user_data['user_id'])) {
            $user_data = fn_get_user_info($user_data['user_id'], true);
        }
    }

    if (!empty($user_data)
        && $user_data['b_country'] == DIVIDO_COUNTRY
        && !empty($product['price'])
    ) {

        $processor_params = fn_divido_get_processor_params();

        if (!empty($processor_params['show_product_page_calculator'])
            && !empty($processor_params['api_key'])
            && !empty($processor_params['currency'])
            && $processor_params['currency'] == CART_SECONDARY_CURRENCY
            && $processor_params['show_product_page_calculator'] == 'Y'
        ) {

            $divido_data = array(
                'price' => fn_format_price_by_currency($product['price'], CART_PRIMARY_CURRENCY, $processor_params['currency']),
                'api_key' => fn_divido_slice_api_key($processor_params['api_key']),
                'currency' => $processor_params['currency']
            );

            if (empty($processor_params['product_price_limit'])
                || $divido_data['price'] >= $processor_params['product_price_limit']
            ) {
                $product['divido_data'] = $divido_data;
            }
        }
    }
}

/**
 * Slice api key
 *
 * @param  string $api_key
 * @return string
 */
function fn_divido_slice_api_key($api_key)
{
    if (!empty($api_key)) {
        $str = strpos($api_key, '.');
        $api_key = substr($api_key, 0, $str);
    }

    return $api_key;
}

/**
 * Handles the process of changing product options on product detailed page.
 *
 * @param string $mode Runtime mode
 * @param array  $data Recalculated product data or cart products
 */
function fn_divido_after_options_calculation(&$mode, &$data)
{
    if ($mode == 'options' && $product = Tygh::$app['view']->getTemplateVars('product')) {
        if (empty($product['divido_data'])) {
            $product['divido_data'] = array();
        }
        $product['divido_data']['show_calculator'] = !empty($product['divido_data']['price']) && !empty($product['divido_data']['api_key']);

        Tygh::$app['view']->assign('product', $product);
    }
}