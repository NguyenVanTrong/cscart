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

$schema['return_info'] = array(
    'class' => '\Tygh\Addons\Rma\Documents\PackingSlip\Variables\ReturnInfoVariable',
    'arguments' => array('#context', '#config', '@formatter'),
    'alias' => 'r',
    'attributes' => array(
        'return_id', 'order_id', 'user_id', 'timestamp', 'action', 'status', 'action_name', 'status_name',
        'total_amount', 'comment'
    )
);

return $schema;