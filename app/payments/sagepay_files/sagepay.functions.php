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

use Tygh\Registry;

function addPKCS5Padding($input)
{
    $blockSize = 16;
    $padd = "";

    // Pad input to an even block size boundary.
    $length = $blockSize - (strlen($input) % $blockSize);
    for ($i = 1; $i <= $length; $i++) {
        $padd .= chr($length);
    }

    return $input . $padd;
}

function encryptAes($string, $key)
{
    // AES encryption, CBC blocking with PKCS5 padding then HEX encoding.
    // Add PKCS5 padding to the text to be encypted.
    $string = addPKCS5Padding($string);

    // Perform encryption with PHP's MCRYPT module.
    $crypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_CBC, $key);

    // Perform hex encoding and return.
    return "@" . strtoupper(bin2hex($crypt));
}

function decryptAes($strIn, $password)
{
    // HEX decoding then AES decryption, CBC blocking with PKCS5 padding.
    // Use initialization vector (IV) set from $str_encryption_password.
    $strInitVector = $password;

    // Remove the first char which is @ to flag this is AES encrypted and HEX decoding.
    $hex = substr($strIn, 1);

    // Throw exception if string is malformed
    if (!preg_match('/^[0-9a-fA-F]+$/', $hex)) {
        //invelid key
        return false;
    }
    $strIn = pack('H*', $hex);

    // Perform decryption with PHP's MCRYPT module.
    $string = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $password, $strIn, MCRYPT_MODE_CBC, $strInitVector);

    return removePKCS5Padding($string);
}

function removePKCS5Padding($input)
{
    $blockSize = 16;
    $padChar = ord($input[strlen($input) - 1]);

    /* Check for PadChar is less then Block size */
    if ($padChar > $blockSize) {
        return false;
    }
    /* Check by padding by character mask */
    if (strspn($input, chr($padChar), strlen($input) - $padChar) != $padChar) {
        return false;
    }

    $unpadded = substr($input, 0, (-1) * $padChar);
    /* Chech result for printable characters */
    if (preg_match('/[[:^print:]]/', $unpadded)) {
        return false;
    }

    return $unpadded;
}

/**
 * Formats string to be used in Basket request field.
 *
 * @param array $order_info Order info
 * @param string $primary_currency Cart's primary currency
 * @param string $pp_currency Payment processor currency
 *
 * @return string
 */
function fn_sagepay_get_basket($order_info, $primary_currency, $pp_currency)
{
    $basket_items = array();
    $basket_item_template = '[descr]:[quantity]:[cost_wo_tax]:[tax]:[cost_w_tax]:[total]';

    if (!empty($order_info['products'])) {
        foreach ($order_info['products'] as $v) {
            $total       = $v['subtotal'] - fn_external_discounts($v);
            $cost_wo_tax = ($total - $v['tax_value']) / $v['amount'];
            $tax         = $v['tax_value'] / $v['amount'];
            $cost_w_tax  = $total / $v['amount'];
            $basket_items[] = strtr($basket_item_template,
                array(
                    '[descr]'       => str_replace(":", " ", $v['product']),
                    '[quantity]'    => (int) $v['amount'],
                    '[cost_wo_tax]' => fn_format_price_by_currency($cost_wo_tax, $primary_currency, $pp_currency),
                    '[tax]'         => fn_format_price_by_currency($tax,         $primary_currency, $pp_currency),
                    '[cost_w_tax]'  => fn_format_price_by_currency($cost_w_tax,  $primary_currency, $pp_currency),
                    '[total]'       => fn_format_price_by_currency($total,       $primary_currency, $pp_currency),
                )
            );
        }
    }
    if (!empty($order_info['gift_certificates'])) {
        foreach ($order_info['gift_certificates'] as $v) {
            $v['amount'] = (!empty($v['extra']['exclude_from_calculate'])) ? 0 : $v['amount'];
            $basket_items[] = strtr($basket_item_template,
                array (
                    '[descr]'       => str_replace(":", " ", $v['gift_cert_code']),
                    '[quantity]'    => 1,
                    '[cost_wo_tax]' => fn_format_price_by_currency($v['amount'], $primary_currency, $pp_currency),
                    '[tax]'         => 0,
                    '[cost_w_tax]'  => fn_format_price_by_currency($v['amount'], $primary_currency, $pp_currency),
                    '[total]'       => fn_format_price_by_currency($v['amount'], $primary_currency, $pp_currency),
                )
            );
        }
    }
    if (floatval($order_info['payment_surcharge'])) {
        $basket_items[] = strtr($basket_item_template,
            array (
                '[descr]'       => str_replace(":", " ", __('payment_surcharge')),
                '[quantity]'    => '---',
                '[cost_wo_tax]' => '---',
                '[tax]'         => '---',
                '[cost_w_tax]'  => '----',
                '[total]'       => fn_format_price_by_currency($order_info['payment_surcharge'], $primary_currency, $pp_currency),
            )
        );
    }
    if (fn_order_shipping_cost($order_info)) {
        $cost_w_tax = fn_order_shipping_cost($order_info);
        $tax = fn_order_shipping_taxes_cost($order_info);
        $cost_wo_tax = $cost_w_tax - $tax;
        $basket_items[] = strtr($basket_item_template,
            array (
                '[descr]'       => str_replace(":", " ", __('shipping_cost')),
                '[quantity]'    => '---',
                '[cost_wo_tax]' => fn_format_price_by_currency($cost_wo_tax, $primary_currency, $pp_currency),
                '[tax]'         => fn_format_price_by_currency($tax,         $primary_currency, $pp_currency),
                '[cost_w_tax]'  => fn_format_price_by_currency($cost_w_tax,  $primary_currency, $pp_currency),
                '[total]'       => fn_format_price_by_currency($cost_w_tax,  $primary_currency, $pp_currency),
            )
        );
    }

    if (floatval($order_info['subtotal_discount'])) {
        $basket_items[] = strtr($basket_item_template,
            array (
                '[descr]'       => str_replace(":", " ", __('order_discount')),
                '[quantity]'    => '---',
                '[cost_wo_tax]' => '---',
                '[tax]'         => '---',
                '[cost_w_tax]'  => '---',
                '[total]'       => -fn_format_price_by_currency($order_info['subtotal_discount'], $primary_currency, $pp_currency),
            )
        );
    }

    if (!empty($order_info['taxes']) && Registry::get('settings.General.tax_calculation') == 'subtotal') {
        foreach ($order_info['taxes'] as $tax_id => $tax) {
            if ($tax['price_includes_tax'] == 'N') {
                $basket_items[] = strtr($basket_item_template,
                    array (
                        '[descr]'       => str_replace(":", " ", $tax['description']),
                        '[quantity]'    => '---',
                        '[cost_wo_tax]' => '---',
                        '[tax]'         => '---',
                        '[cost_w_tax]'  => '---',
                        '[total]'       => fn_format_price_by_currency($tax['tax_subtotal'], $primary_currency, $pp_currency),
                    )
                );
            }
        }
    }

    return sprintf('%d:%s',
        count($basket_items),
        implode(':', $basket_items)
    );
}
