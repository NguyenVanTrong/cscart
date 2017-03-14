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

/**
 * Class ProductOption
 * @package Ebay
 */
class ProductOption
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $type;

    /** @var bool */
    public $inventory = false;

    /** @var ProductOptionVariant[] */
    protected $variants = array();

    /**
     * ProductOption constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (isset($data['option_id'])) {
            $this->id = (int) $data['option_id'];
        }

        if (isset($data['option_name'])) {
            $this->name = trim($data['option_name']);
        }

        if (isset($data['inventory'])) {
            $this->inventory = $data['inventory'] === 'Y';
        }

        if (!empty($data['variants'])) {
            $this->setVariants($data['variants']);
        }
    }

    /**
     * Return option variants
     * @return ProductOptionVariant[]
     */
    public function getVariants()
    {
        return $this->variants;
    }

    /**
     * Get variant by variant id
     *
     * @param int $variant_id
     * @return ProductOptionVariant|null
     */
    public function getVariant($variant_id)
    {
        return isset($this->variants[$variant_id]) ? $this->variants[$variant_id] : null;
    }

    /**
     * @param array $variants
     */
    private function setVariants(array $variants)
    {
        foreach ($variants as $item) {
            $variant = new ProductOptionVariant($item);
            $this->variants[$variant->id] = $variant;
        }
    }
}
