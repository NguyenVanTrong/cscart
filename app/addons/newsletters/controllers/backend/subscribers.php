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

if ($_SERVER['REQUEST_METHOD']	== 'POST') {

    $suffix = '.manage';

    if ($mode == 'update') {
        if (!empty($_REQUEST['subscriber_data']['list_ids'])) {
            $list_id = reset($_REQUEST['subscriber_data']['list_ids']);
            if (!empty($list_id)) {
                $suffix .= '?list_id=' . $list_id;
            }
        }

        fn_update_subscriber($_REQUEST['subscriber_data'], $_REQUEST['subscriber_id']);
    }

    if ($mode == 'add_users') {

        if (!empty($_REQUEST['add_users'])) {
            $checked_users = array();

            $users = db_get_array("SELECT user_id, email, lang_code FROM ?:users WHERE user_id IN (?n)", $_REQUEST['add_users']);

            $list_ids = array();
            if (!empty($_REQUEST['list_id'])) {
                $list_ids[] = $_REQUEST['list_id'];
                $suffix .= '?list_id=' . $_REQUEST['list_id'];
            }

            foreach ($users as $user) {
                $subscriber_data = array(
                    'email' => $user['email'],
                    'lang_code' => $user['lang_code'],
                    'list_ids' => $list_ids,
                );

                fn_update_subscriber($subscriber_data);

            }
        }
    }

    if ($mode == 'm_update') {
        if (!empty($_REQUEST['subscribers'])) {
            foreach ($_REQUEST['subscribers'] as $subscriber_id => $v) {
                fn_update_subscriber($v, $subscriber_id);
            }
        }
    }

    if ($mode == 'm_delete') {
        fn_delete_subscribers($_REQUEST['subscriber_ids']);
    }

    if ($mode == 'delete') {
        if (!empty($_REQUEST['subscriber_id'])) {
            fn_delete_subscribers((array) $_REQUEST['subscriber_id']);
        }
    }

    return array(CONTROLLER_STATUS_OK, 'subscribers' . $suffix);
}

if ($mode == 'manage') {

    list($subscribers, $search) = fn_get_subscribers($_REQUEST, Registry::get('settings.Appearance.admin_elements_per_page'));

    foreach ($subscribers as &$subscriber) {
        if (!empty($subscriber['list_ids'])) {
            $subscriber['mailing_lists'] = array();
            foreach (explode(',', $subscriber['list_ids']) as $list_id) {
                $subscriber['mailing_lists'][$list_id] = fn_get_mailing_list_data($list_id, DESCR_SL);
                // get additional user-specific data for each mailing list (like lang_code)
                $_where = array(
                    'list_id' => $list_id,
                    'subscriber_id' => $subscriber['subscriber_id']
                );
                $subscriber_list_data = db_get_row("SELECT * FROM ?:user_mailing_lists WHERE ?w", $_where);
                $subscriber['mailing_lists'][$list_id] = array_merge($subscriber['mailing_lists'][$list_id], $subscriber_list_data);

                $subscriber['lang_code'] = $subscriber['mailing_lists'][$list_id]['lang_code'];
            }

            unset($subscriber['list_ids']);
        }
    }

    $mailing_lists = db_get_hash_array("SELECT m.list_id, d.object, ?:newsletters.newsletter_id as register_autoresponder FROM ?:mailing_lists AS m INNER JOIN ?:common_descriptions AS d ON m.list_id=d.object_id LEFT JOIN ?:newsletters ON m.register_autoresponder = ?:newsletters.newsletter_id AND ?:newsletters.status = 'A' WHERE d.object_holder='mailing_lists' AND d.lang_code = ?s", 'list_id', DESCR_SL);

    Tygh::$app['view']->assign('mailing_lists', $mailing_lists);
    Tygh::$app['view']->assign('subscribers', $subscribers);
    Tygh::$app['view']->assign('search', $search);

    fn_newsletters_generate_sections('subscribers');
}

/** /Body **/
