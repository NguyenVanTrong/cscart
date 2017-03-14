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

namespace Ebay\requests;
use Ebay\Product;

/**
 * Class GetItemRequest
 * @package Ebay\requests
 * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/GetItem.html
 */
class GetItemRequest extends Request
{
    /** @var string Ebay item id  */
    public $item_id;

    /**
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->item_id = $product->getExternalId();
    }

    /**
     * @inheritdoc
     */
    public function xml()
    {
        return <<<XML
<IncludeItemSpecifics>true</IncludeItemSpecifics>
<ItemID>{$this->item_id}</ItemID>
<DetailLevel>ReturnAll</DetailLevel>
XML;
    }
}
