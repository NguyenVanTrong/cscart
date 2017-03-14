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
    $suffix = '';
    fn_trusted_vars('store_locations', 'store_location_data');

    if ($mode == 'update') {

        $store_location_id = fn_update_store_location($_REQUEST['store_location_data'], $_REQUEST['store_location_id'], DESCR_SL);

        if (empty($store_location_id)) {
            $suffix = ".manage";
        } else {
            $suffix = ".update?store_location_id=$store_location_id";
        }
    }

    if ($mode == 'delete') {
        if (!empty($_REQUEST['store_location_id'])) {
            fn_delete_store_location($_REQUEST['store_location_id']);
        }
        $suffix = '.manage';
    }

    return array (CONTROLLER_STATUS_OK, 'store_locator' . $suffix);
}

if ($mode == 'manage') {

    list($store_locations, $search) = fn_get_store_locations($_REQUEST, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);

    Tygh::$app['view']->assign('sl_settings', fn_get_store_locator_settings());
    Tygh::$app['view']->assign('store_locations', $store_locations);
    Tygh::$app['view']->assign('search', $search);

} elseif ($mode == 'add') {

    // [Page sections]
    Registry::set('navigation.tabs', array (
        'detailed' => array (
            'title' => __('general'),
            'js' => true
        ),
        'addons' => array (
            'title' => __('addons'),
            'js' => true
        )
    ));
    // [/Page sections]

    Tygh::$app['view']->assign('sl_settings', fn_get_store_locator_settings());

} elseif ($mode == 'update') {

    $store_location = fn_get_store_location($_REQUEST['store_location_id'], DESCR_SL);

    if (empty($store_location)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    Tygh::$app['view']->assign('store_location', $store_location);
    Tygh::$app['view']->assign('sl_settings', fn_get_store_locator_settings());

    // [Page sections]
    $tabs = array (
        'detailed' => array (
            'title' => __('general'),
            'js' => true
        ),
        'addons' => array (
            'title' => __('addons'),
            'js' => true
        )
    );

    Registry::set('navigation.tabs', $tabs);
    // [/Page sections]

}
