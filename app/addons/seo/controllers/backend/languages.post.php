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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Settings;

if ($mode == 'manage') {
    if (fn_allowed_for('ULTIMATE')) {
        $settings_single_url = Settings::instance()->getAllVendorsValues('single_url', 'seo');
    } else {
        $setting_single_url = Settings::instance()->getValue('single_url', 'seo');
        $settings_single_url = array($setting_single_url);
    }

    if (in_array('Y', $settings_single_url)) {
        Tygh::$app['view']->assign('seo_single_url_enable', true);
    }
}
