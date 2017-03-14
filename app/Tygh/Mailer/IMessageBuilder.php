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

namespace Tygh\Mailer;

/**
 * The interface of the class responsible for building a message from the parameters
 *
 * @package Tygh\Mailer
 */
interface IMessageBuilder
{
    /**
     * Build message by parameters
     *
     * @param string $params    Message parameters
     * @param string $area      Area
     * @param string $lang_code Language code
     *
     * @return Message
     */
    public function createMessage($params, $area, $lang_code);
}