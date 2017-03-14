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

namespace Tygh\Addons\Suppliers\Documents\SupplierOrder;


use Tygh\Template\Document\Order\Context as OrderContext;
use Tygh\Template\Document\Order\Order;

/**
 * Class Context
 * @package Tygh\Addons\Suppliers\Documents\SupplierOrder
 */
class Context extends OrderContext
{
    /** @var array */
    protected $supplier;

    /** @var array */
    protected $products = array();

    /**
     * Context constructor.
     *
     * @param Order $order      Instance of order.
     * @param array $supplier   Supplier data.
     */
    public function __construct(Order $order, array $supplier)
    {
        $this->order = $order;
        $this->supplier = $supplier;

        $products = $order->getProducts();

        foreach ($products as $key => $product) {
            if (
                (!empty($product['extra']['supplier_id']) && $product['extra']['supplier_id'] == $supplier['supplier_id'])
                || (fn_get_product_supplier_id($product['product_id']) == $supplier['supplier_id'])
            ) {
                $this->products[$key] = $product;
            }
        }
    }

    /**
     * Gets products.
     *
     * @return array
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Gets supplier data.
     *
     * @return array
     */
    public function getSupplier()
    {
        return $this->supplier;
    }
}