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


use Tygh\Registry;
use Tygh\Template\IActiveVariable;

/**
 * The class of the `settings` variable; it allows access to the store’s settings.
 *
 * @package Tygh\Template\Document\Variables
 */
class SettingsVariable implements IActiveVariable, \ArrayAccess
{
    /** @var array|mixed  */
    protected $settings = array();

    /**
     * SettingsVariable constructor.
     */
    public function __construct()
    {
        $this->settings = Registry::get('settings');
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return isset($this->settings[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return isset($this->settings[$offset]) ? $this->settings[$offset] : null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->settings[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->settings[$offset]);
    }

    /**
     * @inheritDoc
     */
    public static function attributes()
    {
        $settings = Registry::get('settings');

        $settings = array_intersect_key($settings, array_flip(array(
            'General', 'Appearance', 'Checkout', 'Thumbnails', 'Sitemap'
        )));

        $get_attributes = function ($var) use (&$get_attributes) {
            $attributes = array();

            foreach ($var as $attr => $val) {
                if (is_array($val) && !empty($val)) {
                    $attributes[$attr] = $get_attributes($val);
                } else {
                    $attributes[] = $attr;
                }
            }

            return $attributes;
        };

        return $get_attributes($settings);
    }
}