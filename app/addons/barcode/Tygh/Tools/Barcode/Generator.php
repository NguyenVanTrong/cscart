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

namespace Tygh\Tools\Barcode;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\PaletteInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Tygh\Exceptions\DeveloperException;
use Tygh\Tools\ImageHelper;
use Tygh\Tools\Math;

/**
 * Class Generator is intended to be used as a barcode generation service, available at the application container:
 *
 * Tygh::$app['addons.barcode.generator']
 *
 * Based on excellent David Tufts' article and example code: https://github.com/davidscotttufts/php-barcode
 *
 * @package Tygh\Tools\Barcode
 */
class Generator
{
    const ALGO_CODE128 = 'C128';
    const ALGO_CODE128A = 'C128A';
    const ALGO_CODE128B = 'C128B';
    const ALGO_CODE128C = 'C128C';
    const ALGO_CODE39 = 'C39';
    const ALGO_CODE25 = 'I25';
    const ALGO_CODABAR = 'CODABAR';

    /**
     * @var ImagineInterface Imagine instance
     */
    protected $imagine;

    /**
     * @var PaletteInterface Palette to be used
     */
    protected $palette;

    /**
     * @var ColorInterface White color instance
     */
    protected $white_color;

    /**
     * @var ColorInterface Black color instance
     */
    protected $black_color;

    /**
     * @var null|string Path to the font file which will be used for applying encoded value to the barcode image
     */
    protected $font_file_path;

    /**
     * Generator constructor.
     *
     * @param ImagineInterface $imagine        Imagine instance
     * @param string|null      $font_file_path Path to the font file which will be used for applying encoded value to
     *                                         the barcode image
     */
    public function __construct(ImagineInterface $imagine, $font_file_path = null)
    {
        $this->imagine = $imagine;

        $this->palette = new RGB();
        $this->white_color = $this->palette->color(array(255, 255, 255));
        $this->black_color = $this->palette->color(array(0, 0, 0));

        $this->font_file_path = $font_file_path;
    }

    /**
     * Generates a barcode
     *
     * @param int    $desired_image_width_px  Desired barcode image width, px
     * @param int    $desired_image_height_px Desired barcode image height, px
     * @param string $value_to_encode         Value to be encoded
     * @param string $algorithm               Algorithm to be used (code128, code128a, code128b, code39, code25,
     *                                        codabar), i.e. one of the ALGO_* consts
     * @param bool   $print_text_on_image     Whether to add value to be encoded as text layer to the barcode image
     *
     * @return ImageInterface Instance of an image
     */
    public function createBarcode(
        $desired_image_width_px,
        $desired_image_height_px,
        $value_to_encode,
        $algorithm,
        $print_text_on_image = false
    ) {
        list($encoded_value, $value_to_encode) = $this->generateCodeString($value_to_encode, $algorithm);
        $sum_of_bar_widths = $this->calculateSumOfBarWidths($encoded_value);

        list($single_bar_width_px, $desired_image_width_px, $quiet_zone_width_px) = $this->calculateWidths(
            $desired_image_width_px,
            $sum_of_bar_widths
        );

        $image = $this->imagine->create(
            new Box($desired_image_width_px, $desired_image_height_px),
            $this->white_color
        );

        $bar_height_px = $desired_image_height_px;

        if ($print_text_on_image) {
            $bar_height_px = $this->printTextOnCanvas($image, $value_to_encode, $desired_image_height_px);
        }

        $this->drawBarsOnCanvas($image, $encoded_value, $single_bar_width_px, $quiet_zone_width_px, $bar_height_px);

        return $image;
    }

    /**
     * Generates encoded string representation of a given value.
     *
     * @param string $value_to_encode Value that should be encoded
     * @param string $algorithm       Algorithm to be used (code128, code128a, code128b, code39, code25, codabar)
     *
     * @throws DeveloperException When unknown algorithm is passed
     *
     * @return array [
     *    0 => Encoded value - a sequence of numbers [1-4], each one represents a bar width;
     *    1 => Input value, sanitized of unsupported symbols, this is what actually been encoded;
     *  ]
     */
    protected function generateCodeString($value_to_encode, $algorithm)
    {
        switch ($algorithm) {
            case self::ALGO_CODE128:
            case self::ALGO_CODE128B:
                return $this->encodeCode128($value_to_encode);
                break;

            case self::ALGO_CODE128A:
                return $this->encodeCode128A($value_to_encode);
                break;

            case self::ALGO_CODE128C:
                return $this->encodeCode128C($value_to_encode);
                break;

            case self::ALGO_CODE39:
                return $this->encodeCode39($value_to_encode);
                break;

            case self::ALGO_CODE25:
                return $this->encodeCode25($value_to_encode);
                break;

            case self::ALGO_CODABAR:
                return $this->encodeCodabar($value_to_encode);
                break;

            default:
                throw new DeveloperException('Unknown barcode encoding algorithm.');
                break;
        }
    }

