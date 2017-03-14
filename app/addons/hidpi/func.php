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

use Tygh\Storage;
use Tygh\Registry;

/**
 * Hook: generates low-resolution image from HiDPI one
 * @param array $image_data image data
 * @param int $image_id image ID
 * @param string $image_type type of an object image belongs to (product, category, etc.)
 * @param string $images_path path to directory image is located at
 * @param array $_data data to be saved into "images" DB table
 * @param string $mime_type MIME type of an image file
 * @param bool $is_clone true if image is copied from an existing image object
 */
function fn_hidpi_update_image(&$image_data, $image_id, $image_type, $images_path, &$_data, $mime_type, $is_clone)
{
    /** @var \Tygh\Backend\Storage\ABackend $storage */
    $storage = Storage::instance('images');

    if (isset($image_data['old_name'])) {
        fn_hdpi_delete($images_path, $image_data['old_name']);
    }

    $source_name = $image_data['name'];
    $end_fullname = $storage->generateName($images_path . $image_data['name']);
    $image_data['name'] = basename($end_fullname);

    if ($is_clone) {
        $original_path = $image_type . '/' . fn_get_image_subdir($image_data['clone_from']) . '/';
        $relative_path = $original_path . fn_hdpi_form_name($source_name);
        if ($storage->isExist($relative_path)) {
            $hdpi_source_fullname = fn_create_temp_file();
            $storage->export($relative_path, $hdpi_source_fullname);
            fn_hdpi_copy($hdpi_source_fullname, $images_path, $image_data['name']);
        }
    } else {
        fn_hdpi_copy($image_data['path'], $images_path, $image_data['name']);
        fn_hdpi_shrink_original($_data, $image_data);
    }
}

function fn_hidpi_generate_hidpi_name($image_path, &$hidpi_path)
{
    $hidpi_path = fn_hdpi_form_name($image_path);
}

function fn_hidpi_generate_absolute_hidpi_name(&$image_path, &$hidpi_path)
{
    $hidpi_path = fn_hdpi_form_name($image_path);

    if (Storage::instance('images')->isExist($image_path)) {
        $image_path = $hidpi_path;
    }
}

/**
 * Hook: generates HiDPI image during thumbnail generation
 * @param string $image_path
 * @param boolean $lazy
 * @param string $filename
 * @param int $width
 * @param int $height
 */
function fn_hidpi_generate_thumbnail_file_pre($image_path, $lazy, $filename, $width, $height)
{
    if ($lazy == false) {
        list(, , ,$tmp_path) = fn_get_image_size(fn_hdpi_form_name(Storage::instance('images')->getAbsolutePath($image_path)));

        if (!empty($tmp_path)) {
            $back_color = Registry::get('settings.Thumbnails.thumbnail_background_color');
            list($content, $format) = fn_resize_image($tmp_path, $width * 2, $height * 2, $back_color);

            if (!empty($content)) {
                $hdpi_name = fn_hdpi_form_name($filename, $format);
                Storage::instance('images')->put($hdpi_name, array(
                    'contents' => $content,
                    'caching' => true,
                    'keep_origins' => true
                ));
            }
        }
    }
}

/**
 * Hook: deletes HiDPI image
 * @param int $image_id
 * @param int $pair_id
 * @param string $object_type
 * @param string $_image_file
 */
function fn_hidpi_delete_image($image_id, $pair_id, $object_type, $_image_file)
{
    Storage::instance('images')->delete(fn_hdpi_form_name($_image_file));
}

/**
 * Hook: gets images host
 * @param object $view templater object
 */
function fn_hidpi_init_templater_post(&$view)
{
    $url = Storage::instance('images')->getUrl();
    $view->assign('hidpi_image_host', parse_url($url, PHP_URL_HOST));
}

/**
 * Generates name for HiDPI image
 * @param string $filename original file name
 * @param string $extension target image extension, if empty - original extension used
 * @return string generated name
 */
function fn_hdpi_form_name($filename, $extension = '')
{
    list($filename) = explode('?', $filename);

    if (!empty($extension)) {
        $filename = substr_replace($filename, '.' . $extension, strrpos($filename, '.'));      
    }

    return  substr_replace($filename, '@2x.', strrpos($filename, '.'), 1);
}

/**
 * Deletes HiDPI image if exists
 * @param string $path
 * @param string $name
 */
function fn_hdpi_delete($path, $name)
{
    /** @var \Tygh\Backend\Storage\ABackend $storage */
    $storage = Storage::instance('images');

    $hdpi_name = fn_hdpi_form_name($name);
    $old_path = $path . $hdpi_name;
    if ($storage->isExist($old_path)) {
        $storage->delete($old_path);
    }
}

/**
 * Copies image as HiDPI
 * @param string $source_name source file name 
 * @param string $target_path target file path
 * @param string $target_name target non-hidpi file name
 */
function fn_hdpi_copy($source_name, $target_path, $target_name)
{
    /** @var \Tygh\Backend\Storage\ABackend $storage */
    $storage = Storage::instance('images');
    
    $hdpi_name = fn_hdpi_form_name($target_name);
    
    $storage->put($target_path . $hdpi_name, array(
        'file' => $source_name,
        'keep_origins' => true
    ));
}

/**
 * Shrinks original image to non-hidpi resolution
 * @param array $_data data to be saved into "images" DB table
 * @param array $image_data image data
 */
function fn_hdpi_shrink_original(&$_data, &$image_data)
{
    $ext = fn_get_file_ext($image_data['name']);

    // We should not process ICO files
    if ($ext == 'ico') {
        return;
    }

    $_data['image_x'] = intval($_data['image_x'] / 2);
    $_data['image_y'] = intval($_data['image_y'] / 2);

    $back_color = Registry::get('settings.Thumbnails.thumbnail_background_color');
    list($content, ) = fn_resize_image($image_data['path'], $_data['image_x'], $_data['image_y'], $back_color);
    fn_put_contents($image_data['path'], $content);
}

/**
 * Show message on install addon
 */
function fn_hidpi_install()
{
    fn_set_notification('W',__('warning'), __('text_hidpi_install'));
}

/**
 * Show message on uninstall addon
 */
function fn_hidpi_uninstall()
{
    fn_set_notification('W',__('warning'), __('text_hidpi_uninstall'));
}
