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

if ( !defined('BOOTSTRAP') )    { die('Access denied');    }

use Tygh\Registry;
use ExTwigmo\Core\TwigmoConnector;

require_once(Registry::get('config.dir.addons') . 'mobile_admin_app/ExTwigmo/Core/phpqrcode/qrlib.php');

if ($mode == 'show_qr') {

    $access_id = fn_ex_twg_get_connected_access_id($auth);

    if (!$access_id) {
        die();
    }

    $user_info = Registry::get('user_info');
    $login = Registry::get('settings.General.use_email_as_login') == 'N' ? $user_info['user_login'] : $user_info['email'];

    // outputs image directly into browser, as PNG stream
    $url = TwigmoConnector::getAdminUrl(false);

    QRcode::png($url . '_' . $login . '_' . $access_id);
    die();

} elseif ($mode == 'view') {
    $view = fn_ex_twg_get_view_object();
    $view->assign('connected_access_id', fn_ex_twg_get_connected_access_id($auth));

}
