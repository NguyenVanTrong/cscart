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

$schema['blog'] = array(
    'content' => array(
        'items' => array(
            'type' => 'enum',
            'object' => 'pages',
            'items_function' => 'fn_get_pages',
            'remove_indent' => true,
            'hide_label' => true,
            'fillings' => array (
                'blog.recent_posts_scroller' => array(
                    'params' => array(
                        'simple' => true,
                        'sort_by' => 'timestamp',
                        'sort_order' => 'desc',
                        'status' => 'A',
                        'page_type' => PAGE_TYPE_BLOG,
                        'get_image' => true
                    ),
                ),
                'blog.recent_posts' => array(
                    'params' => array(
                        'simple' => true,
                        'sort_by' => 'timestamp',
                        'sort_order' => 'desc',
                        'status' => 'A',
                        'page_type' => PAGE_TYPE_BLOG,
                    )
                ),
                'blog.text_links' => array (
                    'params' => array (
                        'simple' => true,
                        'sort_by' => 'timestamp',
                        'sort_order' => 'desc',
                        'status' => 'A',
                        'page_type' => PAGE_TYPE_BLOG,
                    ),
                    'settings' => array (
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
                        'limit' => array (
                            'type' => 'input',
                            'default_value' => 10
                        ),
                    ),
                ),
            ),

        ),
    ),
    'templates' => 'addons/blog/blocks',
    'wrappers' => 'blocks/wrappers',
    'cache' => array(
        'update_handlers' => array('pages', 'page_descriptions'),
        'request_handlers' => array('%PAGE_ID%', '%COMPANY_ID%')
    )
);

$schema['rss_feed']['content']['filling']['values']['blog'] = 'blog.posts';
$schema['rss_feed']['content']['filling']['values_settings']['blog'] = array(
    'settings' => array(
        'parent_page_id' => array(
            'type' => 'picker',
            'default_value' => '0',
            'picker' => 'pickers/pages/picker.tpl',
            'picker_params' => array(
                'multiple' => false,
                'extra_url' => '&page_type=' . PAGE_TYPE_BLOG,
                'default_name' => __('root_page'),
            ),
        )
    )
);

return $schema;
