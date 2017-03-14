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

$schema['pages']['check_params'] = function($request) use ($schema) {

    $dispatch = $schema['pages']['customer_dispatch'];
    $page_type = '';
    if (!empty($request['page_id'])) {
        $page_type = db_get_field("SELECT page_type FROM ?:pages WHERE page_id = ?i", $request['page_id']);
    } elseif (!empty($request['page_type'])) {
        $page_type = $request['page_type'];
    }
    $suffix = ($page_type == PAGE_TYPE_BLOG) ? '?page_type=' . PAGE_TYPE_BLOG : '';

    return $dispatch . $suffix;
};

return $schema;
