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


use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * The class responsible for converting CSS styles of the message.
 * Convert css to inline styles.
 *
 * @package Tygh\Mailer
 */
class MessageStyleFormatter
{
    /**
     * Converts message css to inline styles
     *
     * @param Message $message Instance of message
     */
    public function convert(Message $message)
    {
        $content = $message->getBody();

        if (preg_match('#\<style(.*?)\>(.*?)\</style\>#s', $content, $m)) {
            try {
                $ci = new CssToInlineStyles();
                $ci->setHTML(str_replace($m[0], '', $content));
                $ci->setCSS($m[2]);

                $message->setBody($ci->convert());
            } catch (\TijsVerkoyen\CssToInlineStyles\Exception $e) {

            }
        }
    }
}