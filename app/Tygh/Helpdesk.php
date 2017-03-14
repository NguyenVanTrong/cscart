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

/* WARNING: DO NOT MODIFY THIS FILE TO AVOID PROBLEMS WITH THE CART FUNCTIONALITY */

namespace Tygh;

use Tygh\Http;
/**
 *
 * Helpdesk connector class
 *
 */
class Helpdesk
{
    /**
     * Returns current license status
     * @param  string $license_key
     * @param  string $host_name   If host_name was specified, license will be checked
     * @return bool
     */
    public static function getLicenseInformation($license_number = '', $extra_fields = array())
    {
        if (empty($license_number)) {
            $uc_settings = Settings::instance()->getValues('Upgrade_center');
            $license_number = $uc_settings['license_number'];
        }

        $store_mode = fn_get_storage_data('store_mode');

        if (empty($license_number) && $store_mode != 'trial') {
            return 'LICENSE_IS_INVALID';
        }

        $store_ip = fn_get_ip();
        $store_ip = $store_ip['host'];

        $request = array(
            'license_number' => $license_number,
            'ver' => PRODUCT_VERSION,
            'product_status' => PRODUCT_STATUS,
            'product_build' => strtoupper(PRODUCT_BUILD),
            'edition' => isset($extra_fields['edition']) ? $extra_fields['edition'] : PRODUCT_EDITION,
            'lang' => strtoupper(CART_LANGUAGE),
            'store_uri' => fn_url('', 'C', 'http'),
            'secure_store_uri' => fn_url('', 'C', 'https'),
            'https_enabled' => (Registry::get('settings.Security.secure_storefront') != 'none' || Registry::get('settings.Security.secure_admin') == 'Y') ? 'Y' : 'N',
            'admin_uri' => str_replace(fn_get_index_script('A'), '',fn_url('', 'A', 'http')),
            'store_ip' => $store_ip,
            'store_mode' => strtoupper(isset($extra_fields['store_mode']) ? $extra_fields['store_mode'] : $store_mode)
        );

        $request = array(
            'Request@action=check_license@api=3' => array_merge($extra_fields, $request),
        );

        $request = '<?xml version="1.0" encoding="UTF-8"?>' . fn_array_to_xml($request);

        Registry::set('log_cut', Registry::ifGet('config.demo_mode', false));

        $data = Http::get(Registry::get('config.resources.updates_server') . '/index.php?dispatch=product_updates.check_available', array('request' => $request), array(
            'timeout' => 10
        ));

        if (empty($data)) {
            $data = fn_get_contents(Registry::get('config.resources.updates_server') . '/index.php?dispatch=product_updates.check_available&request=' . urlencode($request));
        }

        if (empty($license_number)) {
            return 'LICENSE_IS_INVALID';
        }

        return $data;
    }

    /**
     * Set/Get token auth key
     * @param  string $generate If generate value is equal to "true", new token will be generated
     * @return string token value
     */
    public static function token($generate = false)
    {
        if ($generate) {
            $token = fn_crc32(microtime());
            fn_set_storage_data('hd_request_code', $token);
        } else {
            $token = fn_get_storage_data('hd_request_code');
        }

        return $token;
    }

    /**
     * Get store auth key
     *
     * @return string store key
     */
    public static function getStoreKey()
    {
        $key = Registry::get('settings.store_key');
        $host_path = Registry::get('config.http_host') . Registry::get('config.http_path');

        if (!empty($key)) {
            list($token, $host) = explode(';', $key);
            if ($host != $host_path) {
                unset($key);
            }
        }

        if (empty($key)) {
            // Generate new value
            $key = fn_crc32(microtime());
            $key .= ';' . $host_path;
            Settings::instance()->updateValue('store_key', $key);
        }

        return $key;
    }

    public static function auth()
    {
        $_SESSION['last_status'] = 'INIT';

        self::initHelpdeskRequest();

        return true;
    }

    public static function initHelpdeskRequest($area = AREA)
    {
        if ($area != 'C') {
            $protocol = defined('HTTPS') ? 'https' : 'http';

            $_SESSION['stats'][] = '<img src="' . fn_url('helpdesk_connector.auth', 'A', $protocol) . '" alt="" style="display:none" />';
        }
    }

