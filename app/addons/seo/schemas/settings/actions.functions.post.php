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

use Tygh\Http;
use Tygh\Registry;

/**
 * Check if mod_rewrite is active and clean up templates cache
 */
function fn_settings_actions_addons_seo(&$new_value, $old_value)
{
    if ($new_value == 'A') {
        Http::get(Registry::get('config.http_location') . '/catalog.html?version');
        $headers = Http::getHeaders();

        if (strpos($headers, '200 OK') === false) {
            $new_value = 'D';
            fn_set_notification('W', __('warning'), __('warning_seo_urls_disabled'));
        }
    }

    fn_clear_cache();

    return true;
}

function fn_settings_actions_addons_seo_seo_product_type($new_value, $old_value)
{
    if (!empty($old_value) && $new_value != $old_value) {

        $redirect_only = false;
        $options = array('product_file', 'product_file_nohtml');
        if (in_array($new_value, $options) && in_array($old_value, $options)) {
            $redirect_only = true;
        }

        $options = array('product_category', 'product_category_nohtml');
        if (in_array($new_value, $options) && in_array($old_value, $options)) {
            $redirect_only = true;
        }

        fn_seo_settings_update('p', 'seo_product_type', $new_value, $redirect_only);
    }
}

function fn_settings_actions_addons_seo_seo_category_type($new_value, $old_value)
{
    if (!empty($old_value) && $new_value != $old_value) {

        $redirect_only = false;
        $options = array('category', 'file');
        if (in_array($new_value, $options) && in_array($old_value, $options)) {
            $redirect_only = true;
        }

        fn_seo_settings_update('c', 'seo_category_type', $new_value, $redirect_only);
    }
}

function fn_settings_actions_addons_seo_seo_page_type($new_value, $old_value)
{
    if (!empty($old_value) && $new_value != $old_value) {

        $redirect_only = false;
        $options = array('page', 'file');
        if (in_array($new_value, $options) && in_array($old_value, $options)) {
            $redirect_only = true;
        }

        fn_seo_settings_update('a', 'seo_page_type', $new_value, $redirect_only);
    }
}

function fn_seo_settings_update($type, $option, $new_value, $redirect_only)
{
    $old_value = Registry::get('addons.seo.' . $option);

    fn_iterate_through_seo_names(
        function ($seo_name) use ($option, $old_value, $new_value, $redirect_only) {
            // We shouldn't consider null value
            if (false === fn_check_seo_object_exists(
                $seo_name['object_id'], $seo_name['type'], $seo_name['company_id']
            )) {
                fn_delete_seo_name($seo_name['object_id'], $seo_name['type'], '', $seo_name['company_id']);

                return;
            }

            Registry::set('addons.seo.' . $option, $old_value);
            $url = fn_generate_seo_url_from_schema(array(
                'type' => $seo_name['type'],
                'object_id' => $seo_name['object_id'],
                'lang_code' => $seo_name['lang_code']
            ), false);

            fn_seo_update_redirect(array(
                'src' => $url,
                'type' => $seo_name['type'],
                'object_id' => $seo_name['object_id'],
                'company_id' => $seo_name['company_id'],
                'lang_code' => $seo_name['lang_code']
            ), 0, false);

            if (!$redirect_only) {
                Registry::set('addons.seo.' . $option, $new_value);
                fn_create_seo_name(
                    $seo_name['object_id'],
                    $seo_name['type'],
                    $seo_name['name'],
                    0,
                    '',
                    $seo_name['company_id'],
                    $seo_name['lang_code'],
                    true
                );
            }
        },
        db_quote("type = ?s ?p", $type, fn_get_seo_company_condition('?:seo_names.company_id', $type))
    );
}