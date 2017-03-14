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

    return;
}

if ($mode == 'view') {

    $page_data = Tygh::$app['view']->getTemplateVars('page');

    if ($page_data['page_type'] == PAGE_TYPE_BLOG) {

        list($subpages, $search) = fn_get_pages(array(
            'parent_id' => $page_data['page_id'],
            'page' => !empty($_REQUEST['page']) ? $_REQUEST['page'] : 0,
            'page_type' => PAGE_TYPE_BLOG,
            'get_image' => true,
            'status' => 'A',
            'sort_by' => 'timestamp',
            'sort_order' => 'desc'
        ), Registry::get('settings.Appearance.elements_per_page'));

        Tygh::$app['view']->assign('subpages', $subpages);
        Tygh::$app['view']->assign('search', $search);
    }
}
