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
 * Class ReviseItemResponse
 * @package Ebay\responses
 * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/ReviseItem.html
 */
class ReviseItemResponse extends Response
{
    const ERROR_AUCTION_ENDED = 291;

    /** @var string|null Ebay item identifier */
    protected $external_id;

    /**
     * @inheritdoc
     */
    public function __construct(\SimpleXMLElement $response)
    {
        parent::__construct($response);

        if (!empty($response->ItemID)) {
            $this->external_id = (string) $response->ItemID;
        }
    }

    /**
     * Return flag of ebay auction ended
     * @return bool
     */
    public function issetErrorAuctionEnded()
    {
        return isset($this->error_codes[static::ERROR_AUCTION_ENDED]);
    }
}
