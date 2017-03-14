<?php
namespace Tygh;

use Pimple\Container;


/**
 * Application class provides methods for handling current request and stores common runtime state.
 * It is also an IoC container.
 *
 * @package Tygh
 */
class Application extends Container
{
    public function __construct()
    {
        parent::__construct();

        $this->registerCoreServices();
    }

    /**
     * Registers core services at IoC container.
     *
     * @return void
     */
    protected function registerCoreServices()
    {
        $this['app'] = $this;
    }
}