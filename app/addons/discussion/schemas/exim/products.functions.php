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

function fn_exim_products_discussion_export($product_id)
{

    $data = fn_get_discussion($product_id, 'P');

    if (!empty($data['type'])) {
        $return = $data['type'];
    } else {
        $return = false;
    }

    return $return;
}

function fn_exim_products_discussion_import($product_id, $value)
{
    $allow_discussion_type = 'BCRD';

    if (empty($value) || strpos($allow_discussion_type, $value) === false) {
        $value = 'D';
    }

    $product_company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);

    if (!empty($product_company_id)) {
        $product_data['company_id'] = $product_company_id;
    } else {
        if (Registry::get('runtime.company_id')) {
            $product_company_id = Registry::get('runtime.company_id');
        }
    }

    $discussion = array(
        'object_type' => 'P',
        'object_id' => $product_id,
        'type' => $value,
        'company_id' => $product_company_id
    );

    fn_update_discussion($discussion);

    return true;
}
