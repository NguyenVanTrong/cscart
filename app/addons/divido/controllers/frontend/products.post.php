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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'view' || $mode == 'quick_view') {

    if ($product = Tygh::$app['view']->getTemplateVars('product')) {
        if (empty($product['divido_data'])) {
            $product['divido_data'] = array();
        }
        $product['divido_data']['show_calculator'] = !empty($product['divido_data']['price']) && !empty($product['divido_data']['api_key']);

        Tygh::$app['view']->assign('product', $product);
    }
}
