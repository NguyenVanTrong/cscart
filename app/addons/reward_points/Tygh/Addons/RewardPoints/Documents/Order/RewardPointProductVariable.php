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


use Tygh\Template\IVariable;
use Tygh\Template\Snippet\Table\ItemContext;
use Tygh\Tools\Formatter;

/**
 * Class RewardPointProductVariable
 * @package Tygh\Addons\RewarPoints\Documents\Order
 */
class RewardPointProductVariable implements IVariable
{
    public $points;
    public $text;

    /**
     * RewardPointProductVariable constructor.
     * 
     * @param ItemContext   $context    Instance of table column context.
     * @param Formatter     $formatter  Instance of 
     */
    public function __construct(ItemContext $context, Formatter $formatter)
    {
        $product = $context->getItem();

        if (!empty($product['extra']['points_info']['price'])) {
            $this->points = $product['extra']['points_info']['price'];
            $this->text = __('price_in_points', array(), $context->getLangCode()) . ': ' . $this->points;
        }
    }
}