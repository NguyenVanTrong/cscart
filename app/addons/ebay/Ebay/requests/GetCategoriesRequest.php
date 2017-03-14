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
 * Class GetCategoriesRequest
 * @package Ebay\requests
 * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/GetCategories.html
 */
class GetCategoriesRequest extends Request
{
    /** @var array */
    public $parents = array();
    /** @var string */
    public $site_id;
    /** @var int  */
    public $level_limit;
    /** @var string  */
    public $detail_level;

    /**
     * Constructor
     *
     * @param string|array $parents Category parents
     * @param int          $level
     * @param string       $detail
     */
    public function __construct($parents, $level, $detail)
    {
        $this->parents = (array) $parents;
        $this->level_limit = $level;
        $this->detail_level = $detail;
    }

    /**
     * @inheritdoc
     */
    public function xml()
    {
        $xml = '';

        if (!empty($this->parents)) {
            $xml .= '<CategoryParent>' . implode('</CategoryParent><CategoryParent>', $this->parents) . '</CategoryParent>';
        }

        if (!empty($this->site_id)) {
            $xml .= "<CategorySiteID>{$this->site_id}</CategorySiteID>";
        }

        if ($this->level_limit != null) {
            $xml .= "<LevelLimit>{$this->level_limit}</LevelLimit>";
        }

        if ($this->detail_level != null) {
            $xml .= "<DetailLevel>{$this->detail_level}</DetailLevel>";
        }

        return $xml;
    }
}
