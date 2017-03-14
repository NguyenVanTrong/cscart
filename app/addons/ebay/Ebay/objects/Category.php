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
 * Class Category
 * @package Ebay\objects
 */
class Category
{
    /** Synchronization period for category (1 day) */
    const SYNCHRONIZATION_PERIOD = 86400;

    protected static $categories = array();
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
     * Synchronization categories from ebay
     * @param int $site_id
     * @throws \Exception
     */
    public static function synchronization($site_id)
    {
        $client = Client::instance();
        $client->setSiteId($site_id);

        $result = $client->getCategories();

        if ($result) {
            if (!$result->isSuccess()) {
                throw new \Exception(implode("\n", $result->getErrorMessages()));
            }
            $category_version = $result->getCategoryVersion();
        } else {
            throw new \Exception(implode("\n", $client->getErrors()));
        }

        $versions = static::getCategoryVersions();

        if (empty($versions[$site_id]) || $versions[$site_id] != $category_version) {
            $result = $client->getCategories(array(), null, 'ReturnAll');

            if ($result) {
                if (!$result->isSuccess()) {
                    throw new \Exception(implode("\n", $result->getErrorMessages()));
                }
                $categories = $result->getCategories();
                $data = array();

                foreach ($categories as $item) {
                    $path = $item['CategoryParentIds'];
                    $path[] = $item['CategoryID'];

                    $data[] = array(
                        'category_id' => $item['CategoryID'],
                        'parent_id' => $item['CategoryParentID'],
                        'leaf' => $item['LeafCategory'] ? 'Y' : 'N',
                        'name' => $item['CategoryName'],
                        'level' => $item['CategoryLevel'],
                        'id_path' => implode(',', $path),
                        'full_name' => implode(' > ', $item['CategoryNames']),
                        'site_id' => $site_id,
                        'features' => ''
                    );
                }

                db_query('DELETE FROM ?:ebay_categories WHERE site_id = ?i', $site_id);
                $limit = 60;
                $part_count = (int) ceil(count($data) / $limit);

                for ($i = 0; $i < $part_count; $i++) {
                    $part_data = array_slice($data, $limit * $i, $limit);
                    db_query('INSERT INTO ?:ebay_categories ?m', $part_data);
                }

                static::setCategoryVersion($site_id, $category_version);
                static::setLastSynchronizationTime($site_id, time());
            } else {
                throw new \Exception(implode("\n", $client->getErrors()));
            }
        } elseif ($versions[$site_id] == $category_version) {
            static::setLastSynchronizationTime($site_id, time());
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

        fn_set_storage_data('ebay_category_synchronization_time', json_encode($data));
    }

    /**
     * Remove all synchronization times
     */
    public static function clearLastSynchronizationTime()
    {
        fn_set_storage_data('ebay_category_synchronization_time', null);
    }

    protected static function getLastSynchronizationTimes()
    {
        $result = fn_get_storage_data('ebay_category_synchronization_time');
        $result = json_decode($result, true);

        if (!is_array($result)) {
            $result = array();
        }

        return $result;
    }

    /**
     * Set ebay category version
     * @param int $site_id
     * @param string $version
     */
    public static function setCategoryVersion($site_id, $version)
    {
        $data = static::getCategoryVersions();
        $data[$site_id] = $version;

        fn_set_storage_data('ebay_category_version', json_encode($data));
    }

    /**
     * Remove all category versions
     */
    public static function clearCategoryVersions()
    {
        fn_set_storage_data('ebay_category_version', null);
    }

    protected static function getCategoryVersions()
    {
        $result = fn_get_storage_data('ebay_category_version');
        $result = json_decode($result, true);

        if (!is_array($result)) {
            $result = array();
        }

        return $result;
    }

    /**
     * Get category data
     * @param int $site_id
     * @param int $category_id
     * @return array
     */
    public static function getCategory($site_id, $category_id)
    {
        if (!isset(static::$categories[$site_id][$category_id])) {
            $result = db_get_row("SELECT * FROM ?:ebay_categories WHERE site_id = ?i AND category_id = ?i", $site_id, $category_id);

            if (!empty($result)) {
                if (isset($result['features'])) {
                    $result['features'] = json_decode($result['features'], true);

                    if (!is_array($result['features'])) {
                        $result['features'] = array();
                    }
                }
            }

            static::$categories[$site_id][$category_id] = $result;
        }

        return static::$categories[$site_id][$category_id];
    }

    /**
     * Update category
     * @param int $site_id
     * @param int $category_id
     * @param array $data
     */
    public static function update($site_id, $category_id, array $data)
    {
        if (isset($data['features']) && is_array($data['features'])) {
            $data['features'] = json_encode($data['features']);
        }

        db_query(
            "UPDATE ?:ebay_categories SET ?u  WHERE site_id = ?i AND category_id = ?i",
            $data,
            $site_id,
            $category_id
        );

        unset(static::$categories[$site_id][$category_id]);
    }
}