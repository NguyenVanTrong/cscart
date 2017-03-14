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

if ($mode == 'view') {
    $lang_code = empty($_REQUEST['lang']) ? CART_LANGUAGE : $_REQUEST['lang'];
    list($items_data, $additional_data) = fn_rssf_get_items($_REQUEST, $lang_code);

    header('Content-Type: text/xml; charset=utf-8');
    fn_echo(fn_generate_rss($items_data, $additional_data));
    exit;

} elseif ($mode == 'add_to_cart') {
    if (empty($auth['user_id']) && Registry::get('settings.General.allow_anonymous_shopping') != 'allow_shopping') {
        return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode($_SERVER['HTTP_REFERER']));
    }

    $cart = & Tygh::$app['session']['cart'];
    $lang_code = empty($_REQUEST['lang']) ? CART_LANGUAGE : $_REQUEST['lang'];

    $product_data = array (
        $_REQUEST['product_id'] => array (
            'product_id' => $_REQUEST['product_id'],
            'amount' => 1
        )
    );

    fn_add_product_to_cart($product_data, $cart, $auth);
    fn_save_cart_content($cart, $auth['user_id']);
    fn_calculate_cart_content($cart, $auth, 'S', true, 'F', true);

    return array(CONTROLLER_STATUS_OK, fn_url('checkout.cart?sl=' . $lang_code, 'C', 'http', $lang_code, true));
}
