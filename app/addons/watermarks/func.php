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

use Tygh\Settings;
use Tygh\Storage;
use Tygh\Registry;
use Tygh\Tools\ImageHelper;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_watermarks_init_company_data(&$params, &$company_id, &$company_data)
{
    if (fn_allowed_for('ULTIMATE')) {
        if ($company_id) {
            fn_define('WATERMARK_IMAGE_ID', $company_id);
            fn_define('WATERMARKS_DIR_NAME', 'watermarked/' . $company_id . '/');
        } else {
            fn_define('WATERMARK_IMAGE_ID', 0);
            fn_define('WATERMARKS_DIR_NAME', 'watermarked/');
        }

    } else {
        fn_define('WATERMARK_IMAGE_ID', 1);
        fn_define('WATERMARKS_DIR_NAME', 'watermarked/');
    }
}

function fn_get_watermark_settings($company_id = null)
{
    static $cache;

    if (!isset($cache['settings_' . $company_id])) {
        $settings = Settings::instance()->getValue('watermark', '', $company_id);
        $settings = unserialize($settings);

        if (empty($settings)) {
            $settings = array();
        }

        if (!empty($settings['type']) && $settings['type'] == 'G') {
            if (!empty($company_id)) {
                $settings['image_pair'] = fn_get_image_pairs($company_id, 'watermark', 'M');
            } else {
                $settings['image_pair'] = fn_get_image_pairs(WATERMARK_IMAGE_ID, 'watermark', 'M');
            }
        }

        $cache['settings_' . $company_id] = $settings;
    }

    return $cache['settings_' . $company_id];
}

function fn_replace_rewrite_condition($file_name, $condition, $comment)
{
    if (!empty($condition)) {
        $condition = "\n" .
            "# $comment\n" .
            "<IfModule mod_rewrite.c>\n" .
            "RewriteEngine on\n" .
            $condition .
            "</IfModule>\n" .
            "# /$comment";
    }

    $content = fn_get_contents($file_name);
    if ($content === false) {
        $content = '';
    } elseif (!empty($content)) {
        // remove old instructions
        $data = explode("\n", $content);
        $remove_start = false;
        foreach ($data as $k => $line) {
            if (preg_match("/# $comment/", $line)) {
                $remove_start = true;
            }

            if ($remove_start) {
                unset($data[$k]);
            }

            if (preg_match("/# \/$comment/", $line)) {
                $remove_start = false;
            }
        }
        $content = implode("\n", $data);
    }

    $content .= $condition;

    return fn_put_contents($file_name, $content);
}

function fn_get_apply_watermark_options()
{
    $option_types = array(
        'icons' => array(
            'use_for_product_icons',
            'use_for_category_icons'
        ),
        'detailed' => array(
            'use_for_product_detailed',
            'use_for_category_detailed'
        ),
    );

    $res = array();
    foreach ($option_types as $type => $options) {
        $res[$type] = db_get_hash_single_array("SELECT name, object_id  FROM ?:settings_objects WHERE name IN (?a)", array('name', 'object_id'), $options);
    }

    return $res;
}

/**
 * Clear generated watermarks
 *
 * @param array $images_types Images types to be cleared, clear all if empty
 * @return boolean Always true
 */
function fn_delete_watermarks($images_types)
{
    $path_types = array(
        'icons' => array(
            'category',
            'product',
            'thumbnails'
        ),
        'detailed' => array(
            'detailed'
        )
    );

    $delete_paths = array();
    foreach ($path_types as $k => $v) {
        if (empty($images_types) || !empty($images_types[$k])) {
            $delete_paths = array_merge($delete_paths, $path_types[$k]);
        }
    }

    $wt_paths = array(WATERMARKS_DIR_NAME);

    if (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.company_id')) {
        $wt_paths = array();
        $companies = fn_get_short_companies();
        foreach ($companies as $company_id => $name) {
            $wt_paths[] = 'watermarked/' . $company_id . '/';
        }
    }

    foreach ($delete_paths as $path) {
        foreach ($wt_paths as $wt_path) {
            Storage::instance('images')->deleteDir($wt_path . $path);
        }
    }

    fn_clear_cache();

    return true;
}

