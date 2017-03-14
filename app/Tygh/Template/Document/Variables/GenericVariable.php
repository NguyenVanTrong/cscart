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


namespace Tygh\Template\Document\Variables;


use Tygh\Template\IContext;
use Tygh\Template\IVariable;

/**
 * The class that allows to specify the variables available in the document editor with a schema, without the need to create separate classes.
 *
 * @package Tygh\Template\Document\Variables
 */
class GenericVariable implements IVariable, \ArrayAccess
{
    /** @var array  */
    protected $data = array();

    /**
     * GenericVariable constructor.
     * @param IContext  $context
     * @param array     $config
     */
    public function __construct(IContext $context, array $config)
    {
        if (isset($config['data'])) {
            if ($config['data'] instanceof \Closure) {
                $this->data = $config['data']($context);
            } else {
                $this->data = $config['data'];
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}