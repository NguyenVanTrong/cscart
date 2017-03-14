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


namespace Tygh\Tools;


use Tygh\Registry;

/**
 * Class Formatter
 * @package Tygh\Tools
 */
class Formatter
{
    /** @var string */
    public $default_datetime_format;

    /** @var array */
    protected $settings = array();

    /**
     * Formatter constructor.
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->default_datetime_format = $this->settings['Appearance']['date_format'] . ', ' . $this->settings['Appearance']['time_format'];
    }

    /**
     * Formats the value as a datetime.
     *
     * @param int           $timestamp  Unix timestamp.
     * @param null|string   $format     Output format. Use null for default format.
     *
     * @return string
     */
    public function asDatetime($timestamp, $format = null)
    {
        if ($format === null) {
            $format = $this->default_datetime_format;
        }

        return fn_date_format($timestamp, $format);
    }

    /**
     * Formats the value as a price.
     *
     * @param float     $price                      Price.
     * @param string    $currency_code              Currency code.
     * @param bool      $show_symbol
     * @param bool      $check_alternative_currency
     * @return string
     */
    public function asPrice($price, $currency_code = CART_SECONDARY_CURRENCY, $show_symbol = true, $check_alternative_currency = false)
    {
        $currency = Registry::get('currencies.' . $currency_code);

        $value = fn_format_rate_value(
            $price,
            'F',
            $currency['decimals'],
            $currency['decimals_separator'],
            $currency['thousands_separator'],
            $currency['coefficient']
        );

        if ($show_symbol) {
            if ($currency['after'] == 'Y') {
                $value = $value . '&nbsp;' . $currency['symbol'];
            } else {
                if ($value < 0) {
                    $sign = '-';
                    $value = substr($value, 1);
                } else {
                    $sign = '';
                }
                $value = $sign . $currency['symbol'] . $value;
            }
        }

        if (
            $check_alternative_currency
            && $this->settings['General']['alternative_currency'] == 'use_selected_and_alternative'
            && CART_SECONDARY_CURRENCY != CART_PRIMARY_CURRENCY
            && $currency_code == CART_SECONDARY_CURRENCY
        ) {
            $value = sprintf('%s (%s)', $this->asPrice($price, CART_PRIMARY_CURRENCY, true, false), $value);
        }

        return $value;
    }

    /**
     * Formats the value as a plain text with newlines converted into breaks.
     *
     * @param string $value
     *
     * @return string
     */
    public function asNText($value)
    {
        return nl2br($value);
    }

    /**
     * Formats the image as an html <img>.
     *
     * @param array         $image
     * @param int           $width
     * @param int           $height
     *
     * @return string
     */
    public function asImage($image, $width, $height)
    {
        $data = fn_image_to_display($image, $width, $height);

        if (!empty($data['image_path'])) {
            $result = "<img src=\"{$data['image_path']}\" width=\"{$data['width']}\" height=\"{$data['height']}\" alt=\"{$data['alt']}\" title=\"{$data['alt']}\" />";
        } else {
            if (empty($width)) {
                $width = $height;
            }

            if (empty($height)) {
                $height = $width;
            }
            $no_image = __("no_image");

            $result = "<div class=\"no-image\" style=\"width: {$width}px; height: {$height}px;\"><i class=\"glyph-image\" title=\"{$no_image}\"></i></div>";
        }

        return $result;
    }
}