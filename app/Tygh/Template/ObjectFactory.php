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

namespace Tygh\Template;


use Pimple\Container;
use Tygh\Exceptions\ClassNotFoundException;
use Tygh\Exceptions\InputException;

/**
 * The factory class that implements the logic of object creation based on the object schema.
 *
 * @package Tygh\Template
 */
class ObjectFactory
{
    /** @var Container  */
    protected $container;

    /**
     * ObjectFactory constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Create object by schema.
     *
     * @param string    $class      Class name.
     * @param array     $arguments  List of constructor arguments.
     *                              The argument can be used as placeholder.
     *                              Use "#" for argument from params, use "@" for argument from container.
     *
     * @param array     $params     List of variables available for instantiate object.
     *
     * @throws ClassNotFoundException
     * @throws InputException
     * @return object
     */
    public function create($class, array $arguments, array $params = array())
    {
        if (!class_exists($class)) {
            throw new ClassNotFoundException("Class {$class} not found.");
        }

        foreach ($arguments as &$arg) {
            if (strpos($arg, '#') === 0) {
                $arg = substr($arg, 1);

                if (array_key_exists($arg, $params)) {
                    $arg = $params[$arg];
                } else {
                    throw new InputException("Argument {$arg} is undefined");
                }
            } elseif (strpos($arg, '@') === 0) {
                $arg = substr($arg, 1);

                if (isset($this->container[$arg])) {
                    $arg = $this->container[$arg];
                } else {
                    throw new InputException("Argument {$arg} is undefined");
                }
            }
        }
        unset($arg);

        $reflection  = new \ReflectionClass($class);
        return $reflection->newInstanceArgs($arguments);
    }
}