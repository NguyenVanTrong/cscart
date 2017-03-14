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

namespace Tygh\UpgradeCenter\Connectors;

use Tygh\Registry;
use Tygh\Settings;

/**
 * Base core upgrade connector class
 */
abstract class BaseConnector implements IConnector
{
    /**
     * Upgrade server URL
     *
     * @var string $updates_server
     */
    protected $updates_server = '';

    /**
     * Upgrade center settings
     *
     * @var array $uc_settings
     */
    protected $uc_settings = array();

    /**
     * Product name
     *
     * @var string $product_name
     */
    protected $product_name;

    /**
     * Product version
     *
     * @var string $product_version
     */
    protected $product_version;

    /**
     * Product edition
     *
     * @var string $product_edition
     */
    protected $product_edition;

    /**
     * Product edition
     *
     * @var string $product_edition
     */
    protected $product_build;

    /**
     * Language
     *
     * @var string $language
     */
    protected $language;

    /**
     * License number
     *
     * @var string $license_number
     */
    protected $license_number;

    /**
     * Notification key
     *
     * @var string $notification_key
     */
    protected $notification_key = 'upgrade_center:core';

    /**
     * Prepares request data for request to Upgrade server (Check for the new upgrades)
     *
     * @return array Prepared request information
     */
    public function getConnectionData()
    {
        $request_data = array(
            'method' => 'get',
            'url' => $this->updates_server . '/index.php',
            'data' => array(
                'dispatch' => 'product_updates.get_available',
                'ver' => $this->product_version,
                'edition' => $this->product_edition,
                'lang' => $this->language,
                'product_build' => $this->product_build,
                'license_number' => $this->license_number
            ),
            'headers' => array(
                'Content-type: text/xml'
            )
        );

        return $request_data;
    }

    /**
     * Callback after package installed
     * @param $content_schema
     * @param $information_schema
     */
    public function onSuccessPackageInstall($content_schema, $information_schema)
    {
        fn_delete_notification($this->notification_key);
    }

    /**
     * Processes the response from the Upgrade server.
     *
     * @param  string $response            server response
     * @param  bool   $show_upgrade_notice internal flag, that allows/disallows Connector displays upgrade notice (A new version of [product] available)
     * @return array  Upgrade package information or empty array if upgrade is not available
     */
    public function processServerResponse($response, $show_upgrade_notice)
    {
        $parsed_data = array();
        $data = simplexml_load_string($response);

        if ($data && $data->packages->item) {
            $parsed_data = array(
                'file' => (string) $data->packages->item->file,
                'name' => (string) $data->packages->item->name,
                'description' => (string) $data->packages->item->description,
                'from_version' => (string) $data->packages->item->from_version,
                'to_version' => (string) $data->packages->item->to_version,
                'timestamp' => (int) $data->packages->item->timestamp,
                'size' => (int) $data->packages->item->size,
                'package_id' => (string) $data->packages->item['id'],
                'md5' => (string) $data->packages->item->file['md5'],
            );

            if ($show_upgrade_notice) {
                fn_set_notification('W', __('notice'), __('text_upgrade_available', array(
                    '[product]' => $this->product_name,
                    '[link]' => fn_url('upgrade_center.manage')
                )), 'S', $this->notification_key);
            }
        }

        return $parsed_data;
    }

    /**
     * Downloads upgrade package from the Upgrade server
     *
     * @param  array  $schema       Package schema
     * @param  string $package_path Path where the upgrade pack must be saved
     * @return bool   True if upgrade package was successfully downloaded, false otherwise
     */
    public function downloadPackage($schema, $package_path)
    {
        $data = fn_get_contents(Registry::get('config.resources.updates_server') . '/index.php?dispatch=product_updates.get_package&package_id=' . $schema['package_id'] . '&edition=' . $this->product_edition . '&license_number=' . $this->license_number);

        if (!empty($data)) {
            fn_put_contents($package_path, $data);

            if (md5_file($package_path) == $schema['md5']) {
                $result = array(true, '');
            } else {
                fn_rm($package_path);

                $result = array(false, __('text_uc_broken_package'));
            }
        } else {
            $result = array(false, __('text_uc_cant_download_package'));
        }

        return $result;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->updates_server = Registry::isExist('config.resources.updates_server') ? Registry::get('config.resources.updates_server') : Registry::get('config.updates_server');
        $this->uc_settings = Settings::instance()->getValues('Upgrade_center');
        $this->language = CART_LANGUAGE;
    }
}
