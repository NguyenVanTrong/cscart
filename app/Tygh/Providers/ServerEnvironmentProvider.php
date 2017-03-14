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

namespace Tygh\Providers;

use Pimple\Container;
use Tygh\ServerEnvironment;

/**
 * Class ServerEnvironmentProvider is used to register server environment components at Application container.
 */
class ServerEnvironmentProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        // Environment component
        $app['server.env'] = function ($app) {

            $env = new ServerEnvironment(
                PHP_VERSION,
                PHP_SAPI,
                $app['server.env.ini_vars']
            );

            return $env;
        };

        $app['server.env.ini_vars'] = array();
    }
}
