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


namespace Tygh\Template\Snippet\Table;

use Tygh\Registry;
use Tygh\SmartyEngine\Core;
use Tygh\Template\IActiveVariable;
use Tygh\Template\IVariable;
use Tygh\Tools\Formatter;

/**
 * The class of the `product` variable; it allows access to product data.
 *
 * @package Tygh\Template\Snippet\Table
 */
class ProductVariable implements IVariable, IActiveVariable
{
    public $item_id;
    public $product_id;
    public $product_code;
    public $name;
    public $price;
    public $amount;
    public $product_status;
    public $deleted_product;
    public $discount;
    public $company_id;
    public $base_price;
    public $original_price;
    public $cart_id;
    public $tax;
    public $subtotal;
    public $display_subtotal;
    public $shipped_amount;
    public $shipment_amount;
    public $is_accessible;
    public $shared_product;
    public $product_options;
    public $unlimited_download;
    public $is_edp;
    public $edp_shipping;
    public $stored_price;
    public $counter;
    public $raw = array();

    protected $image;
    protected $main_pair;
    protected $product;
    protected $view;
    protected $context;
    protected $options;
    protected $formatter;
    protected $config;

    /**
     * ProductVariable constructor.
     *
     * @param ItemContext   $context    Instance of column context.
     * @param array         $config     Config data.
     * @param Core          $view       Instance of smarty renderer.
     * @param Formatter     $formatter  Instance of formatter.
     */
    public function __construct(ItemContext $context, array $config, Core $view, Formatter $formatter)
    {
        $this->product = $context->getItem();

        foreach ($this->product as $field => $item) {
            if (property_exists($this, $field) && !in_array($field, array('image', 'main_pair', 'product', 'view', 'context', 'options'))) {
                $this->{$field} = $item;
            }
        }

        $this->config = $config;
        $this->context = $context;
        $this->view = $view;
        $this->formatter = $formatter;

        $this->raw['price'] = $this->price;
        $this->raw['original_price'] = $this->original_price;
        $this->raw['subtotal'] = $this->subtotal;
        $this->raw['display_subtotal'] = $this->display_subtotal;
        $this->raw['discount'] = $this->discount;

        $this->price = $this->formatter->asPrice($this->price, CART_SECONDARY_CURRENCY, true, true);
        $this->original_price = $this->formatter->asPrice($this->original_price, CART_SECONDARY_CURRENCY, true, true);
        $this->subtotal = $this->formatter->asPrice($this->subtotal, CART_SECONDARY_CURRENCY, true, true);
        $this->display_subtotal = $this->formatter->asPrice($this->display_subtotal, CART_SECONDARY_CURRENCY, true, true);

        $this->name = $this->product['product'];

        if ($this->discount) {
            $this->discount = $this->formatter->asPrice($this->discount, CART_SECONDARY_CURRENCY, true, true);
        } else {
            $this->discount = $this->config['null_display'];
        }
    }

    public function getMainPair()
    {
        if ($this->main_pair === null) {
            $this->main_pair = fn_get_cart_product_icon($this->product['product_id'], $this->product);
        }

        return $this->main_pair;
    }

    public function getImage()
    {
        if ($this->image === null) {
            $this->image = '';

            $main_pair = $this->getMainPair();

            if ($main_pair) {
                $this->image = $this->formatter->asImage(
                    !empty($main_pair['icon']) ? $main_pair['icon'] : $main_pair['detailed'],
                    Registry::get('settings.Thumbnails.product_admin_mini_icon_width'),
                    Registry::get('settings.Thumbnails.product_admin_mini_icon_height')
                );
            }
        }

        return $this->image;
    }

    public function getOptions()
    {
        if ($this->options === null && !empty($this->product['product_options'])) {
            $this->view->assign('product_options', $this->product['product_options']);
            $this->options = $this->view->displayMail('common/options_info.tpl', false, 'A', null, $this->context->getLangCode());
        }

        return $this->options;
    }

    public function getTax()
    {
        $tax = isset($this->product['tax_value']) ? $this->product['tax_value'] : 0;

        if ($tax) {
            $tax = $this->formatter->asPrice($tax, CART_SECONDARY_CURRENCY, true, true);
        } else {
            $tax = $this->config['null_display'];
        }

        return $tax;
    }

    /**
     * @inheritDoc
     */
    public static function attributes()
    {
        return array(
            'item_id', 'product_id', 'product_code', 'name', 'price', 'amount', 'product_status', 'deleted_product',
            'discount', 'company_id', 'base_price', 'original_price', 'cart_id', 'tax', 'subtotal', 'display_subtotal',
            'shipped_amount', 'shipment_amount', 'is_accessible', 'shared_product', 'product_options', 'unlimited_download',
            'is_edp', 'edp_shipping', 'stored_price', 'counter', 'main_pair', 'image', 'options',
            'raw' => array(
                'price', 'original_price', 'subtotal', 'display_subtotal', 'discount'
            )
        );
    }
}