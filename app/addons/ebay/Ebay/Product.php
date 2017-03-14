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
use Tygh\Enum\ProductFeatures;
use Tygh\Helpdesk;

/**
 * Class Product
 * @package Ebay
 */
class Product
{
    /** Product not exported on ebay */
    const STATUS_NOT_EXPORTED = 0;

    /** Product active sale on ebay */
    const STATUS_ACTIVE = 1;

    /** Product sale closed on ebay */
    const STATUS_CLOSED = 2;

    /** Gram on lbs */
    const GRAM_ON_LBS_UNIT = 453.6;

    /** Oz on lbs */
    const OZ_ON_LBS_UNIT = 16;

    /** Gram on kg */
    const GRAM_ON_KG_UNIT = 1000;

    /** Max title length */
    const MAX_TITLE_LENGTH = 80;

    /** @var int */
    public $id;

    /** @var string */
    public $code;

    /** @var int */
    public $template_id;

    /** @var int */
    public $company_id;

    /** @var int */
    public $main_category_id;

    /** @var string */
    public $original_title;

    /** @var string */
    public $title;

    /** @var string */
    public $description;

    /** @var string */
    public $package_type;

    /** @var string */
    public $weight;

    /** @var string */
    public $weight_major;

    /** @var string */
    public $weight_minor;

    /** @var string */
    public $shipping_box_height;

    /** @var string */
    public $shipping_box_length;

    /** @var string */
    public $shipping_box_width;

    /** @var float */
    public $price;

    /** @var float */
    public $base_price;

    /** @var float */
    public $amount;

    /** @var string */
    public $tracking;

    /** @var string */
    public $status;

    /** @var string */
    public $hash;

    /** @var array */
    public $pictures = array();

    /** @var int */
    protected $external_id;

    /** @var ProductFeature[] */
    protected $features;

    /** @var ProductOption[] */
    protected $options;

    /** @var ProductPicture[]  */
    protected $all_pictures;

    /** @var ProductVariation[] */
    protected $combinations;

    /** @var array */
    protected $external_pictures;

    /** @var array */
    protected $external_combinations;

    /** @var ProductIdentifier[] */
    protected $identifiers;

    /** @var array Categories */
    public static $categories = array();


    /**
     * Construct
     * @param int|array $product Product id or array of product data
     * ```php
     * array(
     *  'product_id' => int,
     *  'ebay_template_id' => int,
     *  ...
     * )
     * ```
     */
    public function __construct($product)
    {
        if (!is_array($product)) {
            $product_id = (int) $product;
            $auth = \Tygh::$app['session']['auth'];

            $product = fn_get_product_data($product_id, $auth, CART_LANGUAGE, '', true, true, true, false, false, true, false, true);
        }

        if (!empty($product)) {
            $this->init($product);
        }
    }

    /**
     * Init model
     * @param array $data
     */
    protected function init(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        if (isset($data['product_id'])) {
            $this->id = (int) $data['product_id'];
        }

        if (isset($data['ebay_template_id'])) {
            $this->template_id = (int) $data['ebay_template_id'];
        }

        if (isset($data['product_code'])) {
            $this->code = $data['product_code'];
        }

        if (isset($data['product'])) {
            $this->title = $data['product'];
            $this->original_title = $data['product'];
        }

        if (isset($data['full_description'])) {
            $this->description = $data['full_description'];
        }

        if (isset($data['box_length'])) {
            $this->shipping_box_length = $data['box_length'];
        }

        if (isset($data['box_width'])) {
            $this->shipping_box_width = $data['box_width'];
        }

        if (isset($data['box_height'])) {
            $this->shipping_box_height = $data['box_height'];
        }

        if (isset($data['main_category'])) {
            $this->main_category_id = $data['main_category'];
        }

        if (isset($data['ebay_status'])) {
            $this->status = (int) $data['ebay_status'];
        }

        if ($data['ebay_override_price'] === 'Y') {
            $this->price = $data['ebay_price'];
            $this->base_price = $data['ebay_price'];
        }

        if ($data['override'] === 'Y') {
            if (!empty($data['ebay_title'])) {
                $this->title = $data['ebay_title'];
            }

            if (!empty($data['ebay_description'])) {
                $this->description = $data['ebay_description'];
            }
        }

        $this->title = substr(strip_tags($this->title), 0, static::MAX_TITLE_LENGTH);

        if (isset($data['product_features'])) {
            $this->initFeatures((array) $data['product_features']);
        }

        if (isset($data['product_options'])) {
            $this->initOptions((array) $data['product_options'], isset($data['combinations']) ? $data['combinations'] : array());
        }

        if (!empty($data['main_pair']['detailed']['http_image_path'])) {
            $this->pictures[] = $data['main_pair']['detailed']['http_image_path'];
        }

        if (!empty($data['image_pairs'])) {
            foreach ($data['image_pairs'] as $item) {
                if (!empty($item['detailed']['http_image_path'])) {
                    $this->pictures[] = $item['detailed']['http_image_path'];
                }
            }
        }

        $this->initWeight();
        $this->initProductIdentifiers();
    }

