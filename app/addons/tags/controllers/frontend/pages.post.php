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

if ($mode == 'view' && Registry::get('addons.tags.tags_for_pages') == 'Y') {
    $page = Tygh::$app['view']->getTemplateVars('page');
    list($tags) = fn_get_tags(array(
        'object_type' => 'A',
        'object_id' => $page['page_id'],
        'status' => array('A')
    ));

    $page['tags'] = $tags;

    Tygh::$app['view']->assign('page', $page);
}
