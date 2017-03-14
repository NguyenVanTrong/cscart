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

namespace Ebay\responses;

/**
 * Class EndItemsResponse
 * @package Ebay\responses
 * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/EndItems.html
 */
class EndItemsResponse extends Response
{
    /** @var EndItemResponse[]  */
    protected $items = array();

    /**
     * @inheritdoc
     */
    public function __construct(\SimpleXMLElement $response)
    {
        parent::__construct($response);

        if (!empty($response->EndItemResponseContainer)) {
            foreach ($response->EndItemResponseContainer as $item) {
                $this->items[(string) $item->CorrelationID] = new EndItemResponse($item);
            }
        }
    }

    /**
     * Return end item responses
     * @return EndItemResponse[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Return end item response
     * @param string $id Message id from request
     * @return EndItemResponse|null
     */
    public function getItem($id)
    {
        return isset($this->items[$id]) ? $this->items[$id] : null;
    }

    /** @inheritdoc */
    public function getSuccessRate()
    {
        $success_count = 0;

        foreach ($this->items as $item) {
            if ($item->isSuccess()) {
                $success_count++;
            }
        }

        return $success_count > 0 ? $success_count / count($this->items) : 0;
    }
}
