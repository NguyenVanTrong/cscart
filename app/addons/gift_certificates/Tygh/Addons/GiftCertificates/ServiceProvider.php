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


namespace Tygh\Addons\GiftCertificates;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\GiftCertificates\Documents\GiftCertificate\Type;

/**
 * Class ServiceProvider is intended to register services and components of the "gift_certificates" add-on to the application
 * container.
 *
 * @package Tygh\Addons\GiftCertificates
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['template.document.gift_certificate.type'] = function ($app) {
            return new Type($app['template.document.repository'], $app['db'], $app['template.renderer'], $app['template.variable_collection_factory']);
        };
    }
}