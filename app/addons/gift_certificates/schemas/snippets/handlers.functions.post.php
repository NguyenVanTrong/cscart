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
 * Initialize snippet product table variable for gift certificate.
 *
 * @param \Tygh\Template\Snippet\Snippet                                    $snippet
 * @param \Tygh\Addons\GiftCertificates\Documents\GiftCertificate\Context   $context
 * @param \Tygh\Template\Collection                                         $variable_collection
 */
function fn_gift_certificates_init_product_table_variable($snippet, $context, $variable_collection)
{
    $object_factory = Tygh::$app['template.object_factory'];
    $config = array(
        'class' => '\Tygh\Template\Snippet\Table\TableVariable',
        'arguments' => array(
            '#context', '#snippet', '@template.renderer',
            '@template.snippet.table.column_repository',
            '@template.variable_collection_factory',
            '#items'
        ),
        'name' => 'products_table'
    );

    $gift_certificate = $context->getCertificateData();
    $products = !empty($gift_certificate['products']) ? $gift_certificate['products'] : array();

    $variable = new \Tygh\Template\VariableProxy(
        $config,
        $context,
        $object_factory,
        array('snippet' => $snippet, 'items' => $products)
    );

    $variable_collection->add('products_table', $variable);
}
