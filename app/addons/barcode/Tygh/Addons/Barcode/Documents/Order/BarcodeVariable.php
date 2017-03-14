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


namespace Tygh\Addons\Barcode\Documents\Order;


use Tygh\Registry;
use Tygh\Template\Document\Order\Context;
use Tygh\Template\IVariable;

/**
 * Class BarcodeVariable
 * @package Tygh\Addons\Barcode\Documents\Order
 */
class BarcodeVariable implements IVariable
{
    public $image;

    /**
     * BarcodeVariable constructor.
     *
     * @param Context $context Instance of order invoice context.
     */
    public function __construct(Context $context)
    {
        $order = $context->getOrder();

        $width = Registry::get('addons.barcode.width');
        $height = Registry::get('addons.barcode.height');
        $url = fn_url(sprintf('image.barcode?id=%s&type=%s&width=%s&height=%s&xres=%s&font=%s&no_session=Y',
            $order->getId(),
            Registry::get('addons.barcode.type'),
            $width,
            $height,
            Registry::get('addons.barcode.resolution'),
            Registry::get('addons.barcode.text_font')
        ));

        $this->image = "<img src=\"{$url}\" alt=\"BarCode\" width=\"{$width}\" height=\"{$height}\">";
    }
}