    /**
     * Calculates sum of widths of all the bars.
     *
     * @param string $code_string Encoded value string representation
     *
     * @return int Sum of widths
     */
    protected function calculateSumOfBarWidths($code_string)
    {
        $bars_total_width = 0;
        for ($current_position = 1; $current_position <= strlen($code_string); $current_position++) {
            $bars_total_width += (int) (substr($code_string, ($current_position - 1), 1));
        }

        return $bars_total_width;
    }

    /**
     * Calculates single bar width in px, quiet zone width in px and final image width in px.
     *
     * @param int $desired_image_width_px Desired with of the barcode image, px
     * @param int $bar_widths_sum         Sum of every bar width
     *
     * @return array [Single line width, px; Final image width, px, Quiet zone width, px]
     */
    protected function calculateWidths($desired_image_width_px, $bar_widths_sum)
    {
        // We can't draw lines thicker than 1px
        if ($desired_image_width_px <= $bar_widths_sum) {
            $single_bar_width_px = 1;
            $desired_image_width_px = $bar_widths_sum;
        } else {
            $single_bar_width_px = $desired_image_width_px / $bar_widths_sum;
            $single_bar_width_px = Math::floorToPrecision($single_bar_width_px, 1);
        }

        $total_bars_width_px = $bar_widths_sum * $single_bar_width_px;

        $minimal_quiet_zone_width_px = 10 * $single_bar_width_px;

        if ($desired_image_width_px < ($total_bars_width_px + ($minimal_quiet_zone_width_px * 2))) {
            $desired_image_width_px = $total_bars_width_px + ($minimal_quiet_zone_width_px * 2);
            $quiet_zone_width_px = $minimal_quiet_zone_width_px;
        } else {
            $quiet_zone_width_px = ($desired_image_width_px - $total_bars_width_px) / 2;
        }

        return array($single_bar_width_px, $desired_image_width_px, $quiet_zone_width_px);
    }

    /**
     * Applies encoded value as a text layer to the barcode image
     *
     * @param ImageInterface $image                   Image instance
     * @param string         $value_to_encode         Value which was encoded to the barcode
     * @param int            $desired_image_height_px Desired height of an image, will be used to calculate text layer
     *                                                size and position
     *
     * @return int Free space height after text layer was applied, px
     */
    protected function printTextOnCanvas(ImageInterface $image, $value_to_encode, $desired_image_height_px)
    {
        $text_vertical_offset_px = 5;

        $font = $this->imagine->font(
            $this->font_file_path,
            13,
            $this->black_color
        );

        $text_size_box = ImageHelper::calculateTextSize($value_to_encode, $font);

        $text_position = ImageHelper::positionLayerOnCanvas(
            $image->getSize(),
            $text_size_box,
            'center', 'bottom',
            new Box(1, $text_vertical_offset_px)
        );

        if ($this->imagine instanceof \Imagine\Imagick\Imagine) {
            $text_vertical_offset_px = 1;
        }

        $remaining_height_px = $desired_image_height_px
            - ($desired_image_height_px - $text_position->getY())
            - $text_vertical_offset_px;

        $image->draw()->text($value_to_encode, $font, $text_position);

        return $remaining_height_px;
    }

    /**
     * Draws bars on a canvas using given dimensions.
     *
     * @param ImageInterface $canvas              Canvas to be used
     * @param string         $code_string         Encoded value string representation
     * @param int            $single_bar_width_px Width of a single bar, px
     * @param int            $quiet_zone_width_px Width of a quiet zone, px
     * @param int            $bar_height_px       Height of bars, px
     *
     * @return void
     */
    protected function drawBarsOnCanvas(
        ImageInterface $canvas,
        $code_string,
        $single_bar_width_px,
        $quiet_zone_width_px,
        $bar_height_px
    ) {
        $location = 0 + $quiet_zone_width_px;

        for ($position = 1; $position <= strlen($code_string); $position++) {
            $current_bar_width_px = $single_bar_width_px * (int) (substr($code_string, ($position - 1), 1));

            for ($current_bar_pixel = 0; $current_bar_pixel < $current_bar_width_px; $current_bar_pixel++) {
                $canvas->draw()->line(
                    new Point($location + $current_bar_pixel, 0),
                    new Point($location + $current_bar_pixel, $bar_height_px),
                    ($position % 2 == 0) ? $this->white_color : $this->black_color
                );
            }

            $location += $current_bar_width_px;
        }
    }