function fn_is_need_watermark($object_type, $is_detailed = true, $company_id = null)
{
    static $cache;
    if ($object_type == 'watermark') {
        return false;
    }

    if ($object_type == 'product_option' || $object_type == 'variant_image') {
        $object_type = 'product';
    }

    if (!isset($cache[$object_type . $company_id . '_' . $is_detailed])) {

        $result = fn_is_watermarks_enabled($company_id);

        if ($result == true) {

            $image_type = $is_detailed ? 'detailed' : 'icons';
            $option = 'use_for_' . $object_type . '_' . $image_type;

            if (!empty($company_id)) {
                $result = Settings::instance()->getValue($option, 'watermarks', $company_id) == 'Y';
            } else {
                $result = Registry::get('addons.watermarks.' . $option) == 'Y';
            }
        }

        $cache[$object_type . $company_id . '_' . $is_detailed] = $result;

    } else {
        $result = $cache[$object_type . $company_id . '_' . $is_detailed];
    }

    fn_set_hook('is_need_watermark_post', $object_type, $is_detailed, $company_id, $result);

    return $result;
}

function fn_watermarks_generate_thumbnail_file_pre(&$image_path, &$lazy)
{
    if ($lazy == true) {
        return true;
    }

    if (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.company_id')) {
        $pattern = '/^(.*)' . preg_quote(WATERMARKS_DIR_NAME, '/') . '[0-9]+\/(.*)$/';
    } else {
        $pattern = '/^(.*)' . preg_quote(WATERMARKS_DIR_NAME, '/') . '(.*)$/';
    }

    if (preg_match($pattern, $image_path, $matches)) {
        $image_path = $matches[1] . $matches[2];
    }

    return true;
}

function fn_watermarks_update_company(&$company_data, &$company_id, &$lang_code, &$action)
{
    if ($action == 'add') {
        // Clone watermark images
        $clone_from = !empty($company_data['clone_from']) && $company_data['clone_from'] != 'all' ? $company_data['clone_from'] : null;

        if (!is_null($clone_from)) {
            if (!empty($company_id)) {
                $clone_to = $company_id;
                $image_pair = fn_get_image_pairs($clone_from, 'watermark', 'M');
            } else {
                $clone_to = WATERMARK_IMAGE_ID;
                $image_pair = fn_get_image_pairs(WATERMARK_IMAGE_ID, 'watermark', 'M');
            }

            if (!empty($image_pair)) {
                fn_clone_image_pairs($clone_to, $clone_from, 'watermark');
            }
        } else {
            // check if company options are valid
            $option_types = fn_get_apply_watermark_options();

            foreach ($option_types as $type => $options) {
                foreach ($options as $name => $option_id) {
                    $image_name = ($type == 'icons') ? 'icon' : 'detailed';

                    Settings::instance($company_id)->updateValueById($option_id, 'N', $company_id);
                }
            }
        }
    }
}

function fn_is_watermarks_enabled($company_id = null)
{
    $settings = fn_get_watermark_settings($company_id);
    $enabled = true;

    if (empty($settings) || ($settings['type'] == 'T' && empty($settings['text']))) {
        $enabled = false;
    } elseif ($settings['type'] == 'G' && empty($settings['image_pair'])) {
        $enabled = false;
    }

    return $enabled;
}

