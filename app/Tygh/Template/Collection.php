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
 * The class that allows creating collections of objects.
 *
 * @package Tygh\Template
 */
class Collection
{
    /** @var array  */
    protected $items = array();

    /**
     * Collection constructor.
     *
     * @param array $items List of items.
     */
    public function __construct(array $items = array())
    {
        foreach ($items as $name => $variable) {
            $this->add($name, $variable);
        }
    }

    /**
     * Add item to collection.
     *
     * @param string    $name       Item name.
     * @param mixed     $item       Instance of item.
     */
    public function add($name, $item)
    {
        $this->items[$name] = $item;
    }

    /**
     * Remove item from collection.
     *
     * @param string $name Item name.
     */
    public function remove($name)
    {
        unset($this->items[$name]);
    }

    /**
     * Check contains item in collection.
     *
     * @param string $name Item name.
     *
     * @return bool
     */
    public function contains($name)
    {
        return array_key_exists($name, $this->items);
    }

    /**
     * Gets item from collection.
     *
     * @param string $name Item name.
     *
     * @return null|mixed
     */
    public function get($name)
    {
        return $this->contains($name) ? $this->items[$name] : null;
    }

    /**
     * Gets all items from collection.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->items;
    }
}