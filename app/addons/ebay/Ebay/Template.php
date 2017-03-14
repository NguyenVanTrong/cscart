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

namespace Ebay;
use Ebay\objects\CategoryFeature;
use Ebay\objects\Site;
use Tygh\Registry;
use Tygh\Settings;

/**
 * Class Template
 * @package Ebay
 */
class Template
{
    public $id;
    public $site;
    public $name;
    public $site_id;
    public $user_as_default;
    public $root_category;
    public $category;
    public $sec_root_category;
    public $sec_category;
    public $ebay_duration;
    public $payment_methods = array();
    public $paypal_email;
    public $condition_id;
    public $dispatch_days;
    public $shipping_type;
    public $shippings;
    public $international_shippings;
    public $company_id;
    public $free_shipping;
    public $shipping_cost;
    public $international_shipping_cost;
    public $shipping_cost_additional;
    public $international_shipping_cost_additional;
    public $return_policy;
    public $contact_time;
    public $refund_method;
    public $cost_paid_by;
    public $return_policy_descr;
    public $identifiers;
    protected $measure_type = null;
    protected $measure_weight = null;

    /** @var Template[]  */
    protected static $templates = array();

    /**
     * Constructor
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (isset($data['template_id'])) {
            $this->id = $data['template_id'];
        }

        foreach ($data as $field => $value) {
            if (property_exists($this, $field)) {
                $this->$field = $value;
            }
        }
    }
    /**
     * Return template object
     *
     * @param $id
     * @return Template
     */
    public static function getById($id)
    {
        $id = (int) $id;

        if (!isset(static::$templates[$id])) {
            Registry::set('runtime.skip_sharing_selection', true);
            $result = fn_get_ebay_template($id);
            Registry::set('runtime.skip_sharing_selection', false);
            static::$templates[$id] = new Template($result);
        }

        return static::$templates[$id];
    }

    /**
     * Return default template by company
     * @param  int            $company_id
     * @return Template|false
     */
    public static function getDefaultByCompanyId($company_id)
    {
        $id = db_get_field(
            "SELECT template_id FROM ?:ebay_templates WHERE use_as_default = 'Y' AND company_id = ?i",
            $company_id
        );

        if (!empty($id)) {
            return static::getById($id);
        }

        return false;
    }

    /**
     * Load product count by templates
     *
     * @param array $templates
     */
    public static function loadProductCount(array &$templates)
    {
        if (empty($templates)) {
            return;
        }

        $ids = fn_array_column($templates, 'template_id');

        $sql = "SELECT COUNT(*) AS count, ebay_template_id FROM ?:products"
                . " WHERE ebay_template_id IN (?n) GROUP BY ebay_template_id";

        $result = db_get_hash_array($sql, 'ebay_template_id', $ids);

        foreach ($templates as &$template) {
            $template['product_count'] = 0;

            if (isset($result[$template['template_id']])) {
                $template['product_count'] = $result[$template['template_id']]['count'];
            }
        }

        unset($template);
    }

    /**
    * Get used site ids
    * @param null $company_id
    * @return array
    */
    public static function getUsedSiteIds($company_id = null)
    {
        $condition = '';

        if ($company_id !== null) {
            $condition .= db_quote("AND company_id = ?i", $company_id);
        }

        $result = db_get_fields("SELECT site_id FROM ?:ebay_templates WHERE 1 {$condition} GROUP BY site_id ORDER BY site_id ASC");

        return $result;
    }

    /**
     * Return measure type
     * @return string English/Metric
     */
    public function getMeasureType()
    {
        if ($this->measure_type === null) {
            $this->measure_type = Settings::instance()
                ->getValue('weight_symbol', 'General', $this->company_id)  == 'lbs'  ? 'English' : 'Metric';
        }

        return $this->measure_type;
    }

    /**
     * Return measure weight
     * @return string
     */
    public function getMeasureWeight()
    {
        if ($this->measure_weight === null) {
            $this->measure_weight = Settings::instance()->getValue('weight_symbol_grams', 'General', $this->company_id);
        }

        return $this->measure_weight;
    }

    /**
     * Get product identifier value
     *
     * @param string $code
     * @return null
     */
    public function getProductIdentifierValue($code)
    {
        return isset($this->identifiers['product'][$code]) ? $this->identifiers['product'][$code] : null;
    }

    /**
     * Get variation identifier value
     *
     * @param string $code
     * @return null
     */
    public function getVariationIdentifierValue($code)
    {
        return isset($this->identifiers['variation'][$code]) ? $this->identifiers['variation'][$code] : null;
    }

    /**
     * Get product identifier types
     *
     * @return array
     */
    public static function getProductIdentifierCodeNames()
    {
        return array(
            'UPC' => __('ebay_product_identifier_upc'),
            'EAN' => __('ebay_product_identifier_ean'),
            'ISBN' => __('ebay_product_identifier_isbn'),
            'MPN' => __('ebay_product_identifier_mpn'),
            'BRAND' => __('ebay_product_identifier_brand')
        );
    }

    /**
     * Get variation identifier types
     *
     * @return array
     */
    public static function getVariationIdentifierCodeNames()
    {
        return array(
            'UPC' => __('ebay_product_identifier_upc'),
            'EAN' => __('ebay_product_identifier_ean'),
            'ISBN' => __('ebay_product_identifier_isbn')
        );
    }

    /**
     * Get product identifier unavailable text
     *
     * @return string
     */
    public function getIdentifierUnavailableText()
    {
        return Site::getSiteDetail($this->site_id, 'identifier_unavailable_text');
    }

    /**
     * Get enabled identifiers codes
     *
     * @param int $external_category_id
     * @return array
     */
    public function getEnabledIdentifierCodes($external_category_id)
    {
        $result = array('BRAND', 'MPN');

        if (CategoryFeature::isEanEnabled($this->site_id, $external_category_id)) {
            $result[] = 'EAN';
        }

        if (CategoryFeature::isIsbnEnabled($this->site_id, $external_category_id)) {
            $result[] = 'ISBN';
        }

        if (CategoryFeature::isUpcEnabled($this->site_id, $external_category_id)) {
            $result[] = 'UPC';
        }

        return $result;
    }
}
