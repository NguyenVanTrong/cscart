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
use Tygh\Settings;

/**
 * Class ProductFeature
 * @package Ebay
 */
class ProductFeature
{
    /** @var int */
    public $id;

    /** @var string */
    public $code;

    /** @var string */
    public $name;

    /** @var string */
    public $type;

    /** @var ProductFeatureVariant[] */
    protected $variants = array();

    /** @var ProductFeatureVariant[] */
    protected $selected_variants = array();

    /** @var string */
    protected $value;

    /** @var int|float */
    protected $value_int;

    /** @var null|ProductIdentifier */
    protected $product_identifier;

    /**
     * ProductFeature constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (isset($data['feature_id'])) {
            $this->id = (int) $data['feature_id'];
        }

        if (isset($data['feature_code'])) {
            $this->code = $data['feature_code'];
        }

        if (isset($data['feature_type'])) {
            $this->type = $data['feature_type'];
        }

        if (isset($data['description'])) {
            $this->name = $data['description'];
        }

        if (!empty($data['value'])) {
            $this->value = $data['value'];
        }

        if (!empty($data['value_int'])) {
            $this->value_int = $data['value_int'];
        }

        if (!empty($data['variants'])) {
            $this->setVariants($data['variants']);
        }
    }

    /**
     * @return ProductFeatureVariant[]
     */
    public function getVariants()
    {
        return $this->variants;
    }

    /**
     * Get feature value
     *
     * @return mixed
     */
    public function getValue()
    {
        $result = null;

        if ($this->type == ProductFeatures::SINGLE_CHECKBOX && $this->value == 'Y') {
            $result = __('yes');
        } elseif ($this->type == ProductFeatures::DATE) {
            $result = strftime(Settings::instance()->getValue('date_format', 'Appearance'), $this->value_int);
        } elseif ($this->type == ProductFeatures::MULTIPLE_CHECKBOX && $this->selected_variants) {
            $result = array();

            foreach ($this->selected_variants as $variant) {
                $result[] = $variant->name;
            }
        } elseif (in_array($this->type, array(ProductFeatures::TEXT_SELECTBOX, ProductFeatures::EXTENDED, ProductFeatures::NUMBER_SELECTBOX))) {
            $variant = reset($this->selected_variants);

            if ($variant) {
                $result = $variant->name;
            }
        } elseif ($this->type == ProductFeatures::NUMBER_FIELD) {
            $result = (float) $this->value_int;
        } else {
            $result = $this->value;
        }

        return $result;
    }

    /**
     * @param array $variants
     */
    private function setVariants(array $variants)
    {
        foreach ($variants as $item) {
            $variant = new ProductFeatureVariant($item);

            $this->variants[$variant->id] = $variant;

            if ($variant->selected) {
                $this->selected_variants[$variant->id] = $variant;
            }
        }
    }

    /**
     * @return ProductIdentifier|null
     */
    public function getProductIdentifier()
    {
        return $this->product_identifier;
    }

    /**
     * @param ProductIdentifier|null $product_identifier
     */
    public function setProductIdentifier(ProductIdentifier $product_identifier)
    {
        $this->product_identifier = $product_identifier;
    }
}