    /**
     * Parse license information
     *
     * @param  string    $data             Result from [self::getLicenseInformation]
     * @param  array     $auth
     * @param  bool|true $process_messages
     * @return array     Return string $license, string $updates, array $messages, array $params
     */
    public static function parseLicenseInformation($data, $auth, $process_messages = true)
    {
        $updates = $messages = $license = '';
        $params = array();

        if (!empty($data)) {
            // Check if we can parse server response
            if (strpos($data, '<?xml') !== false) {
                $xml = simplexml_load_string($data);
                $updates = (string) $xml->Updates;
                $messages = $xml->Messages;
                $license = (string) $xml->License;

                if (isset($xml->TrialExpiryTime)) {
                    $params['trial_expiry_time'] = (int) $xml->TrialExpiryTime;
                }

                if (isset($xml->TrialLeftTime)) {
                    $params['trial_left_time'] = (int) $xml->TrialLeftTime;
                }

                if (isset($xml->AllowedNumberOfStores)) {
                    fn_set_storage_data('allowed_number_of_stores', (int) $xml->AllowedNumberOfStores);
                } else {
                    fn_set_storage_data('allowed_number_of_stores', null);
                }
            } else {
                $license = $data;
            }
        }

        if (!empty($auth)) {
            if (Registry::get('settings.General.auto_check_updates') == 'Y' && fn_check_user_access($auth['user_id'], 'upgrade_store')) {
                // If upgrades are available
                if ($updates == 'AVAILABLE') {
                    fn_set_notification('W', __('notice'), __('text_upgrade_available', array(
                        '[product]' => PRODUCT_NAME,
                        '[link]' => fn_url('upgrade_center.manage')
                    )), 'S', 'upgrade_center:core');
                }
            }

            if (!empty($data)) {
                $_SESSION['last_status'] = $license;
            }
        }

        $messages = self::processMessages($messages, $process_messages, $license);

        return array($license, $updates, $messages, $params);
    }

    public static function processMessages($messages, $process_messages = true, $license_status = '')
    {
        $messages_queue = array();

        if (!empty($messages)) {
            if ($process_messages) {
                $messages_queue = fn_get_storage_data('hd_messages');
            }

            if (empty($messages_queue)) {
                $messages_queue = array();
            } else {
                $messages_queue = unserialize($messages_queue);
            }

            $new_messages = array();

            foreach ($messages->Message as $message) {
                $message_id = empty($message->Id) ? intval(fn_crc32(microtime()) / 2) : (string) $message->Id;
                $message = array(
                    'type' => empty($message->Type) ? 'W' : (string) $message->Type,
                    'title' => empty($message->Title) ? __('notice') : (string) $message->Title,
                    'text' => (string) $message->Text,
                    'state' => empty($message->State) ? null : (string) $message->State,
                );

                $new_messages[$message_id] = $message;
            }

            // check new messages for 'special' messages
            if (!empty($license_status)) {

                $special_errors = fn_get_schema('settings', 'licensing');
                foreach ($special_errors as $error_id => $type) {
                    if (isset($new_messages[$error_id])) {
                        $new_messages[$error_id] = array(
                            'type' => 'E',
                            'title' => __('error'),
                            'text' => $type == 'local' ? __('licensing.' . $error_id) : $new_messages[$error_id]['text']
                        );
                    }
                }

                if (!$new_messages) {
                    switch ($license_status) {
                        case 'PENDING':
                        case 'SUSPENDED':
                        case 'DISABLED':
                            $new_messages['license_error_license_is_disabled'] = array(
                                'type' => 'E',
                                'title' => __('error'),
                                'text' => __('licensing.license_error_license_is_disabled')
                            );
                            break;
                        case 'LICENSE_IS_INVALID':
                            $new_messages['license_error_license_is_invalid'] = array(
                                'type' => 'E',
                                'title' => __('error'),
                                'text' => __('licensing.license_error_license_is_invalid')
                            );
                            break;
                    }
                }
            }

            $messages_queue = array_merge($messages_queue, $new_messages);

            if ($process_messages) {
                fn_set_storage_data('hd_messages', serialize($messages_queue));
            }
        }

        return $messages_queue;
    }

