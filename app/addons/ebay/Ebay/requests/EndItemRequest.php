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
 * Class EndItemRequest
 * @package Ebay\requests
 * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/EndItem.html
 */
class EndItemRequest extends Request
{
    /** @var string  Ending reason
     *
     * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/types/EndReasonCodeType.html
     */
    public $reason;
    /** @var string Ebay item id  */
    public $item_id;

    /**
     * @param Product $product
     * @param string  $reason
     */
    public function __construct(Product $product, $reason = 'NotAvailable')
    {
        $this->reason = $reason;
        $this->item_id = $product->getExternalId();
    }

    /**
     * @inheritdoc
     */
    public function xml()
    {
        return <<<XML
<EndingReason>{$this->reason}</EndingReason>
<ItemID>{$this->item_id}</ItemID>
XML;
    }
}
