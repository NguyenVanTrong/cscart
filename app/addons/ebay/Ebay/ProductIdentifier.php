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
 * Class ProductIdentifier
 * @package Ebay
 */
class ProductIdentifier
{
    /** @var string */
    public $code;

    /** @var string */
    public $value;

    public function __construct($code, $value)
    {
        $this->code = trim($code);

        if (is_array($value)) {
            $value = implode(',', $value);
        }

        $this->value = trim($value);
    }

    /** @inheritdoc */
    public function __toString()
    {
        return $this->value;
    }
}