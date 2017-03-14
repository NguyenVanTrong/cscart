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

namespace Tygh\Mailer\MessageBuilders;


use Tygh\Mailer\AMessageBuilder;
use Tygh\Mailer\Message;

/**
 * The class responsible for building the message based on the message parameters only. This class is used when the message body is passed in the parameters.
 *
 * @package Tygh\Mailer\MessageBuilders
 */
class DefaultMessageBuilder extends AMessageBuilder
{
    /** @inheritdoc */
    protected function initMessage(Message $message, $params, $area, $lang_code)
    {
        if (!empty($params['body'])) {
            $message->setBody($params['body']);
        }

        if (!empty($params['subject'])) {
            $message->setSubject($params['subject']);
        }

        if (!empty($params['subj'])) {
            $message->setSubject($params['subj']);
        }
    }
}