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

$schema = array(
    'categories.view' => array(
        'index' => array('category_id'),
        'noindex' => array('features_hash')
    ),
    'products.view' => array(
        'index' => array('product_id')
    ),
    'pages.view' => array(
        'index' => array('page_id')
    ),
    'product_features.view' => array(
        'index' => array('variant_id'),
        'noindex' => array('features_hash'),
    ),
    'companies.view' => array(
        'index' => array('company_id')
    ),
    'products.search' => array(
        'index' => array('search_performed'),
        'noindex' => array('features_hash')
    ),
    'product_features.compare' => array(
        'noindex' => true
    ),
    'profiles.add' => array(
        'noindex' => true
    ),
    'auth.login_form' => array(
        'noindex' => true
    ),
    'checkout.cart' => array(
        'noindex' => true
    ),
    'checkout.checkout' => array(
        'noindex' => true
    ),
    'auth.recover_password' => array(
        'noindex' => true,
    ),
    'orders.downloads' => array(
        'noindex' => true,
    ),
    'orders.search' => array(
        'noindex' => true,
    ),
    'orders.details' => array(
        'noindex' => true,
    ),
    '_no_page.index' => array(
        'noindex' => true
    ),
);

if (fn_allowed_for('MULTIVENDOR')) {
    $schema['companies.apply_for_vendor'] = array(
        'noindex' => true
    );
}

return $schema;
