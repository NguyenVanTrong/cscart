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
use Tygh\Addons\AXmlScheme;
use Tygh\Addons\SchemesManager;

/**
 * Base core addon upgrade connector class
 */
abstract class BaseAddonConnector extends BaseConnector
{
    /** @var string */
    protected $addon_id;

    /** @inheritdoc */
    public function onSuccessPackageInstall($content_schema, $information_schema)
    {
        parent::onSuccessPackageInstall($content_schema, $information_schema);

        if ($this->addon_id) {
            SchemesManager::clearInternalCache($this->addon_id);
            /** @var AXmlScheme $scheme */
            $scheme = SchemesManager::getScheme($this->addon_id);

            if ($scheme) {
                $version = $scheme->getVersion();

                if (!empty($version)) {
                    fn_update_addon_version($this->addon_id, $version);
                }
            }
        }
    }

    /** @inheritdoc */
    public function processServerResponse($response, $show_upgrade_notice)
    {
        $data = parent::processServerResponse($response, $show_upgrade_notice);

        if (!empty($data)) {
            $data['name'] = $this->product_name . ': ' . $data['name'];
        }

        return $data;
    }
}