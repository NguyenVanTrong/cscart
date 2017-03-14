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

use Tygh\Template\IContext;

/**
 * The context class for an item represented in a table.
 *
 * @package Tygh\Template\Snippet\Table
 */
class ItemContext implements IContext
{
    /** @var IContext */
    protected $parent_context;

    /** @var mixed */
    protected $item;

    /**
     * ItemContext constructor.
     *
     * @param IContext  $context            Instance of parent context.
     * @param mixed     $item               Item data.
     */
    public function __construct(IContext $context, $item)
    {
        $this->parent_context = $context;
        $this->item = $item;
    }

    /**
     * Gets item.
     *
     * @return mixed
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Gets parent context.
     *
     * @return IContext
     */
    public function getParentContext()
    {
        return $this->parent_context;
    }
    
    /**
     * @inheritDoc
     */
    public function getLangCode()
    {
        return $this->parent_context->getLangCode();
    }
}