<?php

namespace Tygh;

/**
 * Class Tygh is an utility class for creation and convenient access for currently running Application class instance.
 *
 * @package Tygh
 */
class Tygh {

    /**
     * @var Application
     */
    public static $app;

    /**
     * Creates application object and registers it at static variable.
     *
     * @param string $class Application class name
     *
     * @return Application
     */
    public static function createApplication($class = '\Tygh\Application')
    {
        self::$app = new $class();

        Registry::setAppInstance(self::$app);

        return self::$app;
    }
}