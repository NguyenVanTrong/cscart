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

$schema['supplier'] = array(
    'class' => '\Tygh\Template\Document\Variables\GenericVariable',
    'data' => function (\Tygh\Template\Snippet\Table\ItemContext $context) {
        static $suppliers = array();
        $data = array();
        $product = $context->getItem();

        if (!empty($product['extra']['supplier_id'])) {
            $supplier_id = $product['extra']['supplier_id'];

            if (!isset($suppliers[$supplier_id])) {
                $suppliers[$supplier_id] = fn_get_supplier_data($supplier_id);
            }

            $data = $suppliers[$supplier_id];
        }

        return $data;
    },
    'arguments' => array('#context', '#config', '@formatter'),
    'attributes' => array(
        'supplier_id', 'company_id', 'name', 'address', 'city', 'state', 'country', 'zipcode',
        'email', 'phone', 'fax'
    )
);

return $schema;