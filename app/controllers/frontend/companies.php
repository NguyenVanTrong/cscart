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

    if ($mode == 'apply_for_vendor') {

        if (Registry::get('settings.Vendors.apply_for_vendor') != 'Y') {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        $data = $_REQUEST['company_data'];

        $data['timestamp'] = TIME;
        $data['status'] = 'N';
        $data['request_user_id'] = !empty($auth['user_id']) ? $auth['user_id'] : 0;

        $account_data = array();
        $account_data['fields'] = isset($_REQUEST['user_data']['fields']) ? $_REQUEST['user_data']['fields'] : '';
        $account_data['admin_firstname'] = isset($_REQUEST['company_data']['admin_firstname']) ? $_REQUEST['company_data']['admin_firstname'] : '';
        $account_data['admin_lastname'] = isset($_REQUEST['company_data']['admin_lastname']) ? $_REQUEST['company_data']['admin_lastname'] : '';
        $data['request_account_data'] = serialize($account_data);

        if (empty($data['request_user_id'])) {
            $login_condition = empty($data['request_account_name']) ? '' : db_quote(" OR user_login = ?s", $data['request_account_name']);
            $user_account_exists = db_get_field("SELECT user_id FROM ?:users WHERE email = ?s ?p", $data['email'], $login_condition);

            if ($user_account_exists) {
                fn_save_post_data('user_data', 'company_data');
                fn_set_notification('E', __('error'), __('error_user_exists'));

                return array(CONTROLLER_STATUS_REDIRECT, 'companies.apply_for_vendor');
            }
        }

        $company_id = fn_update_company($data);

        $data = array_merge($data, fn_get_company_data($company_id));

        if (!$company_id) {
            fn_save_post_data('user_data', 'company_data');
            fn_set_notification('E', __('error'), __('text_error_adding_request'));

            return array(CONTROLLER_STATUS_REDIRECT, 'companies.apply_for_vendor');
        }

        $msg = Tygh::$app['view']->fetch('views/companies/components/apply_for_vendor.tpl');
        fn_set_notification('I', __('information'), $msg);

        // Notify user department on the new vendor application
        /** @var \Tygh\Mailer\Mailer $mailer */
        $mailer = Tygh::$app['mailer'];

        $mailer->send(array(
            'to' => 'default_company_users_department',
            'from' => 'default_company_users_department',
            'data' => array(
                'company_id' => $company_id,
                'company' => $data,
                'company_update_url' => fn_url('companies.update?company_id=' . $company_id, 'A', 'http')
            ),
            'template_code' => 'apply_for_vendor_notification',
            'tpl' => 'companies/apply_for_vendor_notification.tpl', // this parameter is obsolete and is used for back compatibility
        ), 'A', Registry::get('settings.Appearance.backend_default_language'));

        $return_url = !empty(Tygh::$app['session']['apply_for_vendor']['return_url']) ? Tygh::$app['session']['apply_for_vendor']['return_url'] : fn_url('');
        unset(Tygh::$app['session']['apply_for_vendor']['return_url']);

        return array(CONTROLLER_STATUS_REDIRECT, $return_url);
    }
}

if (fn_allowed_for('ULTIMATE')) {
    if ($mode == 'entry_page') {
        $countries = array();

        $companies_countries = db_get_array('SELECT storefront, countries_list FROM ?:companies');
        foreach ($companies_countries as $data) {
            if (empty($data['countries_list'])) {
                continue;
            }
            $_countries = explode(',', $data['countries_list']);
            foreach ($_countries as $code) {
                $countries[$code] = strpos($data['storefront'], 'http://') === false ? 'http://' . $data['storefront'] : $data['storefront'];
            }
        }

        $country_descriptions = fn_get_countries_name(array_keys($countries));

        Tygh::$app['session']['entry_page'] = true;

        Tygh::$app['view']->assign('countries', $countries);
        Tygh::$app['view']->assign('country_descriptions', $country_descriptions);
        Tygh::$app['view']->display('views/companies/components/entry_page.tpl');

        exit;
    }
}

