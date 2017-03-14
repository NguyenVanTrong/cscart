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

/**
 * The class that implements delayed initialization of variables.
 *
 * @package Tygh\Template
 */
class VariableProxy implements IVariable, \ArrayAccess
{
    /** @var ObjectFactory  */
    private $factory;

    /** @var array  */
    private $config;

    /** @var IContext  */
    private $context;

    /** @var IVariable */
    private $variable;

    /** @var VariableMetaData */
    private $variable_meta_data;

    /** @var array */
    private $params = array();

    /**
     * VariableProxy constructor.
     *
     * @param array         $config     Variable config.
     * @param IContext      $context    Instance of context.
     * @param ObjectFactory $factory    Instance of object factory.
     * @param array         $params     Additional params usable for instantiate variable.
     */
    public function __construct(array $config, IContext $context, ObjectFactory $factory, array $params = array())
    {
        $this->config = $config;
        $this->context = $context;
        $this->factory = $factory;
        $this->params = $params;
        $this->variable_meta_data = new VariableMetaData($this->config);
    }

    /**
     * Gets variable meta data.
     *
     * @return VariableMetaData
     */
    public function getMetaData()
    {
        return $this->variable_meta_data;
    }

    /**
     * Initialize variable.
     *
     * @throws \Tygh\Exceptions\ClassNotFoundException
     * @throws \Tygh\Exceptions\InputException
     */
    private function initVariable()
    {
        if ($this->variable === null) {
            $this->variable = $this->factory->create(
                $this->variable_meta_data->getClass(),
                $this->variable_meta_data->getArguments(),
                array_merge(array(
                    'context' => $this->context,
                    'config' => $this->config,
                    'meta_data' => $this->variable_meta_data
                ), $this->params)
            );
        }
    }

    /**
     * Gets variable attribute.
     *
     * @param string    $attribute  Attribute name.
     * @param array     $arguments  Arguments for retrieve attribute.
     *
     * @return mixed|null
     */
    public function getAttribute($attribute, $arguments = array())
    {
        if ($this->issetAttribute($attribute)) {
            $this->initVariable();
            $method = 'get' . $this->camelize($attribute);

            if (method_exists($this->variable, $method)) {
                return call_user_func_array(array($this->variable, $method), $arguments);
            }

            if (property_exists($this->variable, $attribute)) {
                return $this->variable->$attribute;
            }

            if ($this->variable instanceof \ArrayAccess && isset($this->variable[$attribute])) {
                return $this->variable[$attribute];
            }
        }

        return null;
    }

    /**
     * Check variable isset attribute.
     *
     * @param string $attribute Attribute name.
     * @return bool
     */
    public function issetAttribute($attribute)
    {
        $attributes = $this->variable_meta_data->getAttributes();
        return array_key_exists($attribute, $attributes);
    }

    /**
     * @inheritdoc
     */
    public function __isset($name)
    {
        return $this->issetAttribute($name);
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $arguments)
    {
        return $this->getAttribute($name, $arguments);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        return $this->getAttribute($name);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException("Can not update variable");
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $this->issetAttribute($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        throw new \RuntimeException("Can not update variable");
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    /** @inheritdoc */
    public function __toString()
    {
        $this->initVariable();

        if (method_exists($this->variable, '__toString')) {
            return (string) $this->variable;
        }

        return '';
    }

    /**
     * Returns given word as CamelCased Converts a word like "send_email" to "SendEmail".
     *
     * @param string $word The word to CamelCase.
     *
     * @return string
     */
    public function camelize($word)
    {
        return str_replace(' ', '', ucwords(preg_replace('/[^A-Za-z0-9]+/', ' ', $word)));
    }
}