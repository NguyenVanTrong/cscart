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
 * The interface of the class factory responsible for creating the message sender object.
 * 
 * @package Tygh\Mailer
 */
interface ITransportFactory
{
    /**
     * Create transport instance by type
     *
     * @param string    $type       Type of transport (smtp|mail|sendmail)
     * @param array     $settings   Data of transport settings
     *
     * @return ITransport
     */
    public function createTransport($type, $settings);
}