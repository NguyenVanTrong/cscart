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

$schema = fn_get_schema('documents', 'order');

$schema['supplier'] = array(
    'class' => '\Tygh\Template\Document\Variables\GenericVariable',
    'data' => function (\Tygh\Addons\Suppliers\Documents\SupplierOrder\Context $context) {
        /** @var \Tygh\Tools\Formatter $formatter */
        $formatter = Tygh::$app['formatter'];
        $data = $context->getSupplier();
        $data['cost'] = $formatter->asPrice($data['cost']);

        return $data;
    },
    'arguments' => array('#context', '#config', '@formatter'),
    'alias' => 's',
    'attributes' => array(
        'name', 'company_id', 'cost', 'supplier_id',
        'shippings' => array(
            '[0..N]' => array(
                'shipping_id', 'shipping', 'delivery_time', 'rate_calculation', 'destination',
                'min_weight', 'max_weight', 'service_code', 'module', 'rate', 'group_name'
            )
        ),
    )
);

return $schema;