function fn_watermarks_generate_thumbnail_post(&$relative_path, &$lazy)
{
    static $init_cache = false;

    $image_path_info = fn_pathinfo($relative_path);
    $image_name = $image_path_info['filename'];

    $key = 'wt_data_' . fn_crc32($image_name);

    $condition = array('images', 'images_links');
    if (fn_allowed_for('ULTIMATE')) {
        $condition[] = 'products';
        $condition[] = 'categories';
    }

    $cache_name = 'watermarks_cache_static';

    if (!$init_cache) {
        Registry::registerCache($cache_name, $condition, Registry::cacheLevel('static'), true);
        $init_cache = true;
    }

    $image_data = Registry::get($cache_name . '.' . $key);

    if (empty($image_data)) {
        $image_data = db_get_row("SELECT l.* FROM ?:images AS i, ?:images_links AS l WHERE (l.image_id = i.image_id OR detailed_id = i.image_id) AND image_path LIKE ?l", $image_name . '.%');

        if (empty($image_data)) {
            return true;
        }

        if (fn_allowed_for('ULTIMATE')) {
            $image_data['company_id'] = fn_wt_get_image_company_id($image_data);
        }

        Registry::set($cache_name . '.' . $key, $image_data);
    }

    $company_id = null;
    if (fn_allowed_for('ULTIMATE')) {
        $company_id = Registry::get('runtime.company_id');
        if ($company_id == null) {
            $company_id = $image_data['company_id'];
        }
    }

    if (!empty($image_data['object_type']) && fn_is_need_watermark($image_data['object_type'], $image_data['object_type'] == 'detailed', $company_id)) {

        $prefix = WATERMARKS_DIR_NAME;
        if (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.company_id')) {
            $prefix = WATERMARKS_DIR_NAME . $company_id . '/';
        }

        $hidpi_path = '';
        fn_set_hook('generate_hidpi_name', $relative_path, $hidpi_path);

        if (!empty($hidpi_path)) {
            if (!$lazy && Storage::instance('images')->isExist($relative_path) && !Storage::instance('images')->isExist($prefix . $hidpi_path)) {
                fn_watermark_create($relative_path, $prefix . $hidpi_path, false, $company_id);
            }
        }

        if (!$lazy && !Storage::instance('images')->isExist($prefix . $relative_path)) {
            fn_watermark_create($relative_path, $prefix . $relative_path, false, $company_id);
        }

        $relative_path = $prefix . $relative_path;
    }

    return true;
}

/**
 * Return company id by image data
 * @param array $image_data
 * @return int
 */
function fn_wt_get_image_company_id($image_data)
{
    static $object_companies = array();

    $object_type = (isset($image_data['object_type'])) ? $image_data['object_type'] : '';
    $object_id = (isset($image_data['object_id'])) ? $image_data['object_id'] : '';

    if (isset($object_companies[$object_type][$object_id])) {
        return $object_companies[$object_type][$object_id];
    }

    if ($object_type == 'category') {
        $company_id = db_get_field("SELECT company_id FROM ?:categories WHERE category_id = ?i", $object_id);
    } elseif ($object_type == 'product') {
        $company_id = db_get_field("SELECT company_id FROM ?:products WHERE product_id = ?i", $object_id);
    } elseif ($object_type == 'variant_image') {
        $company_id = db_get_field("SELECT company_id FROM ?:product_option_variants AS ov LEFT JOIN ?:product_options AS po ON ov.option_id = po.option_id WHERE ov.variant_id = ?i", $object_id);
    } elseif ($object_type == 'product_option') {
        $company_id = db_get_field("SELECT company_id FROM ?:product_options_inventory AS pi LEFT JOIN ?:products AS p ON pi.product_id = p.product_id WHERE pi.combination_hash = ?i", $object_id);
    } else {
        // take any company_id
        $company_id = db_get_field("SELECT company_id FROM ?:companies LIMIT 1");
    }

    $object_companies[$object_type][$object_id] = $company_id;

    return $company_id;
}

