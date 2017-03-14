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

function fn_get_barcode_image()
{
    if (Registry::get('addons.barcode.status') != 'D') {
        $src = fn_url("image.barcode?id=0123456789&type=" . Registry::get('addons.barcode.type') . "&width=" . Registry::get('addons.barcode.width') . "&height=" . Registry::get('addons.barcode.height') . "&xres=" . Registry::get('addons.barcode.resolution') . "&font=" . Registry::get('addons.barcode.text_font'));
        $result = "<p align='center'><img src='$src'></p>";
    } else {
        $result = __('please_enable_the_add_on_to_see_barcode');
    }

    return $result;
}

function fn_get_barcode_specification($lang_code = CART_LANGUAGE)
{
    $explanation = __(Registry::get('addons.barcode.type'), '', $lang_code);

    return "<div>$explanation</div>";
}
