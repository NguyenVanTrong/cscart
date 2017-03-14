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

$rule = array(
    'base_url' => array('fn_seo_filter_current_url' => array("result_ids", "full_render", "filter_id", "view_all", "req_range_id", "features_hash", "subcats", "page", "total", "hint_q")),
    'search' => true
);

foreach (array('final_sale', 'on_sale', 'bestsellers', 'newest') as $mode) {
    $schema['products'][$mode] = $rule;
}

return $schema;
