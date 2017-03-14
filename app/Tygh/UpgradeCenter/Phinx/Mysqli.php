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


namespace Tygh\UpgradeCenter\Phinx;

/**
 * Class Mysqli
 * @package Tygh\UpgradeCenter\Phinx
 */
class Mysqli extends \mysqli
{
    /**
     * Quotes a string for use in a query.
     *
     * @param string $value
     * @return string
     */
    public function quote($value)
    {
        $value = $this->real_escape_string($value);
        return "'{$value}'";
    }
}