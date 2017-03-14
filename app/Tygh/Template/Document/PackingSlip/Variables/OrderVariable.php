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


namespace Tygh\Template\Document\PackingSlip\Variables;

use Tygh\Template\Document\Order\Variables\OrderVariable as BaseOrderVariable;
use Tygh\Template\Document\PackingSlip\Context;
use Tygh\Tools\Formatter;

/**
 * Class OrderVariable
 * @package Tygh\Template\Document\PackingSlip\Variables
 */
class OrderVariable extends BaseOrderVariable
{
    /** @inheritdoc */
    public function __construct(Context $context, array $config, Formatter $formatter)
    {
        parent::__construct($context, $config, $formatter);

        $this->data['shipment'] = $context->getShipment();

        if ($this->data['shipment']) {
            $this->data['shipment']['raw'] = array();
            $this->data['shipment']['raw']['shipment_timestamp'] = $this->data['shipment']['shipment_timestamp'];
            $this->data['shipment']['raw']['order_timestamp'] = $this->data['shipment']['order_timestamp'];

            $this->data['shipment']['shipment_timestamp'] = $formatter->asDatetime($this->data['shipment']['shipment_timestamp']);
            $this->data['shipment']['order_timestamp'] = $formatter->asDatetime($this->data['shipment']['order_timestamp']);
        }
    }

    /** @inheritdoc */
    public static function attributes()
    {
        $attributes = parent::attributes();
        $attributes['shipment'] = array(
            'shipment_id', 'shipment_timestamp', 'comments', 'status', 'order_id', 'order_timestamp',
            's_firstname', 's_lastname', 'company', 'user_id', 'shipping_id', 'shipping', 'tracking_number',
            'carrier', 'carrier_info' => array(
                'name', 'tracking_url'
            ),
            'raw' => array(
                'shipment_timestamp', 'order_timestamp'
            ),
        );

        return $attributes;
    }
}