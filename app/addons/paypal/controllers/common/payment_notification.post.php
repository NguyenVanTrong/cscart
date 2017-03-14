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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'paypal_ipn') {
        if (!empty($_REQUEST['custom'])) {

            list($result, $order_ids, $data) = fn_pp_validate_ipn_payload($_REQUEST);

            if ($result == 'VERIFIED') {
                fn_define('ORDER_MANAGEMENT', true);
                foreach($order_ids as $order_id) {
                    fn_process_paypal_ipn($order_id, $data);
                    // unlock order after processing IPN
                    fn_pp_set_orders_lock($order_id, false);
                }
            }
        }
        exit;
    }
}