    /**
     * Load product external data
     */
    private function loadExternalData()
    {
        if (empty($this->template_id)) {
            return;
        }

        $data = db_get_row(
            'SELECT * FROM ?:ebay_template_products WHERE product_id = ?i AND template_id = ?i',
            $this->id,
            $this->template_id
        );

        if (!empty($data)) {
            $this->external_id = $data['ebay_item_id'];

            if (!empty($data['pictures'])) {
                $this->external_pictures = @unserialize($data['pictures']);

                if (!is_array($this->external_pictures)) {
                    $this->external_pictures = array();
                }
            }

            if (!empty($data['combinations'])) {
                $this->external_combinations = @unserialize($data['combinations']);

                if (!is_array($this->external_combinations)) {
                    $this->external_combinations = array();
                }
            }
        }
    }

    /**
     * Load options and combinations
     */
    private function loadOptions()
    {
        $this->options = array();
        $this->combinations = array();
        $options = fn_get_product_options($this->id, CART_LANGUAGE, true, true);
        list($combinations) = fn_get_product_options_inventory(array('product_id' => $this->id));

        $this->initOptions($options, $combinations);
    }

    /**
     * Init options
     *
     * @param array $options
     * @param array $combinations
     */
    private function initOptions(array $options, array $combinations)
    {
        $this->options = array();
        $this->combinations = array();

        foreach ($options as $item) {
            $option = new ProductOption($item);
            $this->options[$option->id] = $option;
        }

        foreach ($combinations as $item) {
            $combination = new ProductVariation($this, $item);
            $this->combinations[$combination->getSku()] = $combination;
        }
    }

    /**
     * Init features
     *
     * @param array $features
     */
    private function initFeatures(array $features)
    {
        $this->features = array();
        $this->initFeaturesRecursive($features);
    }

    /**
     * Init features recursive
     *
     * @param array $features
     */
    private function initFeaturesRecursive(array $features)
    {
        foreach ($features as $item) {
            if ($item['feature_type'] == ProductFeatures::GROUP) {
                if (!empty($item['subfeatures'])) {
                    $this->initFeaturesRecursive($item['subfeatures']);
                }
            } else {
                $feature = new ProductFeature($item);
                $this->features[$feature->id] = $feature;
            }
        }
    }

    /**
     * Init weight major and minor
     */
    private function initWeight()
    {
        $template = $this->getTemplate();

        if ($template) {
            $grams = $template->getMeasureWeight();

            if ($template->getMeasureType() == 'English') {
                $divider = static::GRAM_ON_LBS_UNIT;
                $rate = static::OZ_ON_LBS_UNIT;
            } else {
                $divider = static::GRAM_ON_KG_UNIT;
                $rate = static::GRAM_ON_KG_UNIT;
            }

            $this->weight_major = floor($this->weight * $grams / $divider);
            $this->weight_minor = ($this->weight - $this->weight_major) * $rate;
        }
    }

    /**
     * Init product identifiers
     */
    private function initProductIdentifiers()
    {
        if ($this->identifiers === null) {
            $template = $this->getTemplate();
            $external_category_id = $this->getExternalCategoryId();

            $this->identifiers = array();

            if ($template) {
                $codes = $template->getEnabledIdentifierCodes($external_category_id);

                foreach ($codes as $code) {
                    $value = $template->getProductIdentifierValue($code);

                    if ($value) {
                        if ($value > 0) { //get value from product features
                            $feature = $this->getFeature($value);

                            if ($feature) {
                                $identifier = new ProductIdentifier($code, $feature->getValue());
                                $feature->setProductIdentifier($identifier);
                                $this->identifiers[$code] = $identifier;
                            }
                        } elseif ($value == 'code') {
                            $this->identifiers[$code] = new ProductIdentifier($code, $this->code);
                        } elseif ($value == 'not_apply') {
                            $this->identifiers[$code] = new ProductIdentifier($code, $template->getIdentifierUnavailableText());
                        }
                    }
                }
            }
        }
    }

    /**
     * Set template
     * @param int $template_id
     */
    public function setTemplateId($template_id)
    {
        $this->template_id = $template_id;
        $this->loadExternalData();
    }

    /**
     * Return product ebay template
     * @return Template
     */
    public function getTemplate()
    {
        return Template::getById($this->template_id);
    }

