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
use ExTwigmo\Core\TwigmoSettings;

/**
 * Gets mobile product url
 *
 * @param $product_id
 * @param string $lang_code
 * @return bool
 */
function fn_ex_twg_exim_get_product_mobile_url($product_id, $lang_code = '')
{
    $company_id = 0;
    $company_url = '';

    if (fn_allowed_for('ULTIMATE')) {
        if (Registry::get('runtime.company_id')) {
            $company_id = Registry::get('runtime.company_id');
        } else {
            $company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);
        }

        $company_url = '&company_id=' . $company_id;
    } else {
        $company_url = '';
    }

    $settings = TwigmoSettings::get('customer_connections.' . $company_id);
    $use_twg = !empty($settings['access_id']) && ($settings['use_for_phones'] == 'Y' || $settings['use_for_tablets'] == 'Y');
    if ($use_twg && fn_ex_twg_use_https_for_customer($company_id)) {
        $protocol = 'https';
    } else {
        $protocol = 'http';
    }

    $url = fn_url('products.view?product_id=' . $product_id . $company_url, 'C', $protocol, $lang_code);

    fn_set_hook('exim_get_product_url', $url, $product_id, $options, $lang_code);

    return $url;
}
