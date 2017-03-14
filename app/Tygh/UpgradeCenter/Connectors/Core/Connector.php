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

namespace Tygh\UpgradeCenter\Connectors\Core;

use Tygh\Registry;
use Tygh\UpgradeCenter\Connectors\BaseConnector;

/**
 * Core upgrade connector
 */
class Connector extends BaseConnector
{
    /** @inheritdoc */
    public function __construct()
    {
        parent::__construct();

        $this->product_name = PRODUCT_NAME;
        $this->product_version = PRODUCT_VERSION;
        $this->product_build = PRODUCT_BUILD;
        $this->product_edition = PRODUCT_EDITION;
        $this->license_number = $this->uc_settings['license_number'];
    }
}
