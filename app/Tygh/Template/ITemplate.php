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


namespace Tygh\Template;

/**
 * The interface for the templates of documents, snippets, email notifications, etc.
 *
 * @package Tygh\Template
 */
interface ITemplate
{
    /**
     * Gets template.
     *
     * @return string
     */
    public function getTemplate();


    /**
     * Gets available snippet type.
     *
     * @return string
     */
    public function getSnippetType();
}