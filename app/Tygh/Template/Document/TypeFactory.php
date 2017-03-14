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


namespace Tygh\Template\Document;


use Pimple\Container;
use Tygh\Exceptions\InputException;

/**
 * The factory class of document types.
 *
 * @package Tygh\Template\Document
 */
class TypeFactory
{
    /** @var array  */
    protected $types;

    /** @var Container  */
    protected $container;

    /**
     * TypeFactory constructor.
     *
     * @param array         $types      Document types schema.
     * @param Container     $container  Application container.
     */
    public function __construct(array $types, Container $container)
    {
        $this->types = $types;
        $this->container = $container;
    }

    /**
     * Create document type instance.
     *
     * @param string $type Document type.
     *
     * @return IType
     * @throws \Tygh\Exceptions\ClassNotFoundException
     * @throws \Tygh\Exceptions\InputException
     */
    public function create($type)
    {
        $result = null;
        $service = "template.document.{$type}.type";

        if (in_array($type, $this->types, true) && $this->container->offsetExists($service)) {
            $result = $this->container[$service];

            if (!$result instanceof IType) {
                throw new InputException("Document type {$type} does not instance of IType");
            }

            return $result;
        } else {
            throw new InputException("Undefined document type {$type}.");
        }
    }
}