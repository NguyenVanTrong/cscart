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


namespace Tygh\Addons\Suppliers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\Suppliers\Documents\SupplierOrder\Type;

/**
 * Class ServiceProvider is intended to register services and components of the "Suppliers" add-on to the application
 * container.
 *
 * @package Tygh\Addons\Suppliers
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['template.document.supplier_order.type'] = function ($app) {
            return new Type($app['template.document.repository'], $app['db'], $app['template.renderer'], $app['template.variable_collection_factory']);
        };
    }
}