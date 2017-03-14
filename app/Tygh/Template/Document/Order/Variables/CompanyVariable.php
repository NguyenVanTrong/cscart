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


namespace Tygh\Template\Document\Order\Variables;


use Tygh\Template\Document\Order\Context;

/**
 * Class CompanyVariable
 * @package Tygh\Template\Document\Order\Variables
 */
class CompanyVariable extends \Tygh\Template\Document\Variables\CompanyVariable
{
    public function __construct(Context $context, array $config = array())
    {
        $order = $context->getOrder();
        parent::__construct($config, $order->getCompanyId(), $context->getLangCode());
    }
}