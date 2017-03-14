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

$schema['addons/blog/blocks/recent_posts_scroller.tpl'] = array (
    'fillings' => array('blog.recent_posts_scroller'),
    'params' => array (
        'plain' => true,
        'request' => array (
            'blog_page_id' => '%PAGE_ID%',
        ),
    ),
    'settings' => array (
        'limit' => array (
            'type' => 'input',
            'default_value' => 3
        ),
        'not_scroll_automatically' => array (
            'type' => 'checkbox',
            'default_value' => 'Y'
        ),
        'speed' =>  array (
            'type' => 'input',
            'default_value' => 400
        ),
        'pause_delay' =>  array (
            'type' => 'input',
            'default_value' => 3
        ),
        'item_quantity' =>  array (
            'type' => 'input',
            'default_value' => 3
        ),
        'outside_navigation' => array (
            'type' => 'checkbox',
            'default_value' => 'Y'
        ),
    ),
);

$schema['addons/blog/blocks/recent_posts.tpl'] = array (
    'fillings' => array('blog.recent_posts'),
    'params' => array (
        'plain' => true,
        'request' => array (
            'blog_page_id' => '%PAGE_ID%',
        ),
    )
);

$schema['addons/blog/blocks/text_links.tpl'] = array (
    'fillings' => array('blog.text_links'),
    'params' => array (
        'plain' => true
    )
);

return $schema;