function fn_watermarks_attach_absolute_image_paths(&$image_data, &$object_type, &$path, &$image_name)
{
    if (!empty($image_data['image_path'])) {
        $is_detailed = ($object_type == 'detailed') ? true : false;
        $company_id = null;

        if (empty($image_data['object_type'])) {
            $image_data['object_type'] = $object_type;
        }

        $prefix = WATERMARKS_DIR_NAME;
        if (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.company_id')) {
            $company_id = fn_wt_get_image_company_id($image_data);
            $prefix = WATERMARKS_DIR_NAME . $company_id . '/';
        }

        if (fn_is_need_watermark($image_data['object_type'], $is_detailed, $company_id)) {
            $image_data['http_image_path'] = Storage::instance('images')->getUrl($prefix . $path . '/' . $image_name, 'http');
            $image_data['absolute_path'] = Storage::instance('images')->getAbsolutePath($prefix . $path . '/' . $image_name);
            $image_data['image_path'] = Storage::instance('images')->getUrl($prefix . $path . '/' . $image_name);

            $hidpi_path = '';
            $relative_path = $image_data['relative_path'];
            fn_set_hook('generate_absolute_hidpi_name', $relative_path, $hidpi_path);

            if (!empty($hidpi_path)) {
                if (Storage::instance('images')->isExist($relative_path) && !Storage::instance('images')->isExist($prefix . $hidpi_path)) {
                    fn_watermark_create($relative_path, $prefix . $hidpi_path, $is_detailed, $company_id);
                }
            }

            if (!Storage::instance('images')->isExist($prefix . $path . '/' . $image_name) && Storage::instance('images')->isExist($image_data['relative_path'])) {
                fn_watermark_create($image_data['relative_path'], $prefix . $path . '/' . $image_name, $is_detailed, $company_id);
            }
        }
    }

    return true;
}

/**
 * Delete watermarked images before deleteing image pair
 *
 * @param int $image_id Image identifier
 * @param int $pair_id Pair identifier
 * @param string $object_type Object type
 * @param string $image_file Deleted image file
 * @return boolean Always true
 */
function fn_watermarks_delete_image(&$image_id, &$pair_id, &$object_type, &$image_file)
{
    $dir = WATERMARKS_DIR_NAME;
    if (fn_allowed_for('ULTIMATE')) {
        $dir = 'watermarked/*/';
    }

    fn_delete_image_thumbnails($image_file, $dir);

    return true;
}

function fn_watermarks_get_route(&$req, &$result, &$area, &$is_allowed_url)
{
    if (!empty($req['dispatch']) && $req['dispatch'] == 'watermark.create') {
        $is_allowed_url = true;
    }
}

