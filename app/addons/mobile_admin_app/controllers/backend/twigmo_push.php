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

use ExTwigmo\Core\TwigmoConnector;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'send') {
        if (!empty($_REQUEST['push'])) {
            $sent_is_ok = fn_ex_twg_send_mass_push_notification(new TwigmoConnector(), $_REQUEST['push']);
            if ($sent_is_ok) {
                fn_set_notification('N', fn_ex_twg_get_lang_var('notice'), fn_ex_twg_get_lang_var('twgadmin_push_has_uploaded'), 'S');
            } else {
                fn_set_notification('E', fn_ex_twg_get_lang_var('error'), fn_ex_twg_get_lang_var('twgadmin_send_push_error'), 'S');
            }
        }
        die();
    }
}