    /**
     * @param $value_to_encode
     *
     * @return array [
     *    0 => Encoded value - a sequence of numbers [1-4], each one represents a bar width;
     *    1 => Input value, sanitized of unsupported symbols, this is what actually been encoded;
     *  ]
     */
    protected function encodeCode128($value_to_encode)
    {
        $encoded_value = "";

        $checksum = 104;

        // Must not change order of array elements as the checksum depends on the array's key to validate final code
        $code_array = array(
            " " => "212222",
            "!" => "222122",
            "\"" => "222221",
            "#" => "121223",
            "$" => "121322",
            "%" => "131222",
            "&" => "122213",
            "'" => "122312",
            "(" => "132212",
            ")" => "221213",
            "*" => "221312",
            "+" => "231212",
            "," => "112232",
            "-" => "122132",
            "." => "122231",
            "/" => "113222",
            "0" => "123122",
            "1" => "123221",
            "2" => "223211",
            "3" => "221132",
            "4" => "221231",
            "5" => "213212",
            "6" => "223112",
            "7" => "312131",
            "8" => "311222",
            "9" => "321122",
            ":" => "321221",
            ";" => "312212",
            "<" => "322112",
            "=" => "322211",
            ">" => "212123",
            "?" => "212321",
            "@" => "232121",
            "A" => "111323",
            "B" => "131123",
            "C" => "131321",
            "D" => "112313",
            "E" => "132113",
            "F" => "132311",
            "G" => "211313",
            "H" => "231113",
            "I" => "231311",
            "J" => "112133",
            "K" => "112331",
            "L" => "132131",
            "M" => "113123",
            "N" => "113321",
            "O" => "133121",
            "P" => "313121",
            "Q" => "211331",
            "R" => "231131",
            "S" => "213113",
            "T" => "213311",
            "U" => "213131",
            "V" => "311123",
            "W" => "311321",
            "X" => "331121",
            "Y" => "312113",
            "Z" => "312311",
            "[" => "332111",
            "\\" => "314111",
            "]" => "221411",
            "^" => "431111",
            "_" => "111224",
            "\`" => "111422",
            "a" => "121124",
            "b" => "121421",
            "c" => "141122",
            "d" => "141221",
            "e" => "112214",
            "f" => "112412",
            "g" => "122114",
            "h" => "122411",
            "i" => "142112",
            "j" => "142211",
            "k" => "241211",
            "l" => "221114",
            "m" => "413111",
            "n" => "241112",
            "o" => "134111",
            "p" => "111242",
            "q" => "121142",
            "r" => "121241",
            "s" => "114212",
            "t" => "124112",
            "u" => "124211",
            "v" => "411212",
            "w" => "421112",
            "x" => "421211",
            "y" => "212141",
            "z" => "214121",
            "{" => "412121",
            "|" => "111143",
            "}" => "111341",
            "~" => "131141",
            "DEL" => "114113",
            "FNC 3" => "114311",
            "FNC 2" => "411113",
            "SHIFT" => "411311",
            "CODE C" => "113141",
            "FNC 4" => "114131",
            "CODE A" => "311141",
            "FNC 1" => "411131",
            "Start A" => "211412",
            "Start B" => "211214",
            "Start C" => "211232",
            "Stop" => "2331112"
        );

        $code_keys = array_keys($code_array);
        $code_values = array_flip($code_keys);

        $value_to_encode = preg_replace('/[^' . preg_quote(implode('', $code_keys), '/') . ']/i', '', $value_to_encode);

        for ($position = 1; $position <= strlen($value_to_encode); $position++) {
            $active_key = substr($value_to_encode, ($position - 1), 1);
            $encoded_value .= $code_array[$active_key];
            $checksum = ($checksum + ($code_values[$active_key] * $position));
        }
        $encoded_value .= $code_array[$code_keys[($checksum - (intval($checksum / 103) * 103))]];

        $encoded_value = "211214" . $encoded_value . "2331112";

        return array($encoded_value, $value_to_encode);
    }

