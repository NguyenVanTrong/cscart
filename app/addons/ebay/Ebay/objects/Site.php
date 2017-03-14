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
 * Class Site
 * @package Ebay\objects
 */
class Site
{
    /** Synchronization period for site (7 days) */
    const SYNCHRONIZATION_PERIOD = 604800;

    /** Synchronization period for site details (1 day) */
    const SYNCHRONIZATION_DETAIL_PERIOD = 86400;

    private static $sites = array();

    /**
     * Check needle synchronization sites from ebay
     * @return bool
     */
    public static function isNeedSynchronization()
    {
        $time = fn_get_storage_data('ebay_site_synchronization_time');

        return empty($time) || $time + self::SYNCHRONIZATION_PERIOD < time();
    }

    /**
     * Check needle synchronization sites from ebay
     * @param int $site_id
     * @return bool
     */
    public static function isNeedDetailSynchronization($site_id)
    {
        $site = Site::getSite($site_id);

        if (empty($site)) {
            return false;
        }

        return empty($site['details']) || $site['detail_update_time'] + self::SYNCHRONIZATION_DETAIL_PERIOD < time();
    }

    /**
     * Update sites from ebay
     * @throws \Exception
     */
    public static function synchronization()
    {
        $client = Client::instance();
        $result = $client->getEbayDetails(array('SiteDetails'));

        if ($result) {
            if (!$result->isSuccess()) {
                throw new \Exception(implode("\n", $result->getErrorMessages()));
            }

            $sites = $result->getSiteDetails();

            if (!empty($sites)) {
                $data = array();

                foreach ($sites as $item) {
                    $data[] = array(
                        'site_id' => $item->site_id,
                        'site' => $item->site,
                    );
                }

                db_query('DELETE FROM ?:ebay_sites WHERE 1');
                db_query('INSERT INTO ?:ebay_sites ?m', $data);

                static::setLastSynchronizationTime(time());
            }
        } else {
            throw new \Exception(implode("\n", $client->getErrors()));
        }
    }

    /**
     * Update sites from ebay
     * @param int $site_id
     * @throws \Exception
     */
    public static function synchronizationDetail($site_id)
    {
        $client = Client::instance();
        $client->setSiteId($site_id);

        $result = $client->getEbayDetails(array('ProductDetails'));

        if ($result) {
            if (!$result->isSuccess()) {
                throw new \Exception(implode("\n", $result->getErrorMessages()));
            }

            $data = array(
                'detail_update_time' => time(),
                'details' => array(
                    'identifier_unavailable_text' => $result->getProductIdentifierUnavailableText()
                )
            );

            Site::update($site_id, $data);
        } else {
            throw new \Exception(implode("\n", $client->getErrors()));
        }
    }

    /**
     * set last synchronization time
     * @param int $time
     */
    public static function setLastSynchronizationTime($time)
    {
        fn_set_storage_data('ebay_site_synchronization_time', $time);
    }

    /**
     * Remove all synchronization times
     */
    public static function clearLastSynchronizationTime()
    {
        fn_set_storage_data('ebay_site_synchronization_time', null);
    }

    /**
     * Get site data
     *
     * @param int $site_id
     * @return array
     */
    public static function getSite($site_id)
    {
        if (!isset(static::$sites[$site_id])) {
            $result = db_get_row("SELECT * FROM ?:ebay_sites WHERE site_id = ?i", $site_id);

            if (!empty($result)) {
                if (isset($result['details'])) {
                    $result['details'] = json_decode($result['details'], true);

                    if (!is_array($result['details'])) {
                        $result['details'] = array();
                    }
                }
            }

            static::$sites[$site_id] = $result;
        }

        return static::$sites[$site_id];
    }

    /**
     * Update site
     * @param int $site_id
     * @param array $data
     */
    public static function update($site_id, array $data)
    {
        if (isset($data['details']) && is_array($data['details'])) {
            $data['details'] = json_encode($data['details']);
        }

        db_query(
            "UPDATE ?:ebay_sites SET ?u WHERE site_id = ?i",
            $data,
            $site_id
        );

        unset(static::$sites[$site_id]);
    }

    /**
     * Get site detail
     *
     * @param int $site_id
     * @param string $detail
     * @return null
     */
    public static function getSiteDetail($site_id, $detail)
    {
        $site = static::getSite($site_id);

        return isset($site['details'][$detail]) ? $site['details'][$detail] : null;
    }
}