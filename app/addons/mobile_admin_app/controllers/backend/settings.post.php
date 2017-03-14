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

if ( !defined('AREA') )    { die('Access denied');    }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $mode == 'update') {
    Tygh::$app['session']['twg_need_update_connection'] = !fn_ex_twg_reconnect_on_license_change();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && $mode == 'manage') {
    if (!empty(Tygh::$app['session']['twg_need_update_connection'])) {
        $view = fn_ex_twg_get_view_object();
        $view->assign('stats', fn_ex_twg_get_ajax_reconnect_code());
        Tygh::$app['session']['twg_need_update_connection'] = false;
    }
}

?>
