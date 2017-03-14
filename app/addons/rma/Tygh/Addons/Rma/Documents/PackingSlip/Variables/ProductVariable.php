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


namespace Tygh\Addons\Rma\Documents\PackingSlip\Variables;


use Tygh\Addons\Rma\Documents\PackingSlip\Context;
use Tygh\SmartyEngine\Core;
use Tygh\Template\IActiveVariable;
use Tygh\Template\IVariable;
use Tygh\Template\Snippet\Table\ItemContext;
use Tygh\Tools\Formatter;

/**
 * Class ProductVariable
 * @package Tygh\Addons\Rma\Documents\PackingSlip\Variables
 */
class ProductVariable implements IVariable, IActiveVariable
{
    public $product_id;
    public $code;
    public $name;
    public $reason;
    public $amount;
    public $type;
    public $price;
    public $product_options;
    public $options;
    public $raw = array();

    /** @var ItemContext */
    protected $context;

    /** @var array */
    protected static $reasons = array();

    /**
     * ProductVariable constructor.
     *
     * @param ItemContext   $context    Instance of table item context.
     * @param array         $config     Config data.
     * @param Core          $view       Instance of smarty renderer.
     * @param Formatter     $formatter  Instance of formatter.
     */
    public function __construct(ItemContext $context, array $config, Core $view, Formatter $formatter)
    {
        $product = $context->getItem();

        $this->context = $context;
        $this->code = $product['product_code'];
        $this->product_id = $product['product_id'];
        $this->name = $product['product'];
        $this->reason = $product['reason'];
        $this->amount = $product['amount'];
        $this->type = $product['type'];
        $this->product_options = $product['product_options'];
        $this->raw['price'] = $this->price;

        if ($this->price) {
            $this->price = __("free", array(), $context->getLangCode());
        } else {
            $this->price = $formatter->asPrice($product['price']);
        }

        if ($this->product_options) {
            $view->assign('product_options', $this->product_options);
            $this->options = $view->displayMail('common/options_info.tpl', false, 'A', null, $this->context->getLangCode());
        }
    }

    /**
     * Gets reason text.
     *
     * @return string
     */
    public function getReasonText()
    {
        $reasons = self::getReasons($this->context->getLangCode());

        return isset($reasons[$this->reason]) ? $reasons[$this->reason]['property'] : '';
    }

    /**
     * Gets rma reasons.
     *
     * @param string    $lang_code  Language code.
     *
     * @return array
     */
    protected static function getReasons($lang_code)
    {
        if (!isset(self::$reasons[$lang_code])) {
            self::$reasons[$lang_code] = fn_get_rma_properties(RMA_REASON, $lang_code);
        }

        return self::$reasons[$lang_code];
    }

    /**
     * @inheritDoc
     */
    public static function attributes()
    {
        return array(
            'product_id', 'code', 'name', 'reason', 'amount', 'type', 'price', 'product_options', 'options',
            'raw' => array(
                'price'
            )
        );
    }
}