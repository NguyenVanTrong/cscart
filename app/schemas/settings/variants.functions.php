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
use Tygh\Tools\DateTimeHelper;

/**
 * Get languages list for customer language
 */
function fn_settings_variants_appearance_frontend_default_language()
{
    return fn_get_simple_languages();
}

/**
 * Get languages list for admin language
 */
function fn_settings_variants_appearance_backend_default_language()
{
    return fn_get_simple_languages(true);
}

/**
 * Get available formats, supported by currently used image manipulation library
 */
function fn_settings_variants_thumbnails_convert_to()
{
    return fn_get_supported_image_format_variants();
}

/**
 * Get list of objects, available to search through
 */
function fn_settings_variants_general_search_objects()
{
    return fn_search_get_objects();
}

function fn_settings_variants_appearance_default_products_sorting()
{
    return fn_settings_variants_appearance_available_product_list_sortings();
}

function fn_settings_variants_appearance_default_products_view()
{
    return fn_get_products_views(true, true);
}

function fn_settings_variants_appearance_default_products_view_templates()
{
    return fn_get_products_views(true);
}

function fn_settings_variants_appearance_default_product_details_view()
{
    return fn_get_product_details_views();
}

function fn_settings_variants_appearance_default_wysiwyg_editor()
{
    $editors_path = Registry::get('config.dir.root') . '/js/tygh/editors';
    $editors = fn_get_dir_contents($editors_path , false, true, 'js');

    $return = array();
    foreach ($editors as $editor) {
        $is_disabled = fn_get_file_description($editors_path . '/' . $editor, 'disabled', true);
        if ($is_disabled == 'Y') {
            continue;
        }

        $editor_description = fn_get_file_description($editors_path . '/' . $editor, 'editior-description');
        $return[fn_basename($editor, '.editor.js')] = $editor_description;
    }

    return $return;
}

function fn_settings_variants_appearance_default_image_previewer()
{
    $previewers_path = Registry::get('config.dir.root') . '/js/tygh/previewers';
    $previewers = fn_get_dir_contents($previewers_path, false, true, 'js');

    $return = array();
    foreach ($previewers as $previewer) {
        $previewer_description = fn_get_file_description($previewers_path . '/' . $previewer, 'previewer-description');
        $return[fn_basename($previewer, '.previewer.js')] = $previewer_description;
    }

    return $return;
}

/**
 * Gets settings variants for 'Available product list sortings' option
 *
 * @return array Possible sortings for product list
 */
function fn_settings_variants_appearance_available_product_list_sortings()
{
    $sortings = fn_get_products_sorting();
    $orders = fn_get_products_sorting_orders();

    $return = array();

    foreach ($sortings as $option => $info) {
        foreach ($orders as $order) {
            if (!isset($info[$order]) || $info[$order] !== false) {
                $label = 'sort_by_' . $option . '_' . $order;
                $return[$option . '-' . $order] = __($label);
            }
        }
    }

    return $return;
}

/**
 * Gets settings variants for the option 'Image verification: Use for'
 *
 * @return array Available objects
 */
function fn_settings_variants_image_verification_use_for()
{
    $objects = array(
        'login' => __('use_for_login'),
        'register' => __('use_for_register'),
        'checkout' => __('use_for_checkout'),
        'track_orders' => __('use_for_track_orders'),
    );

    /**
     * Add objects that should use 'Image verification'
     *
     * @param array $objects Available objects
     */
    fn_set_hook('settings_variants_image_verification_use_for', $objects);

    return $objects;
}

/**
 * Post processing of the time zone variants.
 *
 * @return array
 */
function fn_settings_variants_appearance_timezone_post($variants)
{
    $timezones_offsets = array();

    foreach ($variants as $timezone => &$variant_name) {
        try {
            $offset = DateTimeHelper::getTimeZoneOffset($timezone);
        } catch (Exception $e) {
            $offset = false;
        }

        $timezones_offsets[$timezone] = $offset;

        if ($offset !== false) {
            $offset_string = $offset != 0 ? DateTimeHelper::formatTimeZoneOffsetString($offset) : '';
            $variant_name = preg_replace("/\(GMT.*?\)/", "(GMT{$offset_string})", $variant_name);
        }
    }
    unset($variant_name);

    uksort($variants, function ($timezone_a, $timezone_b) use ($timezones_offsets) {
        $offset_a = $timezones_offsets[$timezone_a];
        $offset_b = $timezones_offsets[$timezone_b];

        if ($offset_a == $offset_b) {
            return 0;
        }

        return $offset_a > $offset_b ? -1 : 1;
    });

    return $variants;
}