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
 * Class ProductPicture
 * @package Ebay
 */
class ProductPicture
{
    /** @var string */
    public $hash;

    /** @var string */
    public $path;

    /** @var string */
    public $external_path;

    /**
     * ProductImage constructor.
     *
     * @param string $path
     * @param null|string $external_path
     */
    public function __construct($path, $external_path = null)
    {
        $this->hash = md5($path);
        $this->path = $path;
        $this->external_path = $external_path;
    }

    /**
     * Set external path
     *
     * @param string $external_path
     */
    public function setExternalPath($external_path)
    {
        $this->external_path = $external_path;
    }
}