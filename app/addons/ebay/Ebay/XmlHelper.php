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

namespace Ebay;

/**
 * Class ApiHelper
 * @package Ebay
 */
class XmlHelper
{
    /**
     * Normalize xml value as boolean
     * @param string $value
     * @return bool
     */
    public static function normalizeBoolean($value)
    {
        return in_array(strtolower((string) $value), array('true', '1', 'required'));
    }

    /**
     * Return xml value as double. Check on isset
     *
     * @param \SimpleXMLElement $response
     * @param string $key
     * @param mixed $default Default null
     * @return bool|mixed
     */
    public static function getAsDouble(\SimpleXMLElement $response, $key, $default = null)
    {
        if (isset($response->$key)) {
            return (double) $response->$key;
        }

        return $default;
    }

    /**
     * Return xml value as int. Check on isset
     *
     * @param \SimpleXMLElement $response
     * @param string $key
     * @param mixed $default  Default null
     * @return bool|mixed
     */
    public static function getAsInt(\SimpleXMLElement $response, $key, $default = null)
    {
        if (isset($response->$key)) {
            return (int) $response->$key;
        }

        return $default;
    }

    /**
     * Return xml value as string. Check on isset
     *
     * @param \SimpleXMLElement $response
     * @param string $key
     * @param mixed $default Default null
     * @return bool|mixed
     */
    public static function getAsString(\SimpleXMLElement $response, $key, $default = null)
    {
        if (isset($response->$key)) {
            return (string) $response->$key;
        }

        return $default;
    }

    /**
     * Return xml array values as string. Check on isset
     *
     * @param \SimpleXMLElement $response
     * @param $key
     * @return array|mixed
     */
    public static function getArrayAsStrings(\SimpleXMLElement $response, $key)
    {
        $result = array();

        if (isset($response->$key)) {
            foreach ($response->$key as $item) {
                $result[] = (string) $item;
            }
        }

        return $result;
    }
    /**
     * Return xml value as boolean. Check on isset
     *
     * @param \SimpleXMLElement $response
     * @param string $key
     * @param mixed $default Default null
     * @return bool|mixed
     */
    public static function getAsBoolean(\SimpleXMLElement $response, $key, $default = null)
    {
        if (isset($response->$key)) {
            return static::normalizeBoolean($response->$key);
        }

        return $default;
    }
}