if ($mode == 'view') {

    $company_data = !empty($_REQUEST['company_id']) ? fn_get_company_data($_REQUEST['company_id']) : array();

    if (empty($company_data) || empty($company_data['status']) || !empty($company_data['status']) && $company_data['status'] != 'A') {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    fn_add_breadcrumb(__('all_vendors'), 'companies.catalog');
    fn_add_breadcrumb($company_data['company']);

    $company_data['total_products'] = count(db_get_fields(fn_get_products(array(
        'get_query' => true,
        'company_id' => $_REQUEST['company_id']
    ))));

    $company_data['logos'] = fn_get_logos($company_data['company_id']);

    Registry::set('navigation.tabs', array(
        'description' => array(
            'title' => __('description'),
            'js' => true
        )
    ));

    $params = array(
        'company_id' => $_REQUEST['company_id'],
    );

    Tygh::$app['view']->assign('company_data', $company_data);

} elseif ($mode == 'catalog') {

    fn_add_breadcrumb(__('all_vendors'));

    $params = $_REQUEST;
    $params['status'] = 'A';
    $params['get_description'] = 'Y';

    $vendors_per_page = Registry::get('settings.Vendors.vendors_per_page');
    list($companies, $search) = fn_get_companies($params, $auth, $vendors_per_page);

    foreach ($companies as &$company) {
        $company['logos'] = fn_get_logos($company['company_id']);
    }

    Tygh::$app['view']->assign('companies', $companies);
    Tygh::$app['view']->assign('search', $search);

} elseif ($mode == 'apply_for_vendor') {

    if (Registry::get('settings.Vendors.apply_for_vendor') != 'Y') {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $restored_company_data = fn_restore_post_data('company_data');
    if ($restored_company_data) {
        Tygh::$app['view']->assign('company_data', $restored_company_data);
    }

    $restored_user_data = fn_restore_post_data('user_data');
    if ($restored_user_data) {
        Tygh::$app['view']->assign('user_data', $restored_user_data);
    }

    $profile_fields = fn_get_profile_fields('A', array(), CART_LANGUAGE, array('get_custom' => true, 'get_profile_required' => true));

    Tygh::$app['view']->assign('profile_fields', $profile_fields);
    Tygh::$app['view']->assign('countries', fn_get_simple_countries(true, CART_LANGUAGE));
    Tygh::$app['view']->assign('states', fn_get_all_states());

    fn_add_breadcrumb(__('apply_for_vendor_account'));

    Tygh::$app['session']['apply_for_vendor']['return_url'] = !empty($_REQUEST['return_previous_url']) ? $_REQUEST['return_previous_url'] : fn_url('');

} elseif ($mode == 'products') {
    $company_data = !empty($_REQUEST['company_id']) ? fn_get_company_data($_REQUEST['company_id']) : array();

    if (empty($company_data)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $company_id = $company_data['company_id'];

    fn_add_breadcrumb(__('all_vendors'), 'companies.catalog');

    $params = $_REQUEST;

    $params['company_id'] = $company_id;
    $params['extend'] = array('description');

    if (!empty($_REQUEST['category_id'])) {
        fn_add_breadcrumb($company_data['company'], 'companies.products?company_id=' . $company_id);

        $category_id = $_REQUEST['category_id'];
        // Get full data for current category
        $category_data = fn_get_category_data($category_id);

        if (!empty($category_data)) {
            $params['cid'] = $category_id;
            if (Registry::get('settings.General.show_products_from_subcategories') == 'Y') {
                $params['subcats'] = 'Y';
            }

            // [Breadcrumbs]
            $parent_ids = explode('/', $category_data['id_path']);
            array_pop($parent_ids);

            if (!empty($parent_ids)) {
                $cats = fn_get_category_name($parent_ids);
                foreach ($parent_ids as $c_id) {
                    fn_add_breadcrumb($cats[$c_id], "companies.products?category_id=$c_id&company_id=$company_id");
                }
            }
            fn_add_breadcrumb($category_data['category']);
        }

        // Get subcategories list for current category
        Tygh::$app['view']->assign('subcategories', fn_get_subcategories(
            $category_id, array('company_ids' => $company_data['company_id'])
        ));
        Tygh::$app['view']->assign('category_data', $category_data);
        Tygh::$app['view']->assign('reset_url', fn_url('companies.products?category_id=' . $category_id . '&company_id=' . $company_id));

    } else {
        if (!empty($_REQUEST['q'])) {
            fn_add_breadcrumb($company_data['company'], 'companies.products?company_id=' . $company_id);
            fn_add_breadcrumb(__('search'));
        } else {
            fn_add_breadcrumb($company_data['company']);
        }
        Tygh::$app['view']->assign('reset_url', fn_url('companies.products?company_id=' . $company_id));
    }

    if ($items_per_page = fn_change_session_param(Tygh::$app['session']['companies_params'], $_REQUEST, 'items_per_page')) {
        $params['items_per_page'] = $items_per_page;
    }
    if ($sort_by = fn_change_session_param(Tygh::$app['session']['companies_params'], $_REQUEST, 'sort_by')) {
        $params['sort_by'] = $sort_by;
    }
    if ($sort_order = fn_change_session_param(Tygh::$app['session']['companies_params'], $_REQUEST, 'sort_order')) {
        $params['sort_order'] = $sort_order;
    }

    list($products, $search) = fn_get_products($params, Registry::get('settings.Appearance.products_per_page'));

    if (defined('AJAX_REQUEST') && (!empty($params['features_hash']) && !$products)) {
        fn_filters_not_found_notification();
        exit;
    }

    fn_gather_additional_products_data($products, array('get_icon' => true, 'get_detailed' => true, 'get_additional' => true, 'get_options'=> true));

    if (!empty($products)) {
        Tygh::$app['session']['continue_url'] = Registry::get('config.current_url');
    }

    $selected_layout = fn_get_products_layout($params);

    Tygh::$app['view']->assign('products', $products);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('selected_layout', $selected_layout);
    Tygh::$app['view']->assign('company_id', $company_data['company_id']);

    Registry::set('runtime.vendor_id', $company_id);
}
