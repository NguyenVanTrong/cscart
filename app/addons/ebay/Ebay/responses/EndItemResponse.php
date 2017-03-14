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
 * Class EndItemResponse
 * @package Ebay\responses
 * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/EndItem.html
 */
class EndItemResponse extends Response
{
    /** @var string|null  */
    protected $end_time;

    /**
     * @inheritdoc
     */
    public function __construct(\SimpleXMLElement $response)
    {
        parent::__construct($response);

        if (!empty($response->EndTime)) {
            $this->end_time = (string) $response->EndTime;
        }
    }

    /**
     * @return null|string
     */
    public function getEndTime()
    {
        return $this->end_time;
    }
}
