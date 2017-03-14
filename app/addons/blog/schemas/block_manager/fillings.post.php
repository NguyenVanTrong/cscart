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

$schema['blog.recent_posts_scroller'] = array(
    'parent_page_id' => array (
        'type' => 'picker',
        'default_value' => '0',
        'picker' => 'pickers/pages/picker.tpl',
        'picker_params' => array (
            'multiple' => false,
            'use_keys' => 'N',
            'default_name' => __('root_level'),
            'extra_url' => "&page_type=" . PAGE_TYPE_BLOG
        ),
    ),
    'period' => array (
        'type' => 'selectbox',
        'values' => array (
            'A' => 'any_date',
            'D' => 'today',
            'HC' => 'last_days',
        ),
        'default_value' => 'any_date'
    ),
);

$schema['blog.recent_posts'] = array(
    'period' => array (
        'type' => 'selectbox',
        'values' => array (
            'A' => 'any_date',
            'D' => 'today',
            'HC' => 'last_days',
        ),
        'default_value' => 'any_date'
    ),
    'last_days' => array (
        'type' => 'input',
        'default_value' => 1
    ),
    'limit' => array (
        'type' => 'input',
        'default_value' => 3
    ),
);

return $schema;