    /**
     * @param $value_to_encode
     *
     * @return array [
     *    0 => Encoded value - a sequence of numbers [1-4], each one represents a bar width;
     *    1 => Input value, sanitized of unsupported symbols, this is what actually been encoded;
     *  ]
     */
    protected function encodeCode128A($value_to_encode)
    {
        $encoded_value = "";

        $checksum = 103;

        $value_to_encode = strtoupper($value_to_encode);

        $code_array = array(
            " " => "212222",
            "!" => "222122",
            "\"" => "222221",
            "#" => "121223",
            "$" => "121322",
            "%" => "131222",
            "&" => "122213",
            "'" => "122312",
            "(" => "132212",
            ")" => "221213",
            "*" => "221312",
            "+" => "231212",
            "," => "112232",
            "-" => "122132",
            "." => "122231",
            "/" => "113222",
            "0" => "123122",
            "1" => "123221",
            "2" => "223211",
            "3" => "221132",
            "4" => "221231",
            "5" => "213212",
            "6" => "223112",
            "7" => "312131",
            "8" => "311222",
            "9" => "321122",
            ":" => "321221",
            ";" => "312212",
            "<" => "322112",
            "=" => "322211",
            ">" => "212123",
            "?" => "212321",
            "@" => "232121",
            "A" => "111323",
            "B" => "131123",
            "C" => "131321",
            "D" => "112313",
            "E" => "132113",
            "F" => "132311",
            "G" => "211313",
            "H" => "231113",
            "I" => "231311",
            "J" => "112133",
            "K" => "112331",
            "L" => "132131",
            "M" => "113123",
            "N" => "113321",
            "O" => "133121",
            "P" => "313121",
            "Q" => "211331",
            "R" => "231131",
            "S" => "213113",
            "T" => "213311",
            "U" => "213131",
            "V" => "311123",
            "W" => "311321",
            "X" => "331121",
            "Y" => "312113",
            "Z" => "312311",
            "[" => "332111",
            "\\" => "314111",
            "]" => "221411",
            "^" => "431111",
            "_" => "111224",
            "NUL" => "111422",
            "SOH" => "121124",
            "STX" => "121421",
            "ETX" => "141122",
            "EOT" => "141221",
            "ENQ" => "112214",
            "ACK" => "112412",
            "BEL" => "122114",
            "BS" => "122411",
            "HT" => "142112",
            "LF" => "142211",
            "VT" => "241211",
            "FF" => "221114",
            "CR" => "413111",
            "SO" => "241112",
            "SI" => "134111",
            "DLE" => "111242",
            "DC1" => "121142",
            "DC2" => "121241",
            "DC3" => "114212",
            "DC4" => "124112",
            "NAK" => "124211",
            "SYN" => "411212",
            "ETB" => "421112",
            "CAN" => "421211",
            "EM" => "212141",
            "SUB" => "214121",
            "ESC" => "412121",
            "FS" => "111143",
            "GS" => "111341",
            "RS" => "131141",
            "US" => "114113",
            "FNC 3" => "114311",
            "FNC 2" => "411113",
            "SHIFT" => "411311",
            "CODE C" => "113141",
            "CODE B" => "114131",
            "FNC 4" => "311141",
            "FNC 1" => "411131",
            "Start A" => "211412",
            "Start B" => "211214",
            "Start C" => "211232",
            "Stop" => "2331112"
        );
        $code_keys = array_keys($code_array);
        $code_values = array_flip($code_keys);

        $value_to_encode = preg_replace('/[^' . preg_quote(implode('', $code_keys), '/') . ']/i', '', $value_to_encode);
        for ($position = 1; $position <= strlen($value_to_encode); $position++) {
            $active_key = substr($value_to_encode, ($position - 1), 1);
            $encoded_value .= $code_array[$active_key];
            $checksum = ($checksum + ($code_values[$active_key] * $position));
        }

        $encoded_value .= $code_array[$code_keys[($checksum - (intval($checksum / 103) * 103))]];

        $encoded_value = "211412" . $encoded_value . "2331112";

        return array($encoded_value, $value_to_encode);
    }

