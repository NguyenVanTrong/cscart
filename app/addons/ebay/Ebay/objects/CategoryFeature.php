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
 * Class CategoryFeature
 * @package Ebay\objects
 */
class CategoryFeature
{
    /** Synchronization period for category features (1 day) */
    const SYNCHRONIZATION_PERIOD = 86400;

    /**
     * Check needle synchronization category features from ebay
     * @param int $site_id
     * @param int $category_id
     * @return bool
     */
    public static function isNeedSynchronization($site_id, $category_id)
    {
        $data = Category::getCategory($site_id, $category_id);

        if (empty($data)) {
            return false;
        }

        return empty($data['features']) || $data['feature_update_time'] + self::SYNCHRONIZATION_PERIOD < time();
    }

    /**
     * Synchronization categories from ebay
     * @param int $site_id
     * @param int $category_id
     * @throws \Exception
     */
    public static function synchronization($site_id, $category_id)
    {
        $client = Client::instance();
        $client->setSiteId($site_id);

        $features_list = array(
            'PayPalRequired',
            'EANEnabled',
            'UPCEnabled',
            'ISBNEnabled',
            'BrandMPNIdentifierEnabled',
            'VariationsEnabled',
            'MinimumReservePrice',
            'ReturnPolicyEnabled',
            'PaymentMethods',
            'StoreInventoryEnabled',
            'ListingDurations',
            'ConditionEnabled',
            'ConditionValues',
            'HandlingTimeEnabled',
            'StoreOwnerExtendedListingDurations'
        );

        $result = $client->getCategoryFeatures($category_id, $features_list);

        if ($result) {
            if (!$result->isSuccess()) {
                throw new \Exception(implode("\n", $result->getErrorMessages()));
            }

            $features = array(
                'payment_methods' => $result->getPaymentMethods(),
                'paypal_required' => $result->isPayPalRequired(),
                'condition_enabled' => $result->isConditionEnabled(),
                'conditions' => $result->getConditions(),
                'listing_duration' => $result->getListingDurations('FixedPriceItem'),
                'ean_enabled' => $result->isEanEnabled(),
                'upc_enabled' => $result->isUpcEnabled(),
                'isbn_enabled' => $result->isIsbnEnabled(),
                'brand_mpn_enabled' => $result->isBrandMpnEnabled(),
                'ean_required' => $result->isEanRequired(),
                'upc_required' => $result->isUpcRequired(),
                'isbn_required' => $result->isIsbnRequired(),
                'brand_mpn_required' => $result->isIsbnRequired()
            );

            $data = array(
                'features' => json_encode($features),
                'feature_update_time' => time(),
            );

            Category::update($site_id, $category_id, $data);
        } else {
            throw new \Exception(implode("\n", $client->getErrors()));
        }
    }

    /**
     * Get feature value
     *
     * @param int $site_id
     * @param int $category_id
     * @param string $feature
     * @return mixed
     */
    public static function getFeatureValue($site_id, $category_id, $feature)
    {
        $category = Category::getCategory($site_id, $category_id);
        $result = null;

        if ($category && isset($category['features'][$feature])) {
            $result = $category['features'][$feature];
        }

        return $result;
    }

    /**
     * Is EAN enabled
     *
     * @param int $site_id
     * @param int $category_id
     * @return bool
     */
    public static function isEanEnabled($site_id, $category_id)
    {
        return (bool) static::getFeatureValue($site_id, $category_id, 'ean_enabled');
    }

    /**
     * Is UPC enabled
     *
     * @param int $site_id
     * @param int $category_id
     * @return bool
     */
    public static function isUpcEnabled($site_id, $category_id)
    {
        return (bool) static::getFeatureValue($site_id, $category_id, 'upc_enabled');
    }

    /**
     * Is ISBN enabled
     *
     * @param int $site_id
     * @param int $category_id
     * @return bool
     */
    public static function isIsbnEnabled($site_id, $category_id)
    {
        return (bool) static::getFeatureValue($site_id, $category_id, 'isbn_enabled');
    }

    /**
     * Is BrandMPN enabled
     *
     * @param int $site_id
     * @param int $category_id
     * @return bool
     */
    public static function isBrandMpnEnabled($site_id, $category_id)
    {
        return (bool) static::getFeatureValue($site_id, $category_id, 'brand_mpn_enabled');
    }
}