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

abstract class AGenerator {

    const ITEMS_PER_PAGE = 100;

    protected $price_schema;
    protected $selected_fields;

    public function __construct()
    {
        $this->price_schema = fn_get_schema('price_list', 'schema');
        $this->selected_fields = Registry::get('addons.price_list.price_list_fields');
    }

    public function getFileName()
    {
        $parts = explode('\\', get_class($this));
        $type = array_pop($parts);
        $type = strtolower($type);

        return fn_get_files_dir_path() . 'price_list/price_list_' . CART_LANGUAGE . '.' . $this->price_schema['types'][$type]['extension'];
    }

    /**
     * Renders products to document
     */
    public function render()
    {
        $this->printHeader();

        if (Registry::get('addons.price_list.group_by_category') == 'Y') {

            $categories = fn_get_plain_categories_tree(0, false);

            foreach ($categories as $category) {

                if (empty($category['product_count'])) {
                    continue;
                }

                $this->printCategoryRow($category);

                $params = array();
                $params['sort_by'] = $this->price_schema['fields'][Registry::get('addons.price_list.price_list_sorting')]['sort_by'];
                $params['page'] = 1;
                $params['skip_view'] = 'Y';
                $params['cid'] = $category['category_id'];
                $params['subcats'] = 'N';

                $this->processProducts($params);
            }

        } else {

            $total = static::ITEMS_PER_PAGE;

            $params = array();
            $params['sort_by'] = $this->price_schema['fields'][Registry::get('addons.price_list.price_list_sorting')]['sort_by'];
            $params['page'] = 1;
            $params['skip_view'] = 'Y';

            $this->processProducts($params);
        }

        $this->printFooter();
    }

    /**
     * Gets products for printing and print them
     * @param array $params product search params
     */
    protected function processProducts($params)
    {
        $total = static::ITEMS_PER_PAGE;

        while (static::ITEMS_PER_PAGE * ($params['page'] - 1) <= $total) {
            list($products, $search) = fn_get_products($params, static::ITEMS_PER_PAGE);
            $total = $search['total_items'];

            if ($params['page'] == 1) {
                fn_set_progress('parts', $total);
            }

            $get_images = !empty($this->selected_fields['image']);

            $_params = array(
                'get_icon' => $get_images,
                'get_detailed' => $get_images,
                'get_options' => (Registry::get('addons.price_list.include_options') == 'Y')? true : false,
                'get_discounts' => false,
            );
            fn_gather_additional_products_data($products, $_params);

            $params['page']++;

            $this->printProductsBatch(true);

            foreach ($products as $product) {
                fn_set_progress('echo');

                if (Registry::get('addons.price_list.include_options') == 'Y' && $product['has_options']) {
                    $product = fn_price_list_get_combination($product);

                    foreach ($product['combinations'] as $c_id => $c_value) {

                        $product['price'] = $product['combination_prices'][$c_id];
                        $product['weight'] = $product['combination_weight'][$c_id];
                        $product['amount'] = $product['combination_amount'][$c_id];
                        $product['product_code'] = $product['combination_code'][$c_id];

                        $this->printProductRow($product, $c_value);
                    }

                } else {
                    $this->printProductRow($product);
                }
            }

            $this->printProductsBatch();
        }
    }

    /**
     *
     * Prints product row
     * @param array $product Product data
     * @param array $options_variants Product options variants
     */
    protected function printProductData($product, $options_variants = array())
    {
    }

    /**
     * Prints batch of product rows
     * @param boolean $start flag, set to true before printing rows and false - after
     */
    protected function printProductsBatch($start = false)
    {
    }

    /**
     * Prints category header
     */
    protected function printCategoryRow($category)
    {
    }

    /**
     * Prints document header
     */
    protected function printHeader()
    {
    }

    /**
     * Prints document footer
     */
    protected function printFooter()
    {
    }
}