    /**
     * @param $value_to_encode
     *
     * @return array [
     *    0 => Encoded value - a sequence of numbers [1-4], each one represents a bar width;
     *    1 => Input value, sanitized of unsupported symbols, this is what actually been encoded;
     *  ]
     */
    protected function encodeCode128C($value_to_encode)
    {
        $encoded_value = '';

        $checksum = 105;

        // Must not change order of array elements as the checksum depends on the array's key to validate final code
        $code_array = array(
            '00' => '212222',
            '01' => '222122',
            '02' => '222221',
            '03' => '121223',
            '04' => '121322',
            '05' => '131222',
            '06' => '122213',
            '07' => '122312',
            '08' => '132212',
            '09' => '221213',
            '10' => '221312',
            '11' => '231212',
            '12' => '112232',
            '13' => '122132',
            '14' => '122231',
            '15' => '113222',
            '16' => '123122',
            '17' => '123221',
            '18' => '223211',
            '19' => '221132',
            '20' => '221231',
            '21' => '213212',
            '22' => '223112',
            '23' => '312131',
            '24' => '311222',
            '25' => '321122',
            '26' => '321221',
            '27' => '312212',
            '28' => '322112',
            '29' => '322211',
            '30' => '212123',
            '31' => '212321',
            '32' => '232121',
            '33' => '111323',
            '34' => '131123',
            '35' => '131321',
            '36' => '112313',
            '37' => '132113',
            '38' => '132311',
            '39' => '211313',
            '40' => '231113',
            '41' => '231311',
            '42' => '112133',
            '43' => '112331',
            '44' => '132131',
            '45' => '113123',
            '46' => '113321',
            '47' => '133121',
            '48' => '313121',
            '49' => '211331',
            '50' => '231131',
            '51' => '213113',
            '52' => '213311',
            '53' => '213131',
            '54' => '311123',
            '55' => '311321',
            '56' => '331121',
            '57' => '312113',
            '58' => '312311',
            '59' => '332111',
            '60' => '314111',
            '61' => '221411',
            '62' => '431111',
            '63' => '111224',
            '64' => '111422',
            '65' => '121124',
            '66' => '121421',
            '67' => '141122',
            '68' => '141221',
            '69' => '112214',
            '70' => '112412',
            '71' => '122114',
            '72' => '122411',
            '73' => '142112',
            '74' => '142211',
            '75' => '241211',
            '76' => '221114',
            '77' => '413111',
            '78' => '241112',
            '79' => '134111',
            '80' => '111242',
            '81' => '121142',
            '82' => '121241',
            '83' => '114212',
            '84' => '124112',
            '85' => '124211',
            '86' => '411212',
            '87' => '421112',
            '88' => '421211',
            '89' => '212141',
            '90' => '214121',
            '91' => '412121',
            '92' => '111143',
            '93' => '111341',
            '94' => '131141',
            '95' => '114113',
            '96' => '114311',
            '97' => '411113',
            '98' => '411311',
            '99' => '113141',
        );

        $code_keys = array_keys($code_array);
        $code_values = array_flip($code_keys);

        $value_to_encode = preg_replace('/[^0-9]/', '', $value_to_encode); // Code 128C supports numbers only

        if ((strlen($value_to_encode) % 2) != 0) {
            $value_to_encode = '0' . $value_to_encode;
        }

        $i = 1;
        for ($position = 1; $position <= strlen($value_to_encode); $position += 2) {
            $active_key = substr($value_to_encode, ($position - 1), 2);
            $encoded_value .= $code_array[$active_key];
            $checksum = ($checksum + ($code_values[$active_key] * $i));
            $i++;
        }

        $encoded_value .= $code_array[$code_keys[($checksum - (intval($checksum / 103) * 103))]];

        $encoded_value = "211232" . $encoded_value . "2331112";

        return array($encoded_value, $value_to_encode);
    }

