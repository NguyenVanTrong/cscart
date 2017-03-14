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


namespace Tygh\Addons\Rma\Documents\PackingSlip;

use Tygh\Template\Document\Order\Context as OrderContext;
use Tygh\Template\Document\Order\Order;

/**
 * Class Context
 * @package Tygh\Template\Document\PackingSlip
 */
class Context extends OrderContext
{
    /** @var array */
    protected $return_info = array();

    /** @var array */
    protected $products = array();

    /**
     * Context constructor.
     *
     * @param Order $order          Instance of order.
     * @param array $return_info    Return data.
     */
    public function __construct(Order $order, array $return_info = array())
    {
        $this->return_info = $return_info;
        $this->order = $order;

        if (!empty($return_info['items'][RETURN_PRODUCT_ACCEPTED])) {
            foreach ($return_info['items'][RETURN_PRODUCT_ACCEPTED] as $item) {
                if (isset($order->data['products'][$item['item_id']]['product_code'])) {
                    $item['product_code'] = $order->data['products'][$item['item_id']]['product_code'];
                } else {
                    $item['product_code'] = '';
                }

                $this->products[] = $item;
            }
        }
    }

    /**
     * Gets return request data.
     * 
     * @return array
     */
    public function getReturnInfo()
    {
        return $this->return_info;
    }

    /**
     * @inheritDoc
     */
    public function getProducts()
    {
        return $this->products;
    }
}