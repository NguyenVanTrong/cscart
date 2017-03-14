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

use Tygh\Exceptions\InputException;

/**
 * The class that provides access to the meta data of a variable.
 *
 * @package Tygh\Template
 */
class VariableMetaData
{
    /** @var string */
    protected $class;

    /** @var string|null */
    protected $alias;

    /** @var string|null */
    protected $name;

    /** @var array */
    protected $arguments = array();

    /** @var array */
    protected $attributes = array();

    /**
     * VariableMetaData constructor.
     *
     * @param array $config Variable config.
     * @throws InputException
     */
    public function __construct(array $config)
    {
        if (!isset($config['class'])) {
            throw new InputException("Variable class is not defined.");
        }

        if (!isset($config['arguments'])) {
            $config['arguments'] = array('#context', '#config');
        }

        $this->class = (string) $config['class'];
        $this->arguments = (array) $config['arguments'];

        if (isset($config['name'])) {
            $this->name = (string) $config['name'];
        }

        if (isset($config['alias'])) {
            $this->alias = (string) $config['alias'];
        }

        $attributes = array();
        $reflexion_class = new \ReflectionClass($this->getClass());

        if ($reflexion_class->implementsInterface('\Tygh\Template\IActiveVariable')) {
            $attributes = (array) call_user_func(array($this->getClass(), 'attributes'));
        } else {
            $properties = $reflexion_class->getProperties(\ReflectionProperty::IS_PUBLIC);
            $methods = $reflexion_class->getMethods(\ReflectionMethod::IS_PUBLIC);

            foreach ($properties as $property) {
                if (!$property->isStatic()) {
                    $attributes[] = $property->getName();
                }
            }

            foreach ($methods as $method) {
                if (!$method->isStatic()) {
                    $method_name = $method->getName();

                    if (strpos($method_name, 'get') === 0) {
                        $method_name = substr($method_name, 3);
                        $attributes[] = $this->underscore($method_name);
                    }
                }
            }
        }

        if (isset($config['attributes'])) {
            if ($config['attributes'] instanceof \Closure) {
                $config_attributes = (array) call_user_func($config['attributes']);
            } else {
                $config_attributes = (array) $config['attributes'];
            }

            $attributes = array_merge_recursive($attributes, $config_attributes);
        }

        $this->attributes = $this->convertAttributes($attributes);
    }

    /**
     * @param array $attributes
     * @return array
     */
    protected function convertAttributes(array $attributes)
    {
        $result = array();

        foreach ($attributes as $attribute => $value) {
            if (is_array($value)) {
                $result[$attribute] = $this->convertAttributes($value);
            } else {
                $result[$value] = $value;
            }
        }

        return $result;
    }
    /**
     * Gets variable constructor arguments.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Gets variable class.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Gets variable attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Gets variable alias.
     *
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Gets variable name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Converts any "CamelCased" into an "underscored_word".
     *
     * @param string $words The word(s) to underscore.
     *
     * @return string
     */
    protected function underscore($words)
    {
        return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $words));
    }
}