function fn_watermark_create(
    $source_filepath,
    $target_filepath,
    $is_detailed = false,
    $company_id = null,
    $generate_watermark = true
)
{
    $original_abs_path = Storage::instance('images')->getAbsolutePath($source_filepath);

    list(, , , $original_abs_path) = fn_get_image_size($original_abs_path);

    if (!$generate_watermark) {
        Storage::instance('images')->put($target_filepath, array(
            'file' => $original_abs_path,
            'keep_origins' => true
        ));

        return true;
    }

    $settings = fn_get_watermark_settings($company_id);

    if (empty($settings)) {
        return false;
    }

    list($settings['horizontal_position'], $settings['vertical_position']) = explode('_', $settings['position']);

    gc_collect_cycles();

    /** @var \Imagine\Image\ImagineInterface $imagine */
    $imagine = Tygh::$app['image'];

    try {
        $image = $imagine->open($original_abs_path);

        fn_catch_exception(function () use ($image) {
            $image->usePalette(new \Imagine\Image\Palette\RGB());
        });

        $filter = ($imagine instanceof \Imagine\Gd\Imagine)
            ? \Imagine\Image\ImageInterface::FILTER_UNDEFINED
            : \Imagine\Image\ImageInterface::FILTER_LANCZOS;

        if ($settings['type'] == WATERMARK_TYPE_GRAPHIC) {
            $watermark_image_file_path = false;
            if ($is_detailed) {
                if (!empty($settings['image_pair']['detailed']['absolute_path'])) {
                    $watermark_image_file_path = $settings['image_pair']['detailed']['absolute_path'];
                }
            } elseif (!empty($settings['image_pair']['icon']['absolute_path'])) {
                $watermark_image_file_path = $settings['image_pair']['icon']['absolute_path'];
            }

            if (!$watermark_image_file_path) {
                return false;
            }

            list(, , , $watermark_image_file_path) = fn_get_image_size($watermark_image_file_path);

            $watermark_image = $imagine->open($watermark_image_file_path);

            fn_catch_exception(function () use ($watermark_image) {
                $watermark_image->usePalette(new \Imagine\Image\Palette\RGB());
            });

            // Watermark image > canvas image
            $watermark_size = $watermark_image->getSize()->increase(WATERMARK_PADDING * 2);

            if (!$image->getSize()->contains($watermark_size)) {
                $ratio = min(array(
                    $image->getSize()->getWidth() / $watermark_size->getWidth(),
                    $image->getSize()->getHeight() / $watermark_size->getHeight(),
                ));
                $watermark_image->resize($watermark_size->scale($ratio), $filter);
            }

            $watermark_position = ImageHelper::positionLayerOnCanvas(
                $image->getSize(),
                $watermark_image->getSize(),
                $settings['horizontal_position'],
                $settings['vertical_position']
            );

            $image->paste($watermark_image, $watermark_position);

            unset($watermark_image);

        } elseif ($settings['type'] == WATERMARK_TYPE_TEXT) {
            $font_path = Registry::get('config.dir.lib') . 'other/fonts/' . $settings['font'] . '.ttf';
            $font_size = $is_detailed ? $settings['font_size_detailed'] : $settings['font_size_icon'];
            $font_alpha_blend = null;

            switch ($settings['font_color']) {
                case 'white':
                    $font_color = array(255, 255, 255);
                    break;
                case 'black':
                    $font_color = array(0, 0, 0);
                    break;
                case 'gray':
                    $font_color = array(120, 120, 120);
                    break;
                case 'clear_gray':
                default:
                    $font_color = array(120, 120, 120);
                    $font_alpha_blend = WATERMARK_FONT_ALPHA;
                    break;
            }

            // For example CMYK palette doesn't support alphachannel
            if (!$image->palette()->supportsAlpha()) {
                $font_alpha_blend = null;
            }

            $font = $imagine->font(
                $font_path,
                $font_size,
                $image->palette()->color($font_color, $font_alpha_blend)
            );

            $text_layer_size = ImageHelper::calculateTextSize(
                $settings['text'],
                $font
            );

            $watermark_position = ImageHelper::positionLayerOnCanvas(
                $image->getSize(),
                $text_layer_size,
                $settings['horizontal_position'],
                $settings['vertical_position'],
                new \Imagine\Image\Box(WATERMARK_PADDING, WATERMARK_PADDING)
            );

            $image->draw()->text($settings['text'], $font, $watermark_position);

            unset($font);
        }

        $settings = Settings::instance()->getValues('Thumbnails');
        $options = array(
            'jpeg_quality' => $settings['jpeg_quality'],
            'png_compression_level' => 9,
            'filter' => $filter
        );

        if ($original_file_type = fn_get_image_extension(fn_get_mime_content_type($original_abs_path, false))) {
            $format = $original_file_type;
        } else {
            $format = 'png';
        }

        Storage::instance('images')->put($target_filepath, array(
            'contents' => $image->get($format, $options)
        ));

        unset($image);

        gc_collect_cycles();

        return true;
    } catch (\Exception $e) {
        $error_message = __('error_unable_to_create_thumbnail', array(
            '[error]' => $e->getMessage(),
            '[file]' => $source_filepath
        ));

        if (AREA == 'A') {
            fn_set_notification('E', __('error'), $error_message);
        }

        gc_collect_cycles();

        return false;
    }
}

/**
 * @deprecated
 * @since 4.3.1
 */
