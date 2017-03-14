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

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' and $mode == 'update'
    and $_REQUEST['addon'] == 'mobile_admin_app') {

    $locations = array(
        'twigmo' => 'twigmo.post',
        'index' => 'index.index'
    );

    $view = fn_ex_twg_get_view_object();

    $view->assign('default_layout_id', fn_ex_twg_get_default_layout_id());
    $view->assign('locations_info', fn_ex_twg_get_locations_info());

    $options = $view->getTemplateVars('options');
    if (isset($options['main'])) {
        unset($options['main']);
        $view->assign('options', $options);
    }
}
