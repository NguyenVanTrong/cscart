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

/**
 * Gets available order documents for order notification
 *
 * @return array
 */
function fn_emails_get_order_document_variants()
{
    /** @var \Tygh\Template\Document\Repository $repository */
    $repository = \Tygh::$app['template.document.repository'];

    $result = array();
    $documents = $repository->findByType('order');

    foreach ($documents as $document) {
        $result[$document->getCode()] = $document->getName();
    }

    return $result;
}