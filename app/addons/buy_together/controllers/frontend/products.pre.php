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

/**
 * @var string $mode
 * @var array $auth
 */
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'options') {
    if (!empty($_REQUEST['appearance']['bt_chain'])) {
        $products = array();

        foreach ($_REQUEST['product_data'] as $id => $options) {
            if (isset($_REQUEST['product_data'][$id]['product_options'])) {
                $products[$id]['selected_options'] = $_REQUEST['product_data'][$id]['product_options'];
            }

            if (isset($_REQUEST['changed_option'][$id])) {
                $products[$id]['changed_option'] = $_REQUEST['changed_option'][$id];
            }

            unset($products[$id]['selected_options']['AOC']);
        }

        $params = array(
            'chain_id' => $_REQUEST['appearance']['bt_chain'],
            'status' => 'A',
            'full_info' => true,
            'date' => true,
            'selected_options' => $products,
        );

        $chains = fn_buy_together_get_chains($params, $auth);

        if (!empty($chains)) {
            Tygh::$app['view']->assign('chains', $chains);
            Tygh::$app['view']->display('addons/buy_together/blocks/product_tabs/buy_together.tpl');

            exit();
        }
    }

}
