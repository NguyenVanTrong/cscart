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

defined('BOOTSTRAP') or die('Access denied');

if ($mode == 'barcode') {
    $value_to_encode = empty($_REQUEST['id']) ? null : $_REQUEST['id'];
    $width = empty($_REQUEST['width']) ? null : $_REQUEST['width'];
    $height = empty($_REQUEST['height']) ? null : $_REQUEST['height'];
    $algorithm = empty($_REQUEST['type']) ? null : $_REQUEST['type'];

    $barcode_prefix = Registry::get('addons.barcode.prefix');

    $value_to_encode = $barcode_prefix . $value_to_encode;

    $output_image_format = Registry::get('addons.barcode.output');
    $print_text_on_image = (Registry::get('addons.barcode.text') == 'Y');

    /** @var \Tygh\Tools\Barcode\Generator $barcode_generator */
    $barcode_generator = Tygh::$app['addons.barcode.generator'];

    $image = $barcode_generator->createBarcode(
        (int) $width, (int) $height,
        $value_to_encode,
        $algorithm,
        $print_text_on_image
    );

    header("Content-type: image/{$output_image_format}");

    echo $image->get($output_image_format, array(
        'flatten' => true,
        'png_compression_level' => 7,
        'jpeg_quality' => 90,
    ));

    exit;
}
