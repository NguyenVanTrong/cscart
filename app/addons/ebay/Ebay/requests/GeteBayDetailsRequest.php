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

/**
 * Class GeteBayDetailsRequest
 * @package Ebay\requests
 * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/GeteBayDetails.html
 */
class GeteBayDetailsRequest extends Request
{
    /** @var array  */
    protected $details = array();

    /**
     * @param string|array $details
     */
    public function __construct($details)
    {
        $this->details = (array) $details;
    }

    /**
     * @inheritdoc
     */
    public function xml()
    {
        $xml = '';

        foreach ($this->details as $item) {
            $xml .= "<DetailName>{$item}</DetailName>";
        }

        return $xml;
    }
}
