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

if ($mode == 'view' && Registry::get('addons.tags.tags_for_products') == 'Y') {
    $product = Tygh::$app['view']->getTemplateVars('product');
    list($tags) = fn_get_tags(array(
        'object_type' => 'P',
        'object_id' => $product['product_id'],
        'status' => array('A')
    ));

    $product['tags'] = $tags;

    Tygh::$app['view']->assign('product', $product);
}
