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

if ($mode == 'manage') {

    $discussion_object_types = fn_get_discussion_objects();
    $discussion_object_titles = fn_get_discussion_titles();

    if (empty($_REQUEST['object_type'])) {
        reset($discussion_object_types);
        $_REQUEST['object_type'] = key($discussion_object_types); // FIXME: bad style
    }

    $_url = fn_query_remove(Registry::get('config.current_url'), 'object_type', 'page');
    foreach ($discussion_object_types as $obj_type => $obj) {
        if ($obj_type == 'E' && Registry::ifGet('addons.discussion.home_page_testimonials', 'D') == 'D') {
            continue;
        }

        $_name = __($discussion_object_titles[$obj_type]);

        Registry::set('navigation.tabs.' . $obj, array (
            'title' => $_name,
            'href' => $_url . '&object_type=' . $obj_type,
        ));

    }

    list($posts, $search) = fn_get_discussions($_REQUEST, Registry::get('settings.Appearance.admin_elements_per_page'));

    if (!empty($posts)) {
        foreach ($posts as $k => $v) {
            $posts[$k]['object_data'] = fn_get_discussion_object_data($v['object_id'], $v['object_type'], DESCR_SL);
        }
    }

    Tygh::$app['view']->assign('posts', $posts);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('discussion_object_type', $_REQUEST['object_type']);
    Tygh::$app['view']->assign('discussion_object_types', $discussion_object_types);
}
