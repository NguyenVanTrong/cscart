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

namespace Tygh\Enum;

/**
 * ProductFeatures contains possible values for feature type
 *
 * @package Tygh\Enum
 */
class ProductFeatures
{
    const GROUP = 'G';

    const SINGLE_CHECKBOX = 'C';
    const MULTIPLE_CHECKBOX = 'M';

    const TEXT_SELECTBOX = 'S';
    const NUMBER_SELECTBOX = 'N';

    const EXTENDED = 'E';

    const TEXT_FIELD = 'T';
    const NUMBER_FIELD = 'O';

    const DATE = 'D';

    public static function getSelectable()
    {
        return self::TEXT_SELECTBOX . self::MULTIPLE_CHECKBOX . self::NUMBER_SELECTBOX . self::EXTENDED;
    }

    public static function getAllTypes()
    {
        return self::SINGLE_CHECKBOX . self::MULTIPLE_CHECKBOX . self::TEXT_SELECTBOX . self::NUMBER_SELECTBOX . self::EXTENDED . self::TEXT_FIELD . self::NUMBER_FIELD . self::DATE;
    }
}
