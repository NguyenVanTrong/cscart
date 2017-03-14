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

    $discussion_settings = Registry::get('addons.discussion');
    $discussion_object_types = fn_get_discussion_objects();

    $suffix = '';
    if ($mode == 'add') {
        $suffix = '&selected_section=discussion';

        fn_add_discussion_post($_REQUEST['post_data']);
    }

    if ($mode == 'update') {
        if (!empty($_REQUEST['posts'])) {
            fn_update_discussion_posts($_REQUEST['posts']);
        }
    }

    $redirect_url = "discussion_manager.manage";
    if (!empty($_REQUEST['redirect_url'])) {
        $redirect_url = $_REQUEST['redirect_url'] . $suffix;
    }

    return array(CONTROLLER_STATUS_OK, $redirect_url);
}

if ($mode == 'view') {
    $data = fn_discussion_get_object(array(
        'thread_id' => $_REQUEST['thread_id'],
    ));
    if (empty($data)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    if (AREA != 'A') {
        // Check if user has an access for this thread
        if (fn_is_accessible_discussion($data, $auth) == false) {
            return array(CONTROLLER_STATUS_DENIED);
        }

        if ($data['object_type'] == 'E' && !empty($_REQUEST['post_id'])) {
            $post_pos = db_get_field("SELECT COUNT(*) FROM ?:discussion_posts WHERE thread_id = ?i AND post_id >= ?i AND status = 'A' ORDER BY timestamp DESC", $_REQUEST['thread_id'], $_REQUEST['post_id']);
            if (!empty($post_pos)) {
                $sets = Registry::get('addons.discussion');
                $discussion_object_types = fn_get_discussion_objects();
                $items_per_page = $sets[$discussion_object_types[$data['object_type']] . '_posts_per_page'];
                $page = ceil($post_pos / $items_per_page);
                if ((empty($_REQUEST['page']) && $page != 1) || (!empty($_REQUEST['page']) && $page != $_REQUEST['page'])) {
                    $_REQUEST['page'] = $page;
                }
                Tygh::$app['session']['discussion_post_id'] = $_REQUEST['post_id'];

                return array(CONTROLLER_STATUS_REDIRECT, fn_query_remove(Registry::get('config.current_url'), 'page', 'post_id'));
            }
        }
    }

    $show_discussion_crumb = true;
    if ($data['object_type'] == 'E') { // testimonials
        $show_discussion_crumb = false;
    }

    $discussion_object_data = fn_get_discussion_object_data($data['object_id'], $data['object_type']);

    fn_add_breadcrumb($discussion_object_data['description'], $discussion_object_data['url']);

    if ($show_discussion_crumb && AREA != 'A') {
        fn_add_breadcrumb(__('discussion'));
    }

    if (!empty(Tygh::$app['session']['discussion_post_id'])) {
        Tygh::$app['view']->assign('current_post_id', Tygh::$app['session']['discussion_post_id']);
        unset(Tygh::$app['session']['discussion_post_id']);
    }

    $discussion = fn_get_discussion($data['object_id'], $data['object_type'], true, $_REQUEST);

    Tygh::$app['view']->assign('search', $discussion['search']);
    Tygh::$app['view']->assign('object_id', $data['object_id']);
    Tygh::$app['view']->assign('title', $discussion_object_data['description']);
    Tygh::$app['view']->assign('object_type', $data['object_type']);
}
