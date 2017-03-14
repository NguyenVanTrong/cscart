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

    $suffix = '';

    //
    // Create/Update usergroups
    //
    if ($mode == 'update') {

        $usergroup_id = fn_update_usergroup($_REQUEST['usergroup_data'], $_REQUEST['usergroup_id'], DESCR_SL);

        if ($usergroup_id == false) {
            fn_delete_notification('changes_saved');
        }

        $suffix .= '.manage';
    }

    //
    // Delete selected usergroups
    //
    if ($mode == 'm_delete') {
        if (!empty($_REQUEST['usergroup_ids'])) {
            fn_delete_usergroups($_REQUEST['usergroup_ids']);
        }

        $suffix .= '.manage';
    }

    if ($mode == 'bulk_update_status') {
        if (!empty($_REQUEST['link_ids'])) {
            $new_status = $action == 'approve' ? 'A' : 'D';
            db_query("UPDATE ?:usergroup_links SET status = ?s WHERE link_id IN(?n)", $new_status, $_REQUEST['link_ids']);

            $force_notification = fn_get_notification_rules($_REQUEST);
            if (!empty($force_notification['C'])) {
                $usergroup_links = db_get_hash_multi_array("SELECT * FROM ?:usergroup_links WHERE link_id IN(?n)", array('user_id', 'usergroup_id'), $_REQUEST['link_ids']);
                foreach ($usergroup_links as $u_id => $val) {
                    fn_send_usergroup_status_notification($u_id, array_keys($val), $new_status);
                }
            }
        }

        $suffix = ".requests";
    }

    if ($mode == 'delete') {
        if (!empty($_REQUEST['usergroup_id'])) {
            fn_delete_usergroups((array) $_REQUEST['usergroup_id']);
        }

        return array(CONTROLLER_STATUS_REDIRECT, 'usergroups.manage');

    }

    if ($mode == 'update_status') {
        $user_data = fn_get_user_info($_REQUEST['user_id']);
        if (empty($user_data) || (Registry::get('runtime.company_id') && $user_data['is_root'] == 'Y') || (defined('RESTRICTED_ADMIN') && ($auth['user_id'] == $_REQUEST['user_id'] || fn_is_restricted_admin(array('user_id' => $_REQUEST['user_id']))))) {
            fn_set_notification('E', __('error'), __('access_denied'));
            exit;
        }

        $group_type = db_get_field("SELECT type FROM ?:usergroups WHERE usergroup_id = ?i", $_REQUEST['id']);

        if (empty($group_type) || ($group_type == 'A' && !in_array($user_data['user_type'], array('A','V')))) {
            fn_set_notification('E', __('error'), __('access_denied'));
            exit;
        }

        $old_status = db_get_field("SELECT status FROM ?:usergroup_links WHERE user_id = ?i AND usergroup_id = ?i", $_REQUEST['user_id'], $_REQUEST['id']);

        $result = fn_change_usergroup_status($_REQUEST['status'], $_REQUEST['user_id'], $_REQUEST['id'], fn_get_notification_rules($_REQUEST));
        if ($result) {
            fn_set_notification('N', __('notice'), __('status_changed'));
        } else {
            fn_set_notification('E', __('error'), __('error_status_not_changed'));
            Tygh::$app['ajax']->assign('return_status', empty($old_status) ? 'F' : $old_status);
        }

        exit;
    }

    return array(CONTROLLER_STATUS_OK, 'usergroups' . $suffix);
}


