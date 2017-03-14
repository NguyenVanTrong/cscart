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
use Ebay\XmlHelper;

/**
 * Class GetCategoriesResponse
 * @package Ebay\responses
 * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/GetCategories.html
 */
class GetCategoriesResponse extends Response
{
    /** @var int CategoryCount  */
    public $count;
    /** @var string CategoryVersion  */
    public $version;
    /** @var double MinimumReservePrice  */
    public $min_reserve_price;
    /** @var bool ReduceReserveAllowed */
    public $reduce_reserve_allowed;
    /** @var bool ReservePriceAllowed  */
    public $reserve_price_allowed;
    /** @var string  */
    public $update_time;

    /** @var \SimpleXMLElement */
    protected $response;
    /**
     * @inheritdoc
     */
    public function __construct(\SimpleXMLElement $response)
    {
        parent::__construct($response);
        $this->response = $response;

        $this->count = XmlHelper::getAsInt($response, 'CategoryCount');
        $this->min_reserve_price = XmlHelper::getAsDouble($response, 'MinimumReservePrice');
        $this->reduce_reserve_allowed = XmlHelper::getAsBoolean($response, 'ReduceReserveAllowed');
        $this->reserve_price_allowed = XmlHelper::getAsBoolean($response, 'ReservePriceAllowed');
        $this->update_time = XmlHelper::getAsString($response, 'UpdateTime');
    }

    /**
     * Get category version
     * @return string|null
     */
    public function getCategoryVersion()
    {
        return XmlHelper::getAsString($this->response, 'CategoryVersion');
    }

    /**
     * Get categories
     * @return array
     */
    public function getCategories()
    {
        $result = array();

        if (!empty($this->response->CategoryArray->Category)) {
            foreach ($this->response->CategoryArray->Category as $category) {
                $category_id = XmlHelper::getAsString($category, 'CategoryID');

                $result[$category_id] = array(
                    'CategoryID' => $category_id,
                    'CategoryLevel' => XmlHelper::getAsString($category, 'CategoryLevel'),
                    'CategoryName' => XmlHelper::getAsString($category, 'CategoryName'),
                    'CategoryParentID' => XmlHelper::getAsString($category, 'CategoryParentID'),
                    'Expired' => XmlHelper::getAsBoolean($category, 'Expired'),
                    'IntlAutosFixedCat' => XmlHelper::getAsBoolean($category, 'IntlAutosFixedCat'),
                    'LeafCategory' => XmlHelper::getAsBoolean($category, 'LeafCategory'),
                    'LSD' => XmlHelper::getAsBoolean($category, 'LSD'),
                    'ORPA' => XmlHelper::getAsBoolean($category, 'ORPA'),
                    'Virtual' => XmlHelper::getAsBoolean($category, 'Virtual'),
                    'BestOfferEnabled' => XmlHelper::getAsBoolean($category, 'BestOfferEnabled'),
                    'B2BVATEnabled' => XmlHelper::getAsBoolean($category, 'B2BVATEnabled'),
                    'AutoPayEnabled' => XmlHelper::getAsBoolean($category, 'AutoPayEnabled'),
                );

                if ($result[$category_id]['CategoryParentID'] == $category_id) {
                    $result[$category_id]['CategoryParentID'] = 0;
                }
            }

            foreach ($result as &$item) {
                $parent_category_id = $item['CategoryParentID'];
                $parent_ids = array();
                $names = array($item['CategoryName']);

                while ($parent_category_id) {
                    $category = $result[$parent_category_id];
                    $parent_ids[] = $parent_category_id;
                    $names[] = $category['CategoryName'];

                    $parent_category_id = $category['CategoryParentID'];
                }

                $item['CategoryParentIds'] = array_reverse($parent_ids);
                $item['CategoryNames'] = array_reverse($names);
            }

            unset($item);
        }

        return $result;
    }
}
