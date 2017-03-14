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
 * Class UploadSiteHostedPicturesResponse
 * @package Ebay\responses
 * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/UploadSiteHostedPictures.html
 */
class UploadSiteHostedPicturesResponse extends Response
{
    /** @var string|null Ebay picture url */
    protected $url;

    /**
     * @inheritdoc
     */
    public function __construct(\SimpleXMLElement $response)
    {
        parent::__construct($response);

        if (!empty($response->SiteHostedPictureDetails->FullURL)) {
            $this->url = (string) $response->SiteHostedPictureDetails->FullURL;
        }
    }

    /**
     * Return url
     * @return null|string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
