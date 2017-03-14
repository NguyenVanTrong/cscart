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
use Tygh\BlockManager\RenderManager;
use Tygh\Settings;
use Tygh\Storage;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    //
    // Delete image
    //
    if ($mode == 'delete_image') {
        if (AREA == 'A' && !empty($auth['user_id'])) {
            fn_delete_image($_REQUEST['image_id'], $_REQUEST['pair_id'], $_REQUEST['object_type']);
            if (defined('AJAX_REQUEST')) {
                Tygh::$app['ajax']->assign('deleted', true);
            } elseif (!empty($_SERVER['HTTP_REFERER'])) {
                return array(CONTROLLER_STATUS_REDIRECT, $_SERVER['HTTP_REFERER']);
            }
        }
        exit;
    }

    //
    // Delete image pair
    //
    if ($mode == 'delete_image_pair') {
        if (AREA == 'A' && !empty($auth['user_id'])) {
            fn_delete_image_pair($_REQUEST['pair_id'], $_REQUEST['object_type']);
            if (defined('AJAX_REQUEST')) {
                Tygh::$app['ajax']->assign('deleted', true);
            }
        }
        exit;
    }
    return;
}

if ($mode == 'custom_image') {
    if (empty($_REQUEST['image'])) {
        exit();
    }

    $type = empty($_REQUEST['type']) ? 'T' : $_REQUEST['type'];

    $image_path = 'sess_data/' . fn_basename($_REQUEST['image']);

    if (Storage::instance('custom_files')->isExist($image_path)) {
        $real_path = Storage::instance('custom_files')->getAbsolutePath($image_path);
        list(, , $image_type, $tmp_path) = fn_get_image_size($real_path);

        if (empty($image_type)) {
            exit();
        }
        
        if ($type == 'T') {
            $thumb_path = $image_path . '_thumb';

            if (!Storage::instance('custom_files')->isExist($thumb_path)) {
                // Output a thumbnail image
                list($cont, $format) = fn_resize_image($tmp_path, Registry::get('settings.Thumbnails.product_lists_thumbnail_width'), Registry::get('settings.Thumbnails.product_lists_thumbnail_height'), Registry::get('settings.Thumbnails.thumbnail_background_color'));

                if (!empty($cont)) {
                    Storage::instance('custom_files')->put($thumb_path, array(
                        'contents' => $cont
                    ));
                }
            }

            $real_path = Storage::instance('custom_files')->getAbsolutePath($thumb_path);
        }

        header('Content-type: ' . $image_type);
        fn_echo(fn_get_contents($real_path));

        exit();
    }

    // Not image file. Display spacer instead.
    header('Content-type: image/gif');
    readfile(fn_get_theme_path('[themes]/[theme]') . '/media/images/spacer.gif');

    exit();

} elseif ($mode == 'thumbnail') {
    if (!Registry::get('config.tweaks.lazy_thumbnails')) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    /** @var \Tygh\Backend\Storage\ABackend $image_storage */
    $image_storage = Storage::instance('images');
    $file_path = fn_generate_thumbnail($_REQUEST['image_path'], $_REQUEST['w'], $_REQUEST['h'], false, true);

    if ($image_storage->isExist($file_path)) {
        $file_path = $image_storage->getAbsolutePath($file_path);

        header('Content-type: ' . fn_get_file_type($file_path));
        fn_echo(fn_get_contents($file_path));
    }

    exit();
}
