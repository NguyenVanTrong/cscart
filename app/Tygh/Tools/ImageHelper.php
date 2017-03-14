<?php

namespace Tygh\Tools;

use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\FontInterface;
use Imagine\Image\Point;
use Tygh\Tygh;

class ImageHelper
{
    /**
     * Calculates size for bounding box of string written in given font.
     *
     * @param string        $string
     * @param FontInterface $font
     *
     * @return bool|Box Instance of Box object, false on error
     */
    public static function calculateTextSize($string, FontInterface $font)
    {
        $imagine = Tygh::$app['image'];

        if ($imagine instanceof \Imagine\Imagick\Imagine && class_exists('ImagickDraw')) {

            $text = new \ImagickDraw();

            $text->setFont($font->getFile());

            if (version_compare(phpversion("imagick"), "3.0.2", ">=")) {
                $text->setResolution(96, 96);
                $text->setFontSize($font->getSize());
            } else {
                $text->setFontSize((int)($font->getSize() * (96 / 72)));
            }

            $imagick = new \Imagick();
            $info = $imagick->queryFontMetrics($text, $string);

            $text->clear();
            $text->destroy();

            $imagick->clear();
            $imagick->destroy();

            return new Box($info['textWidth'], $info['textHeight']);
        }

        if ($imagine instanceof \Imagine\Gd\Imagine && function_exists('imagettfbbox')) {
            $ttfbbox = imagettfbbox($font->getSize(), 0, $font->getFile(), $string);

            return new Box(abs($ttfbbox[2]), abs($ttfbbox[7]));
        }

        return false;
    }

    /**
     * Calculates coordinates of top left corner of layer that should be positioned on canvas using given
     * vertical and horizontal positions.
     *
     * @param BoxInterface      $canvas_size         Size of canvas
     * @param BoxInterface      $layer_size          Size of layer
     * @param string            $horizontal_position left|center|right
     * @param string            $vertical_position   top|center|bottom
     * @param BoxInterface|null $padding             Optional padding between canvas and layer sides.
     *
     * @return Point
     */
    public static function positionLayerOnCanvas(
        BoxInterface $canvas_size,
        BoxInterface $layer_size,
        $horizontal_position,
        $vertical_position,
        BoxInterface $padding = null
    ) {
        $dest_x = $dest_y = 0;

        $original_width = $canvas_size->getWidth();
        $original_height = $canvas_size->getHeight();

        if ($padding instanceof BoxInterface) {
            $delta_x = $padding->getWidth();
            $delta_y = $padding->getHeight();
        } else {
            $delta_x = 0;
            $delta_y = 0;
        }

        $new_wt_width = $layer_size->getWidth();
        $new_wt_height = $layer_size->getHeight();

        if ($new_wt_width + $delta_x > $original_width) {
            $new_wt_height = $new_wt_height * ($original_width - $delta_x) / $new_wt_width;
            $new_wt_width = $original_width - $delta_x;
        }

        if ($new_wt_height > $original_height) {
            $new_wt_width = $new_wt_width * ($original_height - $delta_y) / $new_wt_height;
            $new_wt_height = $original_height - $delta_y;
        }

        if ($vertical_position == 'top') {
            $dest_y = $delta_y;
        } elseif ($vertical_position == 'center') {
            $dest_y = (int)(($original_height - $new_wt_height) / 2);
        } elseif ($vertical_position == 'bottom') {
            $dest_y = $original_height - $new_wt_height - $delta_y;
        }

        if ($horizontal_position == 'left') {
            $dest_x = $delta_x;
        } elseif ($horizontal_position == 'center') {
            $dest_x = (int)(($original_width - $new_wt_width) / 2);
        } elseif ($horizontal_position == 'right') {
            $dest_x = $original_width - $new_wt_width - $delta_x;
        }

        if ($dest_x < 1) {
            $dest_x = 0;
        }
        if ($dest_y < 1) {
            $dest_y = 0;
        }

        return new Point($dest_x, $dest_y);
    }

    /**
     * @return array Image formats, supported by current image manipulation driver.
     *               The only formats that being checked are jpg, png and gif.
     */
    public static function getSupportedFormats()
    {
        $imagine = Tygh::$app['image'];
        $supported_formats = array();

        if ($imagine instanceof \Imagine\Imagick\Imagine) {
            $imagick = new \Imagick();

            $supported_formats = array_uintersect(array('jpg', 'png', 'gif'), $imagick->queryFormats(), 'strcasecmp');
            $supported_formats = array_map('strtolower', $supported_formats);
            $imagick->clear();
            $imagick->destroy();
            unset($imagick);
        } elseif ($imagine instanceof \Imagine\Gd\Imagine) {
            $gd_formats = imagetypes();

            if ($gd_formats & IMG_JPEG) {
                $supported_formats[] = 'jpg';
            }
            if ($gd_formats & IMG_PNG) {
                $supported_formats[] = 'png';
            }

            if ($gd_formats & IMG_GIF) {
                $supported_formats[] = 'gif';
            }
        }

        return $supported_formats;
    }

    /**
     * If given new image width or height is empty (i.e. null or zero), this function calculates the size of empty sides
     * basing on original image proportions.
     *
     * @param int|float $original_width
     * @param int|float $original_height
     * @param int|float $new_width
     * @param int|float $new_height
     * @param bool      $high_precision Whether to return floats instead of integers
     *
     * @return array($new_width, $new_height)
     */
    public static function originalProportionsFallback(
        $original_width,
        $original_height,
        $new_width,
        $new_height,
        $high_precision = false
    ) {
        if (empty($new_width) && !empty($new_height) && !empty($original_height)) {
            $new_width = $new_height * ($original_width / $original_height);
        }

        if (empty($new_height) && !empty($new_width) && !empty($original_width)) {
            $new_height = $new_width * ($original_height / $original_width);
        }

        if (!$high_precision) {
            $new_width = (int)$new_width;
            $new_height = (int)$new_height;
        }

        return array($new_width, $new_height);
    }
}