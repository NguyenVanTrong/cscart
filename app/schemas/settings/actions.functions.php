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

use Tygh\Helpdesk;
use Tygh\Http;
use Tygh\Registry;
use Tygh\Settings;

/**
 * Check if secure connection is available
 */
function fn_settings_actions_security_secure_storefront(&$new_value, $old_value)
{
    if ($new_value !== 'none') {
        $company_id = fn_get_runtime_company_id();

        if (!fn_allowed_for('ULTIMATE') || (fn_allowed_for('ULTIMATE') && $company_id)) {

            $suffix = '';
            if (fn_allowed_for('ULTIMATE')) {
                $suffix = '&company_id=' . $company_id;
            }

            $storefront_url = fn_url('index.index?check_https=Y' . $suffix, 'C', 'https');

            $content = Http::get($storefront_url);
            if (empty($content) || $content != 'OK') {
                // Disable https
                Settings::instance()->updateValue('secure_storefront', 'none', 'Security');
                $new_value = 'none';

                $error = Http::getErrorFields();
                $error_warning = __('warning_https_is_disabled', array(
                    '[href]' => Registry::get('config.resources.kb_https_failed_url'
                    )));

                $error_warning .= fn_settings_actions_build_detailed_error_message($error);
                fn_set_notification('W', __('warning'), $error_warning);
            }
        }
    }
}

/**
 * Check if secure connection is available
 */
function fn_settings_actions_security_secure_admin(&$new_value, $old_value)
{
    if ($new_value !== 'N') {
        $suffix = '';
        if (fn_allowed_for('ULTIMATE')) {
            $suffix = '&company_id=' . Registry::get('runtime.company_id');
        }

        $admin_url = fn_url('index.index?check_https=Y' . $suffix, 'A', 'https');

        $content = Http::get($admin_url);

        if (empty($content) || $content != 'OK') {
            // Disable https
            Settings::instance()->updateValue('secure_admin', 'N', 'Security');
            $new_value = 'N';

            $error = Http::getErrorFields();
            $error_warning = __('warning_https_is_disabled', array(
                    '[href]' => Registry::get('config.resources.kb_https_failed_url'
                    )));

            $error_warning .= fn_settings_actions_build_detailed_error_message($error);
            fn_set_notification('W', __('warning'), $error_warning);
        }
    }
}

/**
 * Alter order initial ID
 */
function fn_settings_actions_general_order_start_id(&$new_value, $old_value)
{
    $new_value = intval($new_value);
    if ($new_value > 0) {

        if ($new_value <= MAX_INITIAL_ORDER_ID) {
            db_query("ALTER TABLE ?:orders AUTO_INCREMENT = ?i", $new_value);

            return true;
        }
    }

    $new_value = $old_value;
    fn_set_notification('W', __('warning'), __('wrong_number_initial_order_id', array('[max_initial_order_id]' => MAX_INITIAL_ORDER_ID)));

    return false;
}

/**
 * Save empty value if has no checked check boxes
 */
function fn_settings_actions_general_search_objects(&$new_value, $old_value)
{
    if ($new_value == 'N') {
        $new_value = '';
    }
}

function fn_settings_actions_upgrade_center_license_number(&$new_value, &$old_value)
{
    if (empty($new_value)) {
        $new_value = $old_value;

        fn_set_notification('E', __('error'), __('license_number_cannot_be_empty'));

        return false;
    }

    $old_mode = fn_get_storage_data('store_mode');

    list($license_status, $messages, $new_mode) = Helpdesk::getStoreMode($new_value, Tygh::$app['session']['auth']);
    
    $messages_queue = fn_get_storage_data('hd_messages');
    if (empty($messages_queue)) {
        $messages_queue = array();
    }
    $messages_queue = array_merge($messages_queue, $messages);
    fn_set_storage_data('hd_messages', serialize($messages_queue));

    if ($license_status == 'ACTIVE' && $old_mode != $new_mode) {
        fn_set_storage_data('store_mode', $new_mode, true);
        Tygh::$app['session']['mode_recheck'] = true;
    } else {
        if ($license_status != 'ACTIVE') {
            $new_value = $old_value;
        }
    }
}

function fn_settings_actions_appearance_backend_default_language(&$new_value, &$old_value)
{
    if (fn_allowed_for('ULTIMATE')) {
        db_query("UPDATE ?:companies SET lang_code = ?s", $new_value);
    }
}

if (fn_allowed_for('ULTIMATE')) {
    function fn_settings_actions_stores_share_users(&$new_value, $old_value)
    {
        $emails = fn_get_double_user_emails();
        if (!empty($emails)) {
            fn_delete_notification('changes_saved');
            fn_set_notification('E', __('error'), __('ult_share_users_setting_disabled'));
            $new_value = $old_value;
        }
    }
}

function fn_settings_actions_appearance_notice_displaying_time(&$new_value, $old_value)
{
    $new_value = fn_convert_to_numeric($new_value);
}

function fn_settings_actions_build_detailed_error_message($error) {

    $detailed_message = "";

    if (!empty($error['error_number'])) {
        $transport_prefix = __('http_transport_error_prefix_' . $error['transport']);

        $detailed_message .= "<br/><strong>{$transport_prefix} {$error['error_number']}</strong>";

        if ($error['transport'] == 'curl') {
            $error_description_paragraph = __('curl_error_code_reference_link',
                array(
                    '[href]' => Registry::get('config.resources.curl_error_interpretation'
                    )));
            $detailed_message .= "<br/>" . $error_description_paragraph;

        }
    }
    return $detailed_message;
}
