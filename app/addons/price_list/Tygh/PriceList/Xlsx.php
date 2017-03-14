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

use Tygh\Registry;

class Xlsx extends AGenerator
{
    protected $data = array();
    protected $writer;
    protected $sheet = 'Sheet1';

    public function __construct()
    {
        parent::__construct();

        \Tygh::$app['class_loader']->addClassMap(array(
            'XLSXWriter' => Registry::get('config.dir.addons') . 'price_list/lib/php_xls_writer/xlsxwriter.class.php'
        ));

        $this->writer = new \XLSXWriter();
    }

    public function generate($force = false)
    {
        $filename = $this->getFileName();

        if (!is_dir(dirname($filename))) {
            fn_mkdir(dirname($filename));
        }

        if ($force) {
            fn_rm($filename);
        }

        if (!file_exists($filename)) {

            fn_set_progress('echo', __('generating_xls'), false);

            $header = $data = array();

            $currencies = Registry::get('currencies');
            $currency = $currencies[CART_SECONDARY_CURRENCY];

            $currency_format = '#' . html_entity_decode($currency['thousands_separator']) . '##0.' . str_repeat('0', $currency['decimals']);
            $currency_format = $currency['after'] == 'Y' ? $currency_format . strip_tags($currency['symbol']) : strip_tags($currency['symbol']) . $currency_format;

            foreach ($this->selected_fields as $field_id => $field_value) {
                $header[$this->price_schema['fields'][$field_id]['title']] = $field_id == 'price' ? $currency_format : 'string';
            }

            $this->writer->writeSheetHeader($this->sheet, $header);

            $this->render();

            $this->writer->writeToFile($filename);
        }

        return $filename;
    }

    protected function printCategoryRow($category)
    {
        $this->writer->writeSheetRow($this->sheet, array(fn_price_list_build_category_name($category['id_path'])));
    }

    protected function printProductRow($product, $options_variants = array())
    {
        $_data = array();
        foreach ($this->selected_fields as $field_id => $field_value) {
            if ($field_id == 'image') {
                if (!empty($product['main_pair']) && $image_data = fn_image_to_display($product['main_pair'])) {
                    $value = $image_data['detailed_image_path'];
                } else {
                    $value = '';
                }

            } else {
                $value = isset($product[$field_id]) ? $product[$field_id] : '';
            }
            $_data[] = $value;
        }

        $this->data[] = $_data;
    }

    protected function printProductsBatch($start = false)
    {
        if ($start == true) {
            return false;
        }

        foreach ($this->data as $row) {
            $this->writer->writeSheetRow($this->sheet, $row);
        }

        $this->data = array();
    }
}