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

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_blog_page_object_by_type(&$types)
{
    $types[PAGE_TYPE_BLOG] = array(
        'content' => 'blog',
        'single' => 'blog.post',
        'name' => 'blog.posts',
        'add_name' => 'blog.add_post',
        'edit_name' => 'blog.editing_post',
        'new_name' => 'blog.new_post',
        'exclusive' => true, // indicates that this page type should not be combined with other pages
        'hide_fields' => array(
            'position' => true
        )
    );
}


function fn_blog_remove_pages()
{
    $pages = db_get_fields("SELECT page_id FROM ?:pages WHERE page_type = ?s ", PAGE_TYPE_BLOG);

    foreach ($pages as $page_id) {
        fn_delete_page($page_id, $recurse = true);
    }
}

function fn_blog_post_get_pages(&$pages, $params, $lang_code)
{
    $blog_pages = array();
    foreach ($pages as $idx => $page) {
        if (!empty($page['page_type']) && $page['page_type'] == PAGE_TYPE_BLOG) {
            $blog_pages[$idx] = $page['page_id'];
            if (!empty($page['description'])) {
                if (strpos($page['description'], BLOG_CUT) !== false) {
                    list($pages[$idx]['spoiler']) = explode(BLOG_CUT, $page['description'], 2);
                } else {
                    $pages[$idx]['spoiler'] = $page['description'];
                }
            }

            if (!empty($page['subpages'])) {
                fn_blog_post_get_pages($pages[$idx]['subpages'], $params, $lang_code);
            }
        }
    }

    if (!empty($blog_pages)) {

        $images = array();
        $authors = db_get_hash_single_array("SELECT CONCAT(u.firstname, ' ', u.lastname) as author, b.page_id FROM ?:blog_authors as b LEFT JOIN ?:users  as u ON b.user_id = u.user_id WHERE b.page_id IN (?n)", array('page_id', 'author'), $blog_pages);
        if (!empty($params['get_image'])) {
            $images = fn_get_image_pairs($blog_pages, 'blog', 'M', true, false, $lang_code);
        }

        foreach ($blog_pages as $idx => $page_id) {
            $pages[$idx]['main_pair'] = !empty($images[$page_id]) ? reset($images[$page_id]) : array();
            $pages[$idx]['author'] = !empty($authors[$page_id]) ? $authors[$page_id] : '';
        }
    }
}

function fn_blog_get_page_data(&$page_data, $lang_code, $preview, $area)
{
    if ($page_data['page_type'] == PAGE_TYPE_BLOG) {
        $page_data['main_pair'] = fn_get_image_pairs($page_data['page_id'], 'blog', 'M', true, false, $lang_code);
        $page_data['author'] = db_get_field("SELECT CONCAT(u.firstname, ' ', u.lastname) FROM ?:blog_authors as b LEFT JOIN ?:users  as u ON b.user_id = u.user_id WHERE b.page_id = ?i", $page_data['page_id']);
    }
}

function fn_blog_get_pages_pre(&$params, $items_per_page, $lang_code)
{
    if (!empty($params['blog_page_id'])) {
        $parent_id = db_get_field("SELECT parent_id FROM ?:pages WHERE page_id = ?i AND page_type = ?l", $params['blog_page_id'], PAGE_TYPE_BLOG);
        if ($parent_id) {
            $params['parent_id'] = $parent_id;
        } elseif ($parent_id !== "") {
            $params['parent_id'] = $params['blog_page_id'];
        }
    }
}

function fn_blog_get_pages(&$params, $join, $condition, $fields, $group_by, &$sortings, $lang_code)
{
    if (!empty($params['page_type']) && $params['page_type'] == PAGE_TYPE_BLOG) {
        if (!empty($params['get_tree'])) {
            $sortings['multi_level'] = array(
                '?:pages.parent_id',
                '?:pages.timestamp',
            );
        }
        db_sort($params, $sortings, 'timestamp', 'desc');
    }
}

function fn_blog_update_page_post($page_data, $page_id, $lang_code, $create, $old_page_data)
{
    if (!empty($page_data['page_type']) && $page_data['page_type'] == PAGE_TYPE_BLOG) {
        fn_attach_image_pairs('blog_image', 'blog', $page_id, $lang_code);

        db_query("REPLACE INTO ?:blog_authors ?e", array(
            'page_id' => $page_id,
            'user_id' => Tygh::$app['session']['auth']['user_id']
        ));
    }
}

function fn_blog_delete_page($page_id)
{
    fn_delete_image_pairs($page_id, 'blog');

    db_query("DELETE FROM ?:blog_authors WHERE page_id = ?i", $page_id);
}

function fn_blog_clone_page($page_id, $new_page_id)
{
    fn_clone_image_pairs($new_page_id, $page_id, 'blog');

    db_query("INSERT INTO ?:blog_authors (page_id, user_id) SELECT ?i as page_id, user_id FROM ?:blog_authors WHERE page_id = ?i", $new_page_id, $page_id);
}

function fn_blog_sanitize_html($purifier_config, $raw_html)
{
    /** @var $purifier_config \HTMLPurifier_Config */
    $purifier_config->set('HTML.AllowedComments', array_merge(
        (array) $purifier_config->get('HTML.AllowedComments'),
        array('CUT')
    ));
}

/**
 * Generates feed items from blog posts
 *
 * @param array $items_data      Feed items
 * @param array $additional_data Feed properties (title, description, etc.)
 * @param array $block_data      Block settings
 * @param string $lang_code      Two-letter language code
 */
function fn_blog_generate_rss_feed(&$items_data, &$additional_data, &$block_data, &$lang_code)
{
    if (!empty($block_data['content']['filling']) && $block_data['content']['filling'] == 'blog') {
        $parent_id = (int) $block_data['properties']['filling']['blog']['parent_page_id'];
        $max_items = !empty($block_data['properties']['max_item']) ? $block_data['properties']['max_item'] : Registry::get('settings.Appearance.elements_per_page');

        list($pages) = fn_get_pages(array(
            'parent_id' => $parent_id,
            'page_type' => PAGE_TYPE_BLOG,
            'status' => 'A'
        ), $max_items, $lang_code);

        $additional_data['title'] = !empty($block_data['properties']['feed_title']) ? $block_data['properties']['feed_title'] : __('blog');
        if ($parent_id) {
            $page_data = fn_get_page_data($parent_id, $lang_code);
            $additional_data['title'] .= !empty($page_data['page']) ? '::' . $page_data['page'] : '';
        }

        $additional_data['description'] = !empty($block_data['properties']['feed_description']) ? $block_data['properties']['feed_description'] : $additional_data['title'];
        $additional_data['link'] = fn_url('', 'C', 'http', $lang_code);
        $additional_data['language'] = $lang_code;
        $additional_data['lastBuildDate'] = !empty($pages[0]['timestamp']) ? $pages[0]['timestamp'] : TIME;

        foreach ($pages as $page_id => $page_data) {
            $items_data[] = array(
                'title' => $page_data['page'],
                'link' => fn_url('pages.view?page_id=' . $page_id),
                'description' => $page_data['description'],
                'pubDate' => fn_format_rss_time($page_data['timestamp'])
            );
        }
    }
}
