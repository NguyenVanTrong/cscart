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
use Tygh\Storage;
use Tygh\Bootstrap;
use Tygh\ElFinder\Core;
use Tygh\ElFinder\Connector;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (Registry::get('config.demo_mode')) {
    // ElFinder should not work in demo mode
    $message = json_encode(array('error' => __('error_demo_mode')));
    exit($message);
}

if (AREA == 'C') {
    if (!Registry::get('runtime.customization_mode.live_editor')) {
        die('Access denied');
    }
}

$private_files_path = fn_get_files_dir_path();
$public_files_path = fn_get_public_files_path();

fn_mkdir($private_files_path);
fn_mkdir($public_files_path);

$start_path = '';
if (!empty($_REQUEST['init']) && !empty($_REQUEST['start_path'])) {
    unset($_GET['target']);
    $start_path = fn_normalize_path($private_files_path . $_REQUEST['start_path']);
    if (strpos($start_path, $private_files_path) !== 0) {
        $start_path = '';
    }
}

$extra_path = str_replace(Storage::instance('images')->getAbsolutePath(''), '', $public_files_path);

$opts = array(
    'roots' => array(
        array(
            'driver' => 'Tygh\ElFinder\Volume',
            'uploadDeny' => Registry::get('config.forbidden_mime_types'),
            'fileMode' => DEFAULT_FILE_PERMISSIONS,
            'dirMode' => DEFAULT_DIR_PERMISSIONS,
            'uploadMaxSize' => Bootstrap::getIniParam('upload_max_filesize', true),
            'alias' => __('private_files'),
            'tmbPath' => '',
            'path' => $private_files_path,
            'startPath' => $start_path,
            'mimeDetect' => 'internal',
            'archiveMimes' => array(
                'application/zip'
            ),
            'icon' => Registry::get('config.current_location') .'/js/lib/elfinder/img/volume_icon_local.png'
        ),
        array(
            'driver' => 'Tygh\ElFinder\Volume',
            'uploadDeny' => Registry::get('config.forbidden_mime_types'),
            'fileMode' => DEFAULT_FILE_PERMISSIONS,
            'dirMode' => DEFAULT_DIR_PERMISSIONS,
            'uploadMaxSize' => Bootstrap::getIniParam('upload_max_filesize', true),
            'alias' => __('public_files'),
            'tmbPath' => '',
            'path' => $public_files_path,
            'URL' => Storage::instance('images')->getUrl($extra_path),
            'mimeDetect' => 'internal',
            'archiveMimes' => array(
                'application/zip'
            ),
            'icon' => Registry::get('config.current_location') .'/js/lib/elfinder/img/volume_icon_local.png'
        ),
    )
);

if ($mode == 'images') {
    unset($opts['roots'][0]);
}

$connector = new Connector(new Core($opts));
$connector->run();
exit;
