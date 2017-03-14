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
use Tygh\Registry;

/**
 * Class ProductOptionVariant
 * @package Ebay
 */
class ProductOptionVariant
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var  */
    public $picture;

    /** @var float */
    public $modifier;

    /** @var string */
    public $modifier_type;

    /** @var float */
    public $weight_modifier;

    /** @var string */
    public $weight_modifier_type;

    /**
     * ProductOptionVariant constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (isset($data['variant_id'])) {
            $this->id = (int) $data['variant_id'];
        }

        if (isset($data['variant_name'])) {
            $this->name = $data['variant_name'];
        }

        if (isset($data['modifier'])) {
            $this->modifier = (float) $data['modifier'];
        }

        if (isset($data['modifier_type'])) {
            $this->modifier_type = $data['modifier_type'];
        }

        if (isset($data['weight_modifier'])) {
            $this->weight_modifier = (float) $data['weight_modifier'];
        }

        if (isset($data['weight_modifier_type'])) {
            $this->weight_modifier_type = $data['weight_modifier_type'];
        }

        if (!empty($data['image_pair']['icon']['http_image_path'])) {
            $this->picture = $data['image_pair']['icon']['http_image_path'];
        } else {
            $this->picture = Registry::get('config.http_location') . '/images/no_image.png';
        }
    }

    /**
     * Get modify price
     *
     * @param float $price
     * @return float
     */
    public function getModifyPrice($price)
    {
        if ($this->modifier_type == 'A') {
            return $this->modifier;
        } else {
            return $price * $this->modifier / 100;
        }
    }
}
