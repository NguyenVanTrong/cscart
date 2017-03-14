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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'send') {
        if (!empty($_REQUEST['send_data']['to_email'])) {

            $redirect_url = fn_query_remove($_REQUEST['redirect_url'], 'selected_section');

            $from = array(
                'email' => !empty($_REQUEST['send_data']['from_email']) ? $_REQUEST['send_data']['from_email'] : Registry::get('settings.Company.company_users_department'),
                'name' => !empty($_REQUEST['send_data']['from_name']) ? $_REQUEST['send_data']['from_name'] : Registry::get('settings.Company.company_name'),
            );

            /** @var \Tygh\Mailer\Mailer $mailer */
            $mailer = Tygh::$app['mailer'];

            $mail_sent = $mailer->send(array(
                'to' => $_REQUEST['send_data']['to_email'],
                'from' => $from,
                'data' => array(
                    'link' => fn_url($redirect_url),
                    'send_data' => $_REQUEST['send_data']
                ),
                'template_code' => 'social_buttons_mail',
                'tpl' => 'addons/social_buttons/mail.tpl', // this parameter is obsolete and is used for back compatibility
            ), 'C');

            if ($mail_sent == true) {
                fn_set_notification('N', __('notice'), __('text_email_sent'));
            }
        } else {
            fn_set_notification('E', __('error'), __('error_no_recipient_address'));
        }

        return array(CONTROLLER_STATUS_REDIRECT);
    }
}
