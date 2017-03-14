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

use Tygh\Http;
use Tygh\SoftwareProductEnvironment;
use Tygh\Tools\Url;

class MarketplaceConnector extends BaseAddonConnector implements IConnector
{
    const DISPATCH_CHECK_FOR_UPGRADE = 'product_packages.get_upgrades';
    const DISPATCH_DOWNLOAD_UPGRADE_PACKAGE = 'product_packages.get_package';

    /**
     * @var SoftwareProductEnvironment
     */
    protected $product_environment;
    protected $env_lang_code;

    protected $addon_marketplace_id;

    protected $license_key;

    public function __construct(
        $marketplace_url,

        SoftwareProductEnvironment $product_environment,

        $environment_lang_code,
        $addon_id_name,
        $addon_name,
        $current_addon_version,
        $addon_marketplace_id,
        $license_key
    ) {
        $this->updates_server = ($marketplace_url instanceof Url) ? $marketplace_url : new Url($marketplace_url);

        $this->product_environment = $product_environment;
        $this->env_lang_code = $environment_lang_code;

        $this->addon_id = $addon_id_name;
        $this->product_name = $addon_name;
        $this->product_version = $current_addon_version;
        $this->addon_marketplace_id = $addon_marketplace_id;

        $this->license_key = $license_key;

        $this->notification_key = 'upgrade:marketplace:' . $addon_id_name;
    }

    /**
     * @inheritDoc
     */
    public function getConnectionData()
    {
        $connection_data = array(
            'method' => 'get',
            'url' => $this->generateCheckForUpgradeUrl()->build(),
            'data' => array(
                'lang' => $this->env_lang_code,
                'product_version' => $this->product_environment->getProductVersion(),
                'product_build' => $this->product_environment->getProductBuild(),
                'edition' => $this->product_environment->getProductEdition(),
                'product_id' => $this->addon_marketplace_id,
                'ver' => $this->product_version,
                'license_number' => $this->license_key
            ),
        );

        return $connection_data;
    }

    /**
     * @inheritDoc
     */
    public function downloadPackage($schema, $package_path)
    {
        $download_url = $this->generateDownloadUpgradePackageUrl();

        $download_url->setQueryParams(array_merge($download_url->getQueryParams(), array(
            'package_id' => $schema['package_id'],
            'product_id' => $this->addon_marketplace_id,
            'license_number' => $this->license_key
        )));

        $download_url = $download_url->build();


        $request_result = Http::get($download_url, array(), array(
            'write_to_file' => $package_path
        ));

        if (!$request_result || strlen($error = Http::getError())) {
            $download_result = array(false, __('text_uc_cant_download_package'));

            fn_rm($package_path);
        } else {
            $download_result = array(true, '');
        }

        return $download_result;
    }

    /**
     * @return Url
     */
    protected function generateCheckForUpgradeUrl()
    {
        return $this->generateMarketplaceUrl(self::DISPATCH_CHECK_FOR_UPGRADE);
    }

    /**
     * @return Url
     */
    protected function generateDownloadUpgradePackageUrl()
    {
        return $this->generateMarketplaceUrl(self::DISPATCH_DOWNLOAD_UPGRADE_PACKAGE);
    }

    /**
     * @param $dispatch
     *
     * @return Url
     */
    protected function generateMarketplaceUrl($dispatch)
    {
        $url = clone $this->updates_server;

        $query_params = $url->getQueryParams();
        $query_params['dispatch'] = $dispatch;
        $url->setQueryParams($query_params);
        $url->setPath('/');

        return $url;
    }
}
