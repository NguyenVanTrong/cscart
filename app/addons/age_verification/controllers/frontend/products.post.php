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

if ($mode == 'view' && !empty($_REQUEST['product_id'])) {
    $current_url = urlencode(Registry::get('config.current_url'));
    $url = 'age_verification.verify' . (!empty($current_url) ? ('?return_url=' . $current_url) : '');

    $data = db_get_row("SELECT product_id, age_verification, age_limit FROM ?:products WHERE product_id = ?i", $_REQUEST['product_id']);
    if ($data['age_verification'] == 'Y') {
        $object = 'product_descriptions';
        $object_id = $_REQUEST['product_id'];

        $age = !empty(Tygh::$app['session']['auth']['age']) ? Tygh::$app['session']['auth']['age'] : 0;

        if (!$age) {
            $type = 'form';
        } else {
            if ($age < $data['age_limit']) {
                $type = 'deny';
            }
        }
    }

    if (!isset($type)) {
        $data = db_get_array("SELECT * FROM ?:products_categories WHERE product_id = ?i", $data['product_id']);
        foreach ($data as $record) {
            list ($type, $object_id) = fn_age_verification_category_check($record['category_id']);
            $object = 'category_descriptions';
            if ($type === false) {
                break;
            }
        }
    }

    if (isset($type) && $type !== false) {
        $url .= '&object=' . $object . '&object_id=' . $object_id . '&type=' . $type;
        return array (CONTROLLER_STATUS_REDIRECT, $url);
    }
}