function fn_create_image_from_file($path, $mime_type)
{
    $ext = fn_get_image_extension($mime_type);

    if ($ext == 'gif') {
        $image = imagecreatefromgif($path);
    } elseif ($ext == 'jpg') {
        $image = imagecreatefromjpeg($path);
    } elseif ($ext == 'png') {
        $image = imagecreatefrompng($path);
    } else {
        return false;
    }

    return $image;
}

function fn_watermarks_images_access_info()
{
    $is_applied = false;

    $option_types = fn_get_apply_watermark_options();
    foreach ($option_types as $options) {
        foreach ($options as $name => $option_id) {
            if (Registry::get('addons.watermarks.' . $name) == 'Y') {
                $is_applied = true;
                break;
            }
        }
    }

    if ($is_applied) {
        if (fn_allowed_for('ULTIMATE')) {
            $img_instr = "# Rewrite watermarks rules\n" .
                "<IfModule mod_rewrite.c>\n" .
                "RewriteEngine on\n" .
                "RewriteCond %{REQUEST_URI} \/images\/(product|category|detailed|thumbnails)\/*\n" .
                "RewriteCond %{REQUEST_FILENAME} -f\n" .
                "RewriteRule .(gif|jpeg|jpg|png)$ " . DIR_ROOT . fn_url('watermark.create', 'C', 'rel') . " [NC]\n" .
                "</IfModule>\n" .
                "# /Rewrite watermarks rules";
        } else {
            $img_instr = "# Rewrite watermarks rules\n" .
                "<IfModule mod_rewrite.c>\n" .
                "RewriteEngine on\n" .
                "RewriteCond %{REQUEST_URI} \/images\/(product|category|detailed|thumbnails)\/*\n" .
                "RewriteCond %{REQUEST_FILENAME} -f\n" .
                "RewriteRule (.*)$ ./watermarked/$1 [NC]\n" .
                "</IfModule>\n" .
                "# /Rewrite watermarks rules";
        }

        $img_instr = nl2br(htmlentities($img_instr));

        $wt_instr = "# Generate watermarks rules\n" .
            "<IfModule mod_rewrite.c>\n" .
            "RewriteEngine on\n" .
            "RewriteCond %{REQUEST_FILENAME} !-f\n" .
            "RewriteRule .(gif|jpeg|jpg|png)$ " . DIR_ROOT . fn_url('watermark.create', 'C', 'rel') . " [NC]\n" .
            "</IfModule>\n" .
            "# /Generate watermarks rules";
        $wt_instr = nl2br(htmlentities($wt_instr));

        $res = '<h2 class="subheader">' . __('wt_images_access_info') . '</h2>' .
            '<p>' . __('wt_images_access_description') . '</p>' .
            '<p><code>' . $img_instr . '</code></p>' .
            '<p>' . __('wt_watermarks_access_description') . '</p>' .
            '<p><code>' . $wt_instr . '</code></p>' .
            '<p>' . __('wt_access_note') . '</p>';

        return $res;
    }

    return '';
}

function fn_settings_actions_addons_post_watermarks($status)
{
    if ($status == 'D') {
        fn_clear_watermarks();
    }
}

function fn_clear_watermarks()
{
    fn_set_notification('W', __('warning'), __('wt_access_warning'));
}

/**
 * Hook handler for image_zoom addon, if detail image not proportional for thumbnail
 * @param string $file_path
 * @param array $image_data
 * @param array $images
 */
function fn_watermarks_image_zoom_check_image_post(&$file_path, &$image_data, &$images)
{
    $company_id = null;

    if (fn_allowed_for('ULTIMATE')) {
        $company_id = fn_wt_get_image_company_id($images['detailed']);
    }

    if (fn_is_need_watermark('product', false, $company_id) || !fn_is_need_watermark('product', true, $company_id)) {
        return;
    }

    $data = $images['detailed'];
    $data['relative_path'] = $file_path;
    $type = 'detailed';
    $name = basename($file_path);
    $path = dirname($file_path);

    fn_watermarks_attach_absolute_image_paths($data, $type, $path, $name);

    $file_path = $data['image_path'];
}