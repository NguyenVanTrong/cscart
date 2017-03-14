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
    if ($mode == 'verify') {
        if (!empty($_REQUEST['age'])) {
            $age = intval($_REQUEST['age']);

            if ($age < 0) {
                $age = 0;
            }

            Tygh::$app['session']['auth']['age'] = $age;

            if (!empty($_REQUEST['redirect_url'])) {
                return array (CONTROLLER_STATUS_OK, $_REQUEST['redirect_url']);
            }

            return array (CONTROLLER_STATUS_REDIRECT, '');
        }
    }
}

if ($mode == 'verify') {
    fn_add_breadcrumb(__('age_verification'));
    $available_objects = array('product_descriptions' => 'product_id', 'category_descriptions' => 'category_id');
    if (isset($_REQUEST['object']) && in_array($_REQUEST['object'], array_keys($available_objects)) && isset($_REQUEST['object_id'])) {
        $field = $available_objects[$_REQUEST['object']];
        $message = db_get_field("SELECT age_warning_message FROM ?:$_REQUEST[object] WHERE $field = ?i AND lang_code = ?s", $_REQUEST['object_id'], CART_LANGUAGE);
        Tygh::$app['view']->assign('age_warning_message', $message);
    }

    if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'deny') {
        Tygh::$app['view']->assign('age_warning_type', 'deny');
    } else {
        Tygh::$app['view']->assign('age_warning_type', 'form');
    }
}
