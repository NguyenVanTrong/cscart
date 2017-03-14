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

use ExTwigmo\Core\TwigmoSettings;

if ($mode == 'block_selection' and !empty($_REQUEST['grid_id'])) {
    // Disable some block types for twigmo location
    if (!fn_ex_twg_is_twigmo_grid($_REQUEST['grid_id'])) {
        return;
    }
    // Disable some types
    $view = fn_ex_twg_get_view_object();
    $block_types = $view->getTemplateVars('block_types');
    $allowed_types = TwigmoSettings::get('block_types');

    foreach ($block_types as $key => $block_type) {
        if (!in_array($key, $allowed_types)) {
            unset($block_types[$key]);
        }
    }
    $view->assign('block_types', $block_types);
}