    public static function registerLicense($license_data)
    {
        $request = array(
            'Request@action=registerLicense@api=2' => array(
                'product_type' => PRODUCT_EDITION,
                'domain' => Registry::get('config.http_host'),
                'first_name' => $license_data['first_name'],
                'last_name' => $license_data['last_name'],
                'email' => $license_data['email'],
            ),
        );

        $request = '<?xml version="1.0" encoding="UTF-8"?>' . fn_array_to_xml($request);

        $data = Http::get(Registry::get('config.resources.updates_server') . '/index.php?dispatch=licenses_remote.add', array('request' => $request), array(
            'timeout' => 10
        ));

        if (empty($data)) {
            $data = fn_get_contents(Registry::get('config.resources.updates_server') . '/index.php?dispatch=licenses_remote.create&request=' . urlencode($request));
        }

        $result = $messages = $license = '';

        if (!empty($data)) {
            // Check if we can parse server response
            if (strpos($data, '<?xml') !== false) {
                $xml = simplexml_load_string($data);
                $result = (string) $xml->Result;
                $messages = $xml->Messages;
                $license = (array) $xml->License;
            }
        }

        self::processMessages($messages, true, $license);

        return array($result, $license, $messages);
    }

    public static function checkStoreImportAvailability($license_number, $version, $edition = PRODUCT_EDITION)
    {
        $request = array(
            'dispatch' => 'product_updates.check_storeimport_available',
            'license_key' => $license_number,
            'ver' => $version,
            'edition' => $edition,
        );

        $data = Http::get(Registry::get('config.resources.updates_server'), $request, array(
            'timeout' => 10
        ));

        if (empty($data)) {
            $data = fn_get_contents(Registry::get('config.resources.updates_server') . '/index.php?' . http_build_query($request));
        }

        $result = false;

        if (!empty($data)) {
            // Check if we can parse server response
            if (strpos($data, '<?xml') !== false) {
                $xml = simplexml_load_string($data);
                $result = ((string) $xml == 'Y') ? true : false;
            }
        }

        return $result;
    }

    /**
     * Masques license number when the demo mode is enabled
     *
     * @param string $license_number License number
     * @param bool   $is_demo_mode   True if demo mode enabled
     *
     * @return string Spoofed (if necessary) license number
     */
    public static function masqueLicenseNumber($license_number, $is_demo_mode = false)
    {
        if ($license_number && $is_demo_mode) {
            $license_number = preg_replace('/[^-]/', 'X', $license_number);
        }

        return $license_number;
    }

    /**
     * Checks store mode.
     *
     * @param string $license_number License number
     * @param array  $auth           Auth data
     * @param array  $extra          Extra data to include into license check
     *
     * @return array License status, messages and store mode
     */
    public static function getStoreMode($license_number, $auth, $extra = array())
    {
        $license_status = 'LICENSE_IS_INVALID';
        $store_mode = '';
        $messages = array();

        if (fn_allowed_for('MULTIVENDOR')) {
            $store_modes_list = array('full');
        } else {
            $store_modes_list = array('', 'ultimate');
        }

        foreach ($store_modes_list as $store_mode) {
            $extra['store_mode'] = $store_mode;
            $data = Helpdesk::getLicenseInformation($license_number, $extra);
            list($license_status, $updates, $messages) = Helpdesk::parseLicenseInformation($data, $auth, false);
            if ($license_status == 'ACTIVE') {
                break;
            }
        }

        return array($license_status, $messages, $store_mode);
    }

    /**
     * Checks if companies limitations have been reached.
     *
     * @return bool True if there are too many companies
     */
    public static function isCompaniesLimitReached()
    {
        if (fn_allowed_for('ULTIMATE')) {
            if ($storefronts_limit = fn_get_storage_data('allowed_number_of_stores')) {
                return count(fn_get_all_companies_ids()) >= $storefronts_limit;
            }
        }

        return false;
    }
}