    /**
     * Return product UUID
     * @return string
     */
    public function getUUID()
    {
        return md5($this->id . Helpdesk::getStoreKey() . $this->template_id);
    }

    /**
     * Return primary category id
     * @return string
     */
    public function getExternalCategoryId()
    {
        $main_category = $this->getCategory();

        if (!empty($main_category['ebay_category_id']) && $main_category['ebay_site_id'] == $this->getTemplate()->site_id) {
            return $main_category['ebay_category_id'];
        }

        return $this->getTemplate()->category;
    }

    /**
     * Return second category id
     * @return string
     */
    public function getExternalSecondCategoryId()
    {
        $main_category = $this->getCategory();

        if (!empty($main_category['ebay_category_id']) && $main_category['ebay_site_id'] == $this->getTemplate()->site_id) {
            return false;
        }

        return $this->getTemplate()->sec_category;
    }

    /**
     * Return product options
     * @return ProductOption[]
     */
    public function getOptions()
    {
        if ($this->options === null) {
            $this->loadOptions();
        }

        return $this->options;
    }

    /**
     * Get product option by option id
     *
     * @param int $option_id
     * @return ProductOption|null
     */
    public function getOption($option_id)
    {
        $options = $this->getOptions();

        return isset($options[$option_id]) ? $options[$option_id] : null;
    }

    /**
     * Return product combinations
     * @return ProductVariation[]
     */
    public function getCombinations()
    {
        if ($this->combinations === null) {
            $this->loadOptions();
        }

        return $this->combinations;
    }

    /**
     * Calculate hash
     *
     * @return string
     */
    public function getHash()
    {
        $data = array(
            'price' => $this->price,
            'title' => $this->title,
            'description' => $this->description,
        );

        if (!empty($this->features)) {
            $data['product_features'] = serialize($this->features);
        }

        return fn_crc32(implode('_', $data));
    }

    /**
     * Save ebay data
     *
     * @return mixed
     */
    public function saveExternalData()
    {
        db_query('REPLACE INTO ?:ebay_template_products ?e', array(
            'ebay_item_id' => $this->external_id,
            'template_id' => $this->template_id,
            'product_id' => $this->id,
            'pictures' => serialize($this->external_pictures),
            'combinations' => serialize($this->external_combinations),
            'product_hash' => $this->getHash()
        ));
    }

    /**
     * Save product template id
     * @return int
     */
    public function saveTemplateId()
    {
        return static::updateProductTemplateId($this->id, $this->template_id);
    }

    /**
     * Update product template id
     * @param $product_id
     * @param $template_id
     * @return int
     */
    public static function updateProductTemplateId($product_id, $template_id)
    {
        db_query("UPDATE ?:ebay_template_products SET template_id = ?i WHERE product_id = ?i", $template_id, $product_id);

        return db_query("UPDATE ?:products SET ebay_template_id = ?i WHERE product_id = ?i", $template_id, $product_id);
    }

    /**
     * Delete ebay data by product external id
     *
     * @param  string $external_id
     * @return int
     */
    public static function deleteExternalData($external_id)
    {
        return db_query("DELETE FROM ?:ebay_template_products WHERE ebay_item_id = ?i", $external_id);
    }

    /**
     * Return template product ids
     *
     * @param  int   $template_id
     * @return array
     */
    public static function getTemplateProductIds($template_id)
    {
        return db_get_fields(
            "SELECT product_id FROM ?:products WHERE ebay_template_id = ?i",
            $template_id
        );
    }

    /**
     * Return all exported to ebay product ids
     *
     * @return array
     */
    public static function getExportedProductIds()
    {
        return db_get_fields("SELECT product_id FROM ?:ebay_template_products");
    }

    /**
     * Set external picture path
     *
     * @param ProductPicture $picture
     * @param string $external_path
     */
    public function setExternalPicture(ProductPicture $picture, $external_path)
    {
        $picture->setExternalPath($external_path);
        $this->external_pictures[$picture->hash] = $external_path;
    }

    /**
     * Get external picture path
     *
     * @param string $path
     * @return bool|string
     */
    public function getExternalPicture($path)
    {
        $hash = md5($path);

        if (isset($this->external_pictures[$hash])) {
            return $this->external_pictures[$hash];
        }

        return false;
    }

    /**
     * Return array product statuses
     * @return array
     */
    public static function getStatuses()
    {
        return array(
            static::STATUS_NOT_EXPORTED => __('ebay_product_status_not_exported'),
            static::STATUS_ACTIVE => __('ebay_product_status_active'),
            static::STATUS_CLOSED => __('ebay_product_status_closed'),
        );
    }

