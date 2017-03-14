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
 * Class AddItemsRequest
 * @package Ebay\requests
 * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/AddItems.html
 */
class AddItemsRequest extends Request
{
    /** @var Product[]  */
    protected $products;

    /**
     * @param Product[] $products
     */
    public function __construct(array $products)
    {
        $this->products = $products;
    }

    /**
     * @inheritdoc
     */
    public function xml()
    {
        $xml = '';

        foreach ($this->products as $product) {
            $request = new AddItemRequest($product);

            $xml .= <<<XML
<AddItemRequestContainer>
    <MessageID>{$product->id}</MessageID>
    {$request->xml()}
</AddItemRequestContainer>
XML;
            $request = null;
            unset($request);
        }

        return $xml;
    }
}
