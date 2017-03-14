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

use \Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_settings_variants_addons_myob_currency()
{
    return fn_get_simple_currencies();
}

function fn_myob_set_default_settings()
{
    $currencies = array_keys(fn_get_simple_currencies());
    $default_currency = reset($currencies);
    Settings::instance()->updateValue('currency', $default_currency, 'myob');
}
