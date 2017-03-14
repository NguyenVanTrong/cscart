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


namespace Ebay\objects;
use Ebay\Client;

/**
 * Class Shipping
 * @package Ebay\objects
 */
class Shipping
{
    /** Synchronization period for shipping (1 day) */
    const SYNCHRONIZATION_PERIOD = 86400;

    /**
     * Check needle synchronization sites from ebay
     * @param int $site_id
     * @return bool
     */
    public static function isNeedSynchronization($site_id)
    {
        $data = static::getLastSynchronizationTimes();

        return empty($data[$site_id]) || $data[$site_id] + self::SYNCHRONIZATION_PERIOD < time();
    }

    /**
     * Synchronization shipping services from ebay
     * @param int $site_id
     * @throws \Exception
     */
    public static function synchronization($site_id)
    {
        $client = Client::instance();
        $client->setSiteId($site_id);

        $result = $client->getEbayDetails('ShippingServiceDetails');

        if ($result) {
            if (!$result->isSuccess()) {
                throw new \Exception(implode("\n", $result->getErrorMessages()));
            }

            $services = $result->getShippingServiceDetails();
            $data = array();

            foreach ($services as $item) {
                if ($item->valid_for_selling_flow) {
                    $data[] = array(
                        'service_id' => $item->shipping_service_id,
                        'name' => $item->shipping_service,
                        'description' => $item->description,
                        'service_type' => implode(',', $item->service_type),
                        'is_international' => $item->international_service ? 'Y' : 'N',
                        'category' => $item->shipping_category,
                        'ship_days_max' => $item->shipping_time_max,
                        'ship_days_min' => $item->shipping_time_min,
                        'package' => implode(',', $item->shipping_package),
                        'carrier' => implode(',', $item->shipping_carrier),
                        'weight_required' => $item->weight_required ? 'Y' : 'N',
                        'selling_flow' => 'Y',
                        'dimensions_required' => $item->dimensions_required ? 'Y' : 'N',
                        'surcharge_applicable' => $item->surcharge_applicable ? 'Y' : 'N',
                        'expedited_service' => $item->expedited_service ? 'Y' : 'N',
                        'detail_version' => $item->detail_version,
                        'update_timestamp' => $item->update_time,
                        'site_id' => $site_id
                    );
                }
            }

            db_query('DELETE FROM ?:ebay_shippings WHERE site_id = ?i', $site_id);
            db_query('INSERT INTO ?:ebay_shippings ?m', $data);

            static::setLastSynchronizationTime($site_id, time());
        } else {
            throw new \Exception(implode("\n", $client->getErrors()));
        }
    }

    /**
     * set last synchronization time
     * @param int $site_id
     * @param int $time
     */
    public static function setLastSynchronizationTime($site_id, $time)
    {
        $data = static::getLastSynchronizationTimes();
        $data[$site_id] = $time;

        fn_set_storage_data('ebay_shipping_synchronization_time', json_encode($data));
    }

    /**
     * Remove all synchronization times
     */
    public static function clearLastSynchronizationTime()
    {
        fn_set_storage_data('ebay_shipping_synchronization_time', null);
    }

    protected static function getLastSynchronizationTimes()
    {
        $result = fn_get_storage_data('ebay_shipping_synchronization_time');
        $result = json_decode($result, true);

        if (!is_array($result)) {
            $result = array();
        }

        return $result;
    }
}