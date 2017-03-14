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

namespace Tygh\Tools;

/**
 * Class Math contains methods commonly used for mathematic operations.
 *
 * @package Tygh\Tools
 * @since   4.3.5
 */
class Math
{
    /**
     * Floors given number to the given precision.
     *
     * @param float|int $x         Number to floor
     * @param float|int $precision Precision in format: 0.01, 0.1, 1, 10, 100, ...
     *
     * @return mixed
     */
    public static function floorToPrecision($x, $precision)
    {
        return $precision * self::floor($x / $precision);
    }

    /**
     * Ceils given number to the given precision.
     *
     * @param float|int $x
     * @param float|int $precision Precision in format: 0.01, 0.1, 1, 10, 100, ...
     *
     * @return mixed
     */
    public static function ceilToPrecision($x, $precision)
    {
        $fmod = $x - self::floorToPrecision($x, $precision);

        if ($fmod > 0) {
            return $x + $precision - $fmod;
        } else {
            return $x + $precision - $precision;
        }
    }

    /**
     * floor() PHP function replacement that doesn't returns floats like 1.999999...
     *
     * @param int|float $x
     *
     * @return int
     */
    public static function floor($x)
    {
        // (int) (string) emulates floor() call for positive values.
        // This is required because floor((double) 201) may be equal to 200, because of
        // PHP's internal floating point numbers representation (i.e. 201.0 is stored as 200.999999...)
        $is_negative = $x < 0;
        $x = (string) $x;
        // negative values have to be floored down
        if ($is_negative && strpos($x, '.')) {
            list($x, $dec) = explode('.', $x);
            $x = $x - ($dec ? 1 : 0);
        }

        return (int) $x;
    }
}
