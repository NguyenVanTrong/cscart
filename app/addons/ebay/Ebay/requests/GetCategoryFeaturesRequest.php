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
 * Class GetCategoryFeaturesRequest
 * @package Ebay\requests
 * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/GetCategoryFeatures.html
 */
class GetCategoryFeaturesRequest extends Request
{
    /** @var string */
    public $category_id;
    /** @var array */
    public $feature_ids = array();

    /**
     * Constructor
     *
     * @param string $category_id
     * @param array  $feature_ids
     */
    public function __construct($category_id, $feature_ids)
    {
        $this->category_id = $category_id;
        $this->feature_ids = $feature_ids;
    }

    /**
     * @inheritdoc
     */
    public function xml()
    {
        $xml = <<<XML
    <DetailLevel>ReturnAll</DetailLevel>
    <ViewAllNodes>true</ViewAllNodes>
XML;
        $xml .= '<FeatureID>' . implode('</FeatureID><FeatureID>', $this->feature_ids) . '</FeatureID>';

        if (!empty($this->category_id)) {
            $xml .= "<CategoryID>{$this->category_id}</CategoryID>";
        }

        return $xml;
    }
}
