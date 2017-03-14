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

namespace Tygh\Web\Antibot;

/**
 * Class NullDriver is a stub antibot driver.
 *
 * @package Tygh\Web\Antibot
 */
class NullDriver implements IAntibotDriver
{
    /**
     * @inheritdoc
     */
    public function isSetUp()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function validateHttpRequest(array $http_request_data)
    {
        return true;
    }
}
