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
use Tygh\Enum\ProductTracking;

/**
 * Class ProductVariation
 * @package Ebay
 */
class ProductVariation
{
    /** @var int */
    public $product_id;

    /** @var int */
    public $code;

    /** @var string */
    public $hash;

    /** @var float */
    public $price;

    /** @var int  */
    public $quantity;

    /** @var array */
    protected $combination = array();

    /** @var ProductOption[] */
    protected $options = array();

    /** @var array */
    protected $identifiers = array();

    /** @var Product */
    protected $product;

    /**
     * ProductVariation constructor.
     * @param Product $product
     * @param array $data
     */
    public function __construct(Product $product, array $data)
    {
        if (isset($data['product_code'])) {
            $this->code = $data['product_code'];
        }

        if ($product->tracking === ProductTracking::TRACK_WITH_OPTIONS) {
            $this->quantity = (int) $data['amount'];
        } else {
            $this->quantity = (int) $product->amount;
        }

        $this->price = (float) $product->base_price;
        $this->product = $product;

        if (!empty($data['combination'])) {
            $this->combination = array();

            foreach ($data['combination'] as $option_id => $variant_id) {
                $option = $product->getOption($option_id);
                
                if ($option) {
                    $variant = $option->getVariant($variant_id);

                    if ($variant) {
                        $this->options[$option->id] = $option;
                        $this->combination[$option->id] = $variant->id;
                        $this->price += $variant->getModifyPrice($product->base_price);
                    }
                }
            }
        }

        $this->initIdentifiers();
    }

    /**
     * Get option variants
     *
     * @return ProductOptionVariant[]
     */
    public function getOptionVariants()
    {
        $result = array();

        foreach ($this->combination as $option_id => $variant_id) {
            $option = $this->options[$option_id];
            $result[] = $option->getVariant($variant_id);
        }
        return $result;
    }

    /**
     * Get option by option id
     *
     * @param int $option_id
     * @return ProductOption|null
     */
    public function getOption($option_id)
    {
        return isset($this->options[$option_id]) ? $this->options[$option_id] : null;
    }

    /**
     * Get sku
     *
     * @return string
     */
    public function getSku()
    {
        //TODO generate sku by combination code
        $result = '';
        $combination = $this->combination;
        ksort($combination);

        foreach ($combination as $option_id => $variant_id) {
            $option = $this->getOption($option_id);

            if ($option) {
                $variant = $option->getVariant($variant_id);

                if ($variant) {
                    $result .= $option->name . $variant->name;
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getIdentifiers()
    {
        return $this->identifiers;
    }

    /**
     * @return array
     */
    public function getCombination()
    {
        return $this->combination;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $combination = $this->combination;
        ksort($combination);
        $result = array(
            'sku' => $this->getSku(),
            'price' => $this->price,
            'quantity' => $this->quantity,
            'variants' => array()
        );

        foreach ($combination as $option_id => $variant_id) {
            $option = $this->getOption($option_id);

            if ($option) {
                $variant = $option->getVariant($variant_id);

                if ($variant) {
                    $result['variants'][$option->name] = $variant->name;
                }
            }
        }

        return $result;
    }

    /**
     * Init identifiers
     */
    private function initIdentifiers()
    {
        $this->identifiers = array();
        $template = $this->product->getTemplate();
        $external_category_id = $this->product->getExternalCategoryId();

        if ($template) {
            $codes = $template->getEnabledIdentifierCodes($external_category_id);

            foreach ($codes as $code) {
                $value = $template->getVariationIdentifierValue($code);

                if ($value) {
                    if ($value == 'code') {
                        $this->identifiers[$code] = new ProductIdentifier($code, $this->code);
                    } elseif ($value == 'not_apply') {
                        $this->identifiers[$code] = new ProductIdentifier($code, $template->getIdentifierUnavailableText());
                    }
                }
            }
        }
    }

    /**
     * Re init identifiers
     */
    public function reInitIdentifiers()
    {
        $this->initIdentifiers();
    }
}
