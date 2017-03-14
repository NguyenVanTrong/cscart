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

namespace Tygh\UpgradeCenter\Connectors\Ebay;

use Tygh\Addons\AXmlScheme;
use Tygh\Addons\SchemesManager;
use Tygh\Registry;
use Tygh\UpgradeCenter\Connectors\BaseAddonConnector;

/**
 * Class Connector
 * @package Tygh\UpgradeCenter\Connectors\Ebay
 */
class Connector extends BaseAddonConnector
{
    /** @inheritdoc */
    public function __construct()
    {
        parent::__construct();

        /** @var AXmlScheme $addon_scheme */
        $addon_scheme = SchemesManager::getScheme('ebay');

        $this->addon_id = 'ebay';
        $this->license_number = Registry::get(str_rot13('nqqbaf.ronl.ronl_yvprafr_ahzore'));
        $this->product_edition = 'EBAY';
        $this->product_name = $addon_scheme->getName();
        $this->product_version = $addon_scheme->getVersion();
        $this->product_build = '';
        $this->notification_key = 'upgrade_center:addon_ebay';
    }
}