    /**
     * @param $value_to_encode
     *
     * @return array [
     *    0 => Encoded value - a sequence of numbers [1-4], each one represents a bar width;
     *    1 => Input value, sanitized of unsupported symbols, this is what actually been encoded;
     *  ]
     */
    protected function encodeCode39($value_to_encode)
    {
        $encoded_value = "";

        $code_array = array(
            "0" => "111221211",
            "1" => "211211112",
            "2" => "112211112",
            "3" => "212211111",
            "4" => "111221112",
            "5" => "211221111",
            "6" => "112221111",
            "7" => "111211212",
            "8" => "211211211",
            "9" => "112211211",
            "A" => "211112112",
            "B" => "112112112",
            "C" => "212112111",
            "D" => "111122112",
            "E" => "211122111",
            "F" => "112122111",
            "G" => "111112212",
            "H" => "211112211",
            "I" => "112112211",
            "J" => "111122211",
            "K" => "211111122",
            "L" => "112111122",
            "M" => "212111121",
            "N" => "111121122",
            "O" => "211121121",
            "P" => "112121121",
            "Q" => "111111222",
            "R" => "211111221",
            "S" => "112111221",
            "T" => "111121221",
            "U" => "221111112",
            "V" => "122111112",
            "W" => "222111111",
            "X" => "121121112",
            "Y" => "221121111",
            "Z" => "122121111",
            "-" => "121111212",
            "." => "221111211",
            " " => "122111211",
            "$" => "121212111",
            "/" => "121211121",
            "+" => "121112121",
            "%" => "111212121",
        );

        $code_keys = array_keys($code_array);

        $value_to_encode = strtoupper($value_to_encode);
        $value_to_encode = preg_replace('/[^' . preg_quote(implode('', $code_keys), '/') . ']/i', '', $value_to_encode);

        for ($position = 1; $position <= strlen($value_to_encode); $position++) {
            $encoded_value .= $code_array[substr($value_to_encode, ($position - 1), 1)] . "1";
        }

        $encoded_value = "1211212111" . $encoded_value . "121121211";

        return array($encoded_value, $value_to_encode);
    }

    /**
     * @param $value_to_encode
     *
     * @return array [
     *    0 => Encoded value - a sequence of numbers [1-4], each one represents a bar width;
     *    1 => Input value, sanitized of unsupported symbols, this is what actually been encoded;
     *  ]
     */
    protected function encodeCode25($value_to_encode)
    {
        $encoded_value = "";

        $code_array1 = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0");
        $code_array2 = array(
            "3-1-1-1-3",
            "1-3-1-1-3",
            "3-3-1-1-1",
            "1-1-3-1-3",
            "3-1-3-1-1",
            "1-3-3-1-1",
            "1-1-1-3-3",
            "3-1-1-3-1",
            "1-3-1-3-1",
            "1-1-3-3-1"
        );

        $value_to_encode = preg_replace('/[^0-9]/', '', $value_to_encode);

        $temp = array();
        for ($X = 1; $X <= strlen($value_to_encode); $X++) {
            for ($Y = 0; $Y < count($code_array1); $Y++) {
                if (substr($value_to_encode, ($X - 1), 1) == $code_array1[$Y]) {
                    $temp[$X] = $code_array2[$Y];
                }
            }
        }

        for ($X = 1; $X <= strlen($value_to_encode); $X += 2) {
            if (isset($temp[$X]) && isset($temp[($X + 1)])) {
                $temp1 = explode("-", $temp[$X]);
                $temp2 = explode("-", $temp[($X + 1)]);
                for ($Y = 0; $Y < count($temp1); $Y++) {
                    $encoded_value .= $temp1[$Y] . $temp2[$Y];
                }
            }
        }

        $encoded_value = "1111" . $encoded_value . "311";

        return array($encoded_value, $value_to_encode);
    }

    /**
     * @param $value_to_encode
     *
     * @return array [
     *    0 => Encoded value - a sequence of numbers [1-4], each one represents a bar width;
     *    1 => Input value, sanitized of unsupported symbols, this is what actually been encoded;
     *  ]
     */
    protected function encodeCodabar($value_to_encode)
    {
        $encoded_value = "";

        $code_array1 = array(
            "1",
            "2",
            "3",
            "4",
            "5",
            "6",
            "7",
            "8",
            "9",
            "0",
            "-",
            "$",
            ":",
            "/",
            ".",
            "+",
            "A",
            "B",
            "C",
            "D"
        );
        $code_array2 = array(
            "1111221",
            "1112112",
            "2211111",
            "1121121",
            "2111121",
            "1211112",
            "1211211",
            "1221111",
            "2112111",
            "1111122",
            "1112211",
            "1122111",
            "2111212",
            "2121112",
            "2121211",
            "1121212",
            "1122121",
            "1212112",
            "1112122",
            "1112221"
        );

        // Convert to uppercase
        $value_to_encode = strtoupper($value_to_encode);

        for ($X = 1; $X <= strlen($value_to_encode); $X++) {
            for ($Y = 0; $Y < count($code_array1); $Y++) {
                if (substr($value_to_encode, ($X - 1), 1) == $code_array1[$Y]) {
                    $encoded_value .= $code_array2[$Y] . "1";
                }
            }
        }
        $encoded_value = "11221211" . $encoded_value . "1122121";

        return array($encoded_value, $value_to_encode);
    }
}
