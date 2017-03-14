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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $return_url = !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : '';

    if ($mode == 'request') {

        if (!empty($_REQUEST['call_data'])) {

            $product_data = !empty($_REQUEST['product_data']) ? $_REQUEST['product_data'] : array();

            if ($res = fn_do_call_request($_REQUEST['call_data'], $product_data, Tygh::$app['session']['cart'], Tygh::$app['session']['auth'])) {
                if (!empty($res['error'])) {
                    fn_set_notification('E', __('error'), $res['error']);
                } elseif (!empty($res['notice'])) {
                    fn_set_notification('N', __('notice'), $res['notice']);
                }
            }

        }

    }

    return array(CONTROLLER_STATUS_OK, $return_url);
}

if ($mode == 'request') {

    if (!empty($_REQUEST['product_id'])) {
        $product = fn_get_product_data($_REQUEST['product_id'], $auth, DESCR_SL);
        Tygh::$app['view']->assign('product', $product);
        $_REQUEST['obj_id'] = $product['product_id'];
    }
    Tygh::$app['view']->assign('obj_prefix', !empty($_REQUEST['obj_prefix']) ? $_REQUEST['obj_prefix'] : '');
    Tygh::$app['view']->assign('obj_id', !empty($_REQUEST['obj_id']) ? $_REQUEST['obj_id'] : '');

}
