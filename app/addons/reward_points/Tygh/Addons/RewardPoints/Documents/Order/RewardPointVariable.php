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

namespace Tygh\Addons\RewardPoints\Documents\Order;


use Tygh\Template\Document\Order\Context;
use Tygh\Template\IActiveVariable;
use Tygh\Template\IVariable;
use Tygh\Tools\Formatter;

/**
 * Class RewardPointVariable
 * @package Tygh\Addons\RewarPoints\Documents\Order
 */
class RewardPointVariable implements IVariable, IActiveVariable
{
    public $cost;
    public $points;
    public $in_use;
    public $in_use_text;
    public $raw = array();

    public function __construct(Context $context, Formatter $formatter)
    {
        $order = $context->getOrder();

        if (!empty($order->data['points_info']['reward'])) {
            $this->points = $order->data['points_info']['reward'];
        }

        if (!empty($order->data['points_info']['in_use'])) {
            $this->in_use = $order->data['points_info']['in_use']['points'];
            $this->in_use_text = __('points_lowercase', array($order->data['points_info']['in_use']['points']), $context->getLangCode());
            $this->cost = $formatter->asPrice($order->data['points_info']['in_use']['cost']);
            $this->raw['cost'] = $order->data['points_info']['in_use']['cost'];
        }
    }

    /**
     * @inheritDoc
     */
    public static function attributes()
    {
        return array(
            'cost', 'points', 'in_use', 'in_use_text',
            'raw' => array(
                'cost'
            )
        );
    }
}