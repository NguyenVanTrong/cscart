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
 * Class ProductFeatureVariant
 * @package Ebay
 */
class ProductFeatureVariant
{
    /** @var int */
    public $id;

    /** @var string  */
    public $name;

    /** @var int */
    public $feature_id;

    /** @var bool */
    public $selected = false;

    /**
     * ProductFeatureVariant constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (isset($data['variant_id'])) {
            $this->id = (int) $data['variant_id'];
        }

        if (isset($data['variant'])) {
            $this->name = $data['variant'];
        }

        if (isset($data['feature_id'])) {
            $this->feature_id = (int) $data['feature_id'];
        }

        if (!empty($data['selected'])) {
            $this->selected = true;
        }
    }
}