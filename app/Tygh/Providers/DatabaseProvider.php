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
use Tygh\Exceptions\DatabaseException;
use Tygh\Database\Connection;
use Tygh\Registry;

/**
 * Class DatabaseProvider is used to register database components at Application container.
 */
class DatabaseProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        // Database component
        $app['db'] = function ($app) {
            $database = new Connection($app['db.driver']);

            $connected = $database->connect(
                Registry::get('config.db_user'),
                Registry::get('config.db_password'),
                Registry::get('config.db_host'),
                Registry::get('config.db_name'),
                array(
                    'table_prefix' => Registry::get('config.table_prefix')
                )
            );

            if ($connected) {
                Registry::set('runtime.database.skip_errors', false);
            } else {
                throw new DatabaseException('Cannot connect to the database server');
            }

            return $database;
        };

        // Database driver instance
        $app['db.driver'] = function ($app) {
            return new $app['db.driver.class'];
        };

        $app['db.driver.class'] = function ($app) {
            $driver_class = Registry::ifGet('config.database_backend', 'mysqli');
            $driver_class = '\\Tygh\\Backend\\Database\\' . ucfirst($driver_class);

            return $driver_class;
        };
    }
}
