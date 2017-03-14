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

use Tygh\Enum\ProductFeatures;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_get_google_sitemap_company_condition($field)
{
    if (fn_allowed_for('ULTIMATE')) {
        return fn_get_company_condition($field);
    }

    return '';
}

function fn_google_sitemap_generate_link($object, $value, $languages, $extra = array())
{
    switch ($object) {
        case 'product':
            $link = 'products.view?product_id=' . $value;

            break;
        case 'category':
            $link = 'categories.view?category_id=' . $value;

            break;
        case 'page':
            $link = 'pages.view?page_id=' . $value;

            break;
        case 'extended':
            $link = 'product_features.view?variant_id=' . $value;

            break;
        case 'companies':
            $link = 'companies.view?company_id=' . $value;

            break;
        default:
            fn_set_hook('sitemap_link_object', $link, $object, $value);
    }

    $links = array();
    if (count($languages) == 1) {
        $links[] = fn_url($link, 'C', fn_get_storefront_protocol(), CART_LANGUAGE);
    } else {
        foreach ($languages as $lang_code => $lang) {
            $links[] = fn_url($link . '&sl=' . $lang_code, 'C', fn_get_storefront_protocol(), $lang_code);
        }
    }

    fn_set_hook('sitemap_link', $link, $object, $value, $languages, $links);

    return $links;
}

function fn_google_sitemap_print_item_info($links, $lmod, $frequency, $priority)
{
    $item = '';
    foreach ($links as $link) {
        $link = fn_html_escape($link);
$item .= <<<ITEM
    <url>
        <loc>$link</loc>
        <lastmod>$lmod</lastmod>
        <changefreq>$frequency</changefreq>
        <priority>$priority</priority>
    </url>\n
ITEM;
    }

    return $item;
}

function fn_google_sitemap_get_frequency()
{
    $frequency = array(
        'always' => __('always'),
        'hourly' => __('hourly'),
        'daily' => __('daily'),
        'weekly' => __('weekly'),
        'monthly' => __('monthly'),
        'yearly' => __('yearly'),
        'never' => __('never'),
    );

    return $frequency;
}

function fn_google_sitemap_get_priority()
{
    $priority = array();

    for ($i = 0.1; $i <= 1; $i += 0.1) {
        $priority[(string) $i] = (string) $i;
    }

    return $priority;
}

function fn_google_sitemap_clear_url_info()
{
    $storefront_url = fn_get_storefront_url(fn_get_storefront_protocol());
    if (fn_allowed_for('ULTIMATE')) {
        if (Registry::get('runtime.company_id') || Registry::get('runtime.simple_ultimate')) {
        } else {
            $storefront_url = '';
        }
    }

    if (!empty($storefront_url)) {
        $sitemap_available_in_customer = __('sitemap_available_in_customer', array(
            '[http_location]' => $storefront_url,
            '[sitemap_url]' => fn_url('xmlsitemap.view', 'C', fn_get_storefront_protocol()),
        ));

        return __('google_sitemap.text_regenerate', array(
            '[http_location]' => $storefront_url,
            '[regenerate_url]' =>  fn_url('xmlsitemap.generate'),
            '[sitemap_available_in_customer]' => $sitemap_available_in_customer
        ));

    } else {
        return __('google_sitemap.text_select_storefront');
    }
}

