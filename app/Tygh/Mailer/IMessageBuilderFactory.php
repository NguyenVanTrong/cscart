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
 * The interface of the class factory class responsible for creating the message builder object.
 * 
 * @package Tygh\Mailer
 */
interface IMessageBuilderFactory
{
    /**
     * Create builder by type
     * 
     * @param string $type Builder type
     * 
     * @return IMessageBuilder
     */
    public function createBuilder($type);
}