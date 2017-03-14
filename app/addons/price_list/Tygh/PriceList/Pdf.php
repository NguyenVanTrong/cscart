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

namespace Tygh\PriceList;

use Tygh\Pdf as RenderPdf;
use Tygh\Registry;

class Pdf extends AGenerator {

    const FIELDS_HEADER_FONT_SIZE = '17px';
    const FIELDS_ODD_BG_COLOR = '#EEEEEE';
    const IMAGE_HEIGHT = 50;

    const CATEGORY_HEADER_FONT_SIZE = '18px';
    const CATEGORY_HEADER_FONT_COLOR = '#FFFFFF';
    const CATEGORY_HEADER_BG_COLOR = '#888888';
    const TABLE_CELLPADDING = 4;
    const TABLE_CELLSPACING = 0;

    protected $tbl = '';

    public function generate($force = false)
    {
        $filename = $this->getFileName();

        if (!is_dir(dirname($filename))) {
            fn_mkdir(dirname($filename));
        }

        if ($force) {
            fn_rm($filename);
        }

        // Min column width in percent
        $min_width = array(
            'product' => 50,
            'product_code' => 13,
            'image' => 10,
        );

        if (!file_exists($filename)) {

            $max_perc = 100;
            $field_count = count($this->selected_fields);

            // First step. Check for the min width.
            $perc = intval($max_perc / $field_count);

            foreach ($this->selected_fields as $field_name => $active) {
                if (isset($min_width[$field_name])) {
                    if ($min_width[$field_name] > $perc) {
                        $max_perc -= $min_width[$field_name];
                        $field_count--;
                    }
                }
            }

            // Second step. Set up the new width values.
            $perc = intval($max_perc / $field_count);

            foreach ($this->selected_fields as $field_name => $active) {
                if (!isset($min_width[$field_name]) || $min_width[$field_name] < $perc) {
                    $this->price_schema['fields'][$field_name]['min_width'] = $perc;
                } else {
                    $this->price_schema['fields'][$field_name]['min_width'] = $min_width[$field_name];
                }
            }

            fn_set_progress('echo', __('generating_pdf'), false);
            $this->render();
        }

        return $filename;
    }

    protected function printProductRow($product, $options_variants = array())
    {
        $tbl = '<tr>';
        foreach ($this->selected_fields as $field_name => $active) {
            $tbl .= '<td width="' . $this->price_schema['fields'][$field_name]['min_width'] . '%">';
            if ($field_name == 'image') {
                if (!empty($product['main_pair']) && $image_data = fn_image_to_display($product['main_pair'], 0, static::IMAGE_HEIGHT)) {
                    $tbl .= '<img src="' . $image_data['image_path'] . '" width= "' . $image_data['width'] . '" height="' . $image_data['height'] . '" alt="">';
                }
            } elseif ($field_name == 'product' && !empty($options_variants)) {
                $options = array();

                foreach ($options_variants as $option_id => $variant_id) {
                    $options[] = htmlspecialchars($product['product_options'][$option_id]['option_name'] . ': ' . $product['product_options'][$option_id]['variants'][$variant_id]['variant_name']);
                }

                $options = implode('<br>', $options);

                $tbl .= htmlspecialchars($product[$field_name]) . '<br>' . $options;
            } elseif ($field_name == 'price') {
                $tbl .= fn_format_price($product[$field_name], CART_PRIMARY_CURRENCY, null, false);
            } else {
                $tbl .= htmlspecialchars($product[$field_name]);
            }
            $tbl .= '</td>';
        }
        $tbl .= '</tr>';

        $this->tbl .= $tbl;
    }

    protected function printProductsBatch($start = false)
    {
        RenderPdf::batchAdd($this->tbl);
        $this->tbl = '';
    }

    protected function printCategoryRow($category)
    {
        // Write category name
        $tbl = '<tr>';
        $tbl .= '<th class="category" colspan="' . count($this->selected_fields) . '">' . fn_price_list_build_category_name($category['id_path']) . '</th>';
        $tbl .= '</tr>';

        // Write product head fields
        $tbl .= '<tr>';
        foreach ($this->selected_fields as $field_name => $active) {
            $tbl .= '<th width="' . $this->price_schema['fields'][$field_name]['min_width'] . '%">' . $this->price_schema['fields'][$field_name]['title'] . '</th>';
        }
        $tbl .= '</tr>';

        $this->tbl .= $tbl;
    }

    protected function printHeader()
    {
        $tbl = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
        <html>
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title></title>
        <style type="text/css" media="screen,print">
        table {
            width: 100%;
            border: 0;
            padding: ' . static::TABLE_CELLPADDING . ';
            border-spacing: ' . static::TABLE_CELLSPACING . ';
            border-collapse: separate;
        }
        tr:nth-child(even) {
            background-color: ' . static::FIELDS_ODD_BG_COLOR . ';
        }
        th {
            text-align: left;
            font-size: ' . static::FIELDS_HEADER_FONT_SIZE . ';
        }
        th.category {
            text-align: left;
            background-color: ' . static::CATEGORY_HEADER_BG_COLOR .';
            font-size: ' . static::CATEGORY_HEADER_FONT_SIZE . ';
            color: ' . static::CATEGORY_HEADER_FONT_COLOR . ';
        }
        </style>
        </head>
        <body>
        <table>';

        if (Registry::get('addons.price_list.group_by_category') != 'Y') {

            $tbl .= '<tr>';

            foreach ($this->selected_fields as $field_name => $active) {
                $tbl .= '<th width="' . $this->price_schema['fields'][$field_name]['min_width'] . '%">' . $this->price_schema['fields'][$field_name]['title'] . '</th>';
            }

            $tbl .= '</tr>';
        }

        $this->tbl = $tbl;
    }

    protected function printFooter()
    {
        RenderPdf::batchAdd('</table></body></html>');
        RenderPdf::batchRender($this->getFileName(), true);
    }
}