    /**
     * Update product status
     *
     * @param  int  $product_id Product id
     * @param  int  $status     eBay product status
     * @return bool
     */
    public static function updateStatus($product_id, $status)
    {
        return db_query("UPDATE ?:products SET ebay_status = ?i WHERE product_id = ?i", $status, $product_id) > 0;
    }

    /**
     * Update product status on active
     * @return bool
     */
    public function setStatusActive()
    {
        return static::updateStatus($this->id, static::STATUS_ACTIVE);
    }

    /**
     * Update product status on closed
     * @return bool
     */
    public function setStatusClosed()
    {
        return static::updateStatus($this->id, static::STATUS_CLOSED);
    }

    /**
     * Return true if status active
     * @return bool
     */
    public function statusIsActive()
    {
        return $this->status === static::STATUS_ACTIVE;
    }

    /**
     * Return true if status closed
     * @return bool
     */
    public function statusIsClosed()
    {
        return $this->status === static::STATUS_CLOSED;
    }

    /**
     * Return product main category data
     * @return bool
     */
    protected function getCategory()
    {
        if (!isset(static::$categories[$this->main_category_id])) {
            $category = fn_get_category_data(
                $this->main_category_id,
                CART_LANGUAGE,
                '',
                false
            );

            if (!empty($category)) {
                static::$categories[$this->main_category_id] = $category;
            }
        }

        return isset(static::$categories[$this->main_category_id]) ? static::$categories[$this->main_category_id] : false;
    }

    /**
     * @param array $values
     */
    public function setExternalCombinations(array $values)
    {
        $this->external_combinations = array();

        foreach ($values as $item) {
            if ($item instanceof ProductVariation) {
                $item = $item->toArray();
            }

            $this->external_combinations[$item['sku']] = $item;
        }
    }

    /**
     * Get product identifiers
     * 
     * @return array
     */
    public function getIdentifiers()
    {
        return $this->identifiers;
    }
    

    /**
     * Get features
     *
     * @return ProductFeature[]
     */
    public function getFeatures()
    {
        return $this->features;
    }

    /**
     * Get feature by feature id
     * 
     * @param int $feature_id
     * @return ProductFeature|null
     */
    public function getFeature($feature_id)
    {
        return isset($this->features[$feature_id]) ? $this->features[$feature_id] : null;
    }

    /**
     * Get external product id
     *
     * @return int|null
     */
    public function getExternalId()
    {
        if ($this->external_id === null) {
            $this->external_id = 0;
            $this->loadExternalData();
        }

        return $this->external_id;
    }

    /**
     * Set external product id
     *
     * @param int $external_id
     */
    public function setExternalId($external_id)
    {
        $this->external_id = $external_id;
    }

    /**
     * Get ebay product images
     *
     * @return array
     */
    public function getExternalPictures()
    {
        if ($this->external_pictures === null) {
            $this->external_pictures = array();
            $this->loadExternalData();
        }

        return $this->external_pictures;
    }

    /**
     * Get ebay product combinations
     *
     * @return array
     */
    public function getExternalCombinations()
    {
        if ($this->external_combinations === null) {
            $this->external_combinations = array();
            $this->loadExternalData();
        }

        return $this->external_combinations;
    }

    /**
     * Get product pictures
     *
     * @return ProductPicture[]
     */
    public function getPictures()
    {
        if ($this->all_pictures === null) {
            $this->all_pictures = array();

            foreach ($this->pictures as $path) {
                $image = $this->createPicture($path);
                $this->all_pictures[$image->hash] = $image;
            }

            $options = $this->getOptions();

            foreach ($options as $option) {
                foreach ($option->getVariants() as $variant) {
                    $image = $this->createPicture($variant->picture);
                    $this->all_pictures[$image->hash] = $image;
                }
            }
        }

        return $this->all_pictures;
    }

    /**
     * Get picture by hash
     *
     * @param string $hash
     * @return ProductPicture|null
     */
    public function getPicture($hash)
    {
        $pictures = $this->getPictures();

        return isset($pictures[$hash]) ? $pictures[$hash] : null;
    }

    /**
     * @param string $path
     * @return ProductPicture
     */
    private function createPicture($path)
    {
        $external_pictures = $this->getExternalPictures();
        $image = new ProductPicture($path);

        if (isset($external_pictures[$image->hash])) {
            $image->setExternalPath($external_pictures[$image->hash]);
        }

        return $image;
    }

    /**
     * Re init product identifiers
     */
    public function reInitProductIdentifiers()
    {
        $this->identifiers = null;
        $this->initProductIdentifiers();

        if ($this->combinations) {
            foreach ($this->combinations as $combination) {
                $combination->reInitIdentifiers();
            }
        }
    }
}