function fn_google_sitemap_get_content($map_page = 0)
{
    $sitemap_settings = Registry::get('addons.google_sitemap');
    $location = fn_get_storefront_url(fn_get_storefront_protocol());

    $lmod = date("Y-m-d", TIME);

    // HEAD SECTION

    $simple_head = <<<HEAD
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">


HEAD;

    $simple_foot = <<<FOOT

</urlset>
FOOT;

    $index_map_url = <<<HEAD
    <url>
        <loc>$location/</loc>
        <lastmod>$lmod</lastmod>
        <changefreq>$sitemap_settings[site_change]</changefreq>
        <priority>$sitemap_settings[site_priority]</priority>
    </url>\n
HEAD;

    // END HEAD SECTION

    $parts = 0;
    if ($sitemap_settings['include_categories'] == "Y") {
        $parts++;
        $get_categories = true;
    }
    if ($sitemap_settings['include_products'] == "Y") {
        $parts++;
        $get_products = true;
    }
    if ($sitemap_settings['include_pages'] == "Y") {
        $parts++;
        $get_pages = true;
    }
    if ($sitemap_settings['include_extended'] == "Y") {
        $parts++;
        $get_features = true;
    }
    if (fn_allowed_for('MULTIVENDOR') && $sitemap_settings['include_companies'] == 'Y') {
        $parts++;
        $get_companies = true;
    }

    fn_set_progress('parts', $parts);

    // SITEMAP CONTENT
    $link_counter = 1;
    $file_counter = 1;

    $sitemap_path = fn_get_files_dir_path(false) . 'google_sitemap/';
    fn_rm($sitemap_path);
    fn_mkdir($sitemap_path);

    $file = fopen($sitemap_path . 'sitemap' . $file_counter . '.xml', "wb");
    fwrite($file, $simple_head . $index_map_url);

    $languages = db_get_hash_single_array("SELECT lang_code, name FROM ?:languages WHERE status = 'A'", array('lang_code', 'name'));

    if (!empty($get_categories)) {
        $categories = db_get_fields("SELECT category_id FROM ?:categories WHERE FIND_IN_SET(?i, usergroup_ids) AND status = 'A' ?p", USERGROUP_ALL, fn_get_google_sitemap_company_condition('?:categories.company_id'));

        fn_set_progress('step_scale', count($categories));

        //Add the all active categories
        foreach ($categories as $category) {
            $links = fn_google_sitemap_generate_link('category', $category, $languages);
            $item = fn_google_sitemap_print_item_info($links, $lmod, $sitemap_settings['categories_change'], $sitemap_settings['categories_priority']);

            fn_google_sitemap_check_counter($file, $link_counter, $file_counter, $links, $simple_head, $simple_foot, 'categories');

            fwrite($file, $item);
        }

    }

    if (!empty($get_products)) {
        $total = ITEMS_PER_PAGE;
        $i = 0;

        $params = $_REQUEST;
        $params['custom_extend'] = array('categories');
        $params['sort_by'] = 'null';
        $params['only_short_fields'] = true; // NEEDED ONLY FOR NOT TO LOAD UNNECESSARY FIELDS FROM DB
        $params['area'] = 'C';

        $original_auth = Tygh::$app['session']['auth'];
        Tygh::$app['session']['auth'] = fn_fill_auth(array(), array(), false, 'C');

        fn_set_progress('step_scale', db_get_field("SELECT COUNT(*) FROM ?:products WHERE status = 'A'"));

        while ($params['pid'] = db_get_fields("SELECT product_id FROM ?:products WHERE status = 'A' ORDER BY product_id ASC LIMIT $i, $total")) {
            $i += $total;

            list($products) = fn_get_products($params, ITEMS_PER_PAGE);

            foreach ($products as $product) {
                $links = fn_google_sitemap_generate_link('product', $product['product_id'], $languages);
                $item = fn_google_sitemap_print_item_info($links, $lmod, $sitemap_settings['products_change'], $sitemap_settings['products_priority']);

                fn_google_sitemap_check_counter($file, $link_counter, $file_counter, $links, $simple_head, $simple_foot, 'products');

                fwrite($file, $item);
            }
        }
        unset($products);

        Tygh::$app['session']['auth'] = $original_auth;
    }

    if (!empty($get_pages)) {

        $page_types = fn_get_page_object_by_type();
        unset($page_types[PAGE_TYPE_LINK]);

        list($pages) = fn_get_pages(array(
            'simple' => true,
            'status' => 'A',
            'page_type' => array_keys($page_types)
        ));
        fn_set_progress('step_scale', count($pages));

        //Add the all active pages
        foreach ($pages as $page) {
            $links = fn_google_sitemap_generate_link('page', $page['page_id'], $languages, $page);
            $item = fn_google_sitemap_print_item_info($links, $lmod, $sitemap_settings['pages_change'], $sitemap_settings['pages_priority']);

            fn_google_sitemap_check_counter($file, $link_counter, $file_counter, $links, $simple_head, $simple_foot, 'pages');

            fwrite($file, $item);
        }
    }

    if (!empty($get_features)) {
        $vars = db_get_fields(
            "SELECT ?:product_feature_variants.variant_id, ?:product_features.feature_id FROM ?:product_features " .
            "LEFT JOIN ?:product_feature_variants ON (?:product_features.feature_id = ?:product_feature_variants.feature_id) " .
            "WHERE ?:product_features.feature_type = ?s AND ?:product_features.status = 'A'"
        , ProductFeatures::EXTENDED);
        fn_set_progress('step_scale', count($vars));

        //Add the all active extended features
        foreach ($vars as $var) {
            $links = fn_google_sitemap_generate_link('extended', $var, $languages);
            $item = fn_google_sitemap_print_item_info($links, $lmod, $sitemap_settings['extended_change'], $sitemap_settings['extended_priority']);

            fn_google_sitemap_check_counter($file, $link_counter, $file_counter, $links, $simple_head, $simple_foot, 'features');

            fwrite($file, $item);
        }
    }

    if (!empty($get_companies)) {
        $companies = db_get_fields("SELECT company_id FROM ?:companies WHERE status = 'A' ?p", fn_get_google_sitemap_company_condition('?:companies.company_id'));
        fn_set_progress('step_scale', count($companies));

        if (!empty($companies)) {
            foreach ($companies as $company_id) {
                $links = fn_google_sitemap_generate_link('companies', $company_id, $languages);
                $item = fn_google_sitemap_print_item_info($links, $lmod, $sitemap_settings['companies_change'], $sitemap_settings['companies_priority']);

                fn_google_sitemap_check_counter($file, $link_counter, $file_counter, $links, $simple_head, $simple_foot, 'companies');

                fwrite($file, $item);
            }
        }
    }

    fn_set_hook('sitemap_item', $sitemap_settings, $file, $lmod, $link_counter, $file_counter);

    fwrite($file, $simple_foot);
    fclose($file);

    if ($file_counter == 1) {
        fn_rename($sitemap_path . 'sitemap' . $file_counter . '.xml', $sitemap_path . 'sitemap.xml');
    } else {
        // Make a map index file

        $maps = '';
        $seo_enabled = Registry::get('addons.seo.status') == 'A' ? true : false;
        for ($i = 1; $i <= $file_counter; $i++) {
            if ($seo_enabled) {
                $name = $location . '/sitemap' . $i . '.xml';
            } else {
                $name = fn_url('xmlsitemap.view?page=' . $i, 'C', fn_get_storefront_protocol());
            }

            $name = htmlentities($name);
            $maps .= <<<MAP
    <sitemap>
        <loc>$name</loc>
        <lastmod>$lmod</lastmod>
    </sitemap>\n
MAP;
        }
        $index_map = <<<HEAD
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

$maps
</sitemapindex>
HEAD;

        $file = fopen($sitemap_path . 'sitemap.xml', "wb");
        fwrite($file, $index_map);
        fclose($file);
    }
    fn_set_notification('N', __('notice'), __('google_sitemap.map_generated'));
    exit();
}

function fn_google_sitemap_check_counter(&$file, &$link_counter, &$file_counter, $links, $header, $footer, $type)
{
    $stat = fstat($file);
    if ((count($links) + $link_counter) > MAX_URLS_IN_MAP || $stat['size'] >= MAX_SIZE_IN_KBYTES * 1024) {
        fwrite($file, $footer);
        fclose($file);
        $file_counter++;
        $filename = fn_get_files_dir_path() . 'google_sitemap/sitemap' . $file_counter . '.xml';
        $file = fopen($filename, "wb");
        $link_counter = count($links);
        fwrite($file, $header);
    } else {
        $link_counter += count($links);
        fn_set_progress('echo', __($type));
    }
}