if ($mode == 'manage') {

    $exclude_types = defined('RESTRICTED_ADMIN') ? array('A') : array();

    if (fn_allowed_for('ULTIMATE')) {
        $customer_usergroups = fn_get_usergroups(array('exclude_types' => $exclude_types, 'type' => 'C'), DESCR_SL);

        $exclude_types[] = 'C';
    }

    $usergroups = fn_get_usergroups(array('exclude_types' => $exclude_types), DESCR_SL);

    if (fn_allowed_for('ULTIMATE')) {
        $usergroups = array_merge($usergroups, $customer_usergroups);
    }

    Tygh::$app['view']->assign('usergroups', $usergroups);

    Registry::set('navigation.tabs', array (
        'general_0' => array (
            'title' => __('general'),
            'js' => true
        ),
    ));

} elseif ($mode == 'update') {

    $usergroups = fn_get_usergroups(array('usergroup_id' => $_REQUEST['usergroup_id']), DESCR_SL);
    $usergroup = $usergroups[$_REQUEST['usergroup_id']];

    Tygh::$app['view']->assign('usergroup', $usergroup);

    $tabs = array(
        'general_' . $_REQUEST['usergroup_id'] => array(
            'title' => __('general'),
            'js' => true
        ),
    );

    if ($usergroup['type'] == 'A') {
        $tabs['privilege_' . $_REQUEST['usergroup_id']] = array(
            'title' => __('privileges'),
            'js' => true
        );
    }

    /* Privilege section */
    if (defined('RESTRICTED_ADMIN')) {
        $requested_mtype = db_get_field("SELECT type FROM ?:usergroups WHERE usergroup_id = ?i", $_REQUEST['usergroup_id']);
        if ($requested_mtype == 'A') {
            unset($tabs['privilege_' . $_REQUEST['usergroup_id']]);
        }
    }

    $usergroup_name = db_get_field("SELECT usergroup FROM ?:usergroup_descriptions WHERE usergroup_id = ?i AND lang_code = ?s", $_REQUEST['usergroup_id'], DESCR_SL);

    $usergroup_privileges = db_get_hash_single_array("SELECT privilege FROM ?:usergroup_privileges WHERE usergroup_id = ?i", array('privilege', 'privilege'), $_REQUEST['usergroup_id']);

    $privileges_data = db_get_array("SELECT a.* FROM ?:privileges as a ORDER BY a.section_id");
    $_preload = array();

    foreach ($privileges_data as $key => $privilege) {
        $section = 'privilege_sections.' . $privilege['section_id'];
        if (!in_array($section, $_preload)) {
            $_preload[] = $section;
        }
        $_preload[] = 'privileges.' . $privilege['privilege'];
    }

    fn_preload_lang_vars($_preload);

    $_sections = array();
    foreach ($privileges_data as $key => $privilege) {
        $_sections[$privilege['section_id']] = __('privilege_sections.' . $privilege['section_id']);
        $privileges_data[$key]['description'] = __('privileges.' . $privilege['privilege']);
    }

    $privileges_data = fn_sort_array_by_key($privileges_data, 'description');
    asort($_sections);
    $privileges = array_fill_keys(array_keys($_sections), array());

    foreach ($privileges_data as $privilege) {
        $privilege['section'] = $_sections[$privilege['section_id']];
        $privileges[$privilege['section_id']][] = $privilege;
    }

    Tygh::$app['view']->assign('usergroup_privileges', $usergroup_privileges);
    Tygh::$app['view']->assign('usergroup_name', $usergroup_name);
    Tygh::$app['view']->assign('privileges', $privileges);

    Registry::set('navigation.tabs', $tabs);

} elseif ($mode == 'requests') {

    list($requests, $search) = fn_get_usergroup_requests($_REQUEST, Registry::get('settings.Appearance.admin_elements_per_page'));

    Tygh::$app['view']->assign('usergroup_requests', $requests);
    Tygh::$app['view']->assign('search', $search);
}

function fn_get_usergroup_requests($params, $items_per_page = 0, $status = 'P', $lang_code = CART_LANGUAGE)
{
    // Set default values to input params
    $default_params = array (
        'page' => 1,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    $fields = array (
        "?:usergroup_links.user_id",
        "?:usergroup_links.link_id",
        "?:usergroup_links.usergroup_id",
        "?:usergroup_links.status",
        "?:users.firstname",
        "?:users.lastname",
        "?:usergroup_descriptions.usergroup"
    );

    $sortings = array (
        'customer' => array("?:users.lastname", "?:users.firstname"),
        'usergroup' => "?:usergroup_descriptions.usergroup",
        'status' => "?:usergroup_links.status"
    );

    $sorting = db_sort($params, $sortings, 'customer', 'desc');
    $condition = '';

    if (!empty($params['cname'])) {
        $arr = explode(' ', $params['cname']);
        if (sizeof($arr) == 2) {
            $condition .= db_quote(" AND ?:users.firstname LIKE ?l AND ?:users.lastname LIKE ?l", "%$arr[0]%", "%$arr[1]%");
        } else {
            $condition .= db_quote(" AND (?:users.firstname LIKE ?l OR ?:users.lastname LIKE ?l)", "%$params[cname]%", "%$params[cname]%");
        }
    }

    if (!empty($params['ugname'])) {
        $condition .= db_quote(" AND ?:usergroup_descriptions.usergroup LIKE ?l", "%$params[ugname]%");
    }

    $join = db_quote("LEFT JOIN ?:users ON ?:usergroup_links.user_id = ?:users.user_id LEFT JOIN ?:usergroup_descriptions ON ?:usergroup_links.usergroup_id = ?:usergroup_descriptions.usergroup_id AND ?:usergroup_descriptions.lang_code = ?s", $lang_code);

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(?:usergroup_links.link_id) FROM ?:usergroup_links $join WHERE ?:usergroup_links.status = ?s $condition", $status);
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $requests = db_get_array("SELECT " . implode(', ', $fields) . " FROM ?:usergroup_links $join WHERE ?:usergroup_links.status = ?s $condition $sorting $limit", $status);

    return array($requests, $params);
}
