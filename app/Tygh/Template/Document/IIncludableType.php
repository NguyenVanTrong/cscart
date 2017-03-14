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


namespace Tygh\Template\Document;

/**
 * The interface for the document type that allows to include the document into email notification templates.
 *
 * @package Tygh\Template\Document
 */
interface IIncludableType
{
    /**
     * Include document into email template.
     *
     * @param string $code      Template code.
     * @param string $lang_code Language code.
     * @param array $params     Including params.
     *
     * @return string
     */
    public function includeDocument($code, $lang_code, $params);
}