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

namespace Ebay\responses;

use Ebay\XmlHelper;

/**
 * Class GetCategoryFeaturesResponse
 * @package Ebay\responses
 * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/GetCategoryFeatures.html
 */
class GetCategoryFeaturesResponse extends Response
{
    /** @var \SimpleXMLElement */
    protected $response;

    /**
     * @inheritdoc
     */
    public function __construct(\SimpleXMLElement $response)
    {
        parent::__construct($response);

        $this->response = $response;
    }

    /**
     * Return listing durations by type of listing
     * @param string $type FixedPriceItem
     * @return array
     */
    public function getListingDurations($type)
    {
        $types = array();
        $durations = array();

        if (isset($this->response->SiteDefaults->ListingDuration)) {
            foreach ($this->response->SiteDefaults->ListingDuration as $duration) {
                /** @var \SimpleXMLElement $duration */
                $attributes = $duration->attributes();
                $types[(string) $attributes['type']] = (int) $duration;
            }
        }

        if (isset($this->response->Category->ListingDuration)) {
            foreach ($this->response->Category->ListingDuration as $duration) {
                /** @var \SimpleXMLElement $duration */
                $attributes = $duration->attributes();
                $types[(string) $attributes['type']] = (int) $duration;
            }
        }

        if (!isset($types[$type])) {
            return array();
        }

        if (isset($this->response->FeatureDefinitions)) {
            foreach ($this->response->FeatureDefinitions as $definition) {
                if (isset($definition->ListingDurations)) {
                    foreach ($definition->ListingDurations as $listing_durations) {
                        foreach ($listing_durations->ListingDuration as $listing_duration) {
                            $attributes = $listing_duration->attributes();
                            $id = (int) $attributes['durationSetID'];

                            foreach ($listing_duration->Duration as $duration) {
                                $durations[$id][] = (string) $duration;
                            }
                        }
                    }
                }
            }
        }

        if (isset($types['FixedPriceItem'])) {
            $fix_price_item_id = $types['FixedPriceItem'];

            if (!empty($this->response->SiteDefaults->StoreOwnerExtendedListingDurations)) {
                foreach ($this->response->SiteDefaults->StoreOwnerExtendedListingDurations->Duration as $duration) {
                    $duration = (string) $duration;

                    if (!in_array($duration, $durations[$fix_price_item_id])) {
                        $durations[$fix_price_item_id][] = $duration;
                    }
                }
            }

            if (!empty($this->response->Category->StoreOwnerExtendedListingDurations)) {
                foreach ($this->response->Category->StoreOwnerExtendedListingDurations->Duration as $duration) {
                    $duration = (string) $duration;

                    if (!in_array($duration, $durations[$fix_price_item_id])) {
                        $durations[$fix_price_item_id][] = $duration;
                    }
                }
            }
        }

        return isset($durations[$types[$type]]) ? $durations[$types[$type]] : array();
    }

    /**
     * Return flag is payPal required
     * @return bool
     */
    public function isPayPalRequired()
    {
        return $this->getFieldBoolean('PayPalRequired');
    }

    /**
     * Return flag is Store Inventory Enabled
     * @return bool
     */
    public function isStoreInventoryEnabled()
    {
        return $this->getFieldBoolean('StoreInventoryEnabled');
    }

    /**
     * Return flag is Return Policy Enabled
     * @return bool
     */
    public function isReturnPolicyEnabled()
    {
        return $this->getFieldBoolean('ReturnPolicyEnabled');
    }

    /**
     * Return flag is Handling Time Enabled
     * @return bool
     */
    public function isHandlingTimeEnabled()
    {
        return $this->getFieldBoolean('HandlingTimeEnabled');
    }

    /**
     * Return flag is Variations Enabled
     * @return bool
     */
    public function isVariationsEnabled()
    {
        return $this->getFieldBoolean('VariationsEnabled');
    }

    /**
     * Return flag is Condition Enabled
     * @return bool
     */
    public function isConditionEnabled()
    {
        return $this->getFieldBoolean('ConditionEnabled');
    }

    /**
     * Return flag is brand and mpn enabled
     * @return bool
     */
    public function isBrandMpnEnabled()
    {
        return $this->getFieldBoolean('BrandMPNIdentifierEnabled');
    }

    /**
     * Return flag is EAN enabled
     * @return bool
     */
    public function isEanEnabled()
    {
        return $this->getFieldBoolean('EANEnabled');
    }

    /**
     * Return flag is UPC enabled
     * @return bool
     */
    public function isUpcEnabled()
    {
        return $this->getFieldBoolean('UPCEnabled');
    }

    /**
     * Return flag is ISBN enabled
     * @return bool
     */
    public function isIsbnEnabled()
    {
        return $this->getFieldBoolean('ISBNEnabled');
    }

    /**
     * Return flag is brand and mpn required
     * @return bool
     */
    public function isBrandMpnRequired()
    {
        return $this->isFieldRequired('BrandMPNIdentifierEnabled');
    }

    /**
     * Return flag is EAN required
     * @return bool
     */
    public function isEanRequired()
    {
        return $this->isFieldRequired('EANEnabled');
    }

    /**
     * Return flag is UPC required
     * @return bool
     */
    public function isUpcRequired()
    {
        return $this->isFieldRequired('UPCEnabled');
    }

    /**
     * Return flag is ISBN required
     * @return bool
     */
    public function isIsbnRequired()
    {
        return $this->isFieldRequired('ISBNEnabled');
    }

    /**
     * Return payment methods list
     * @return array
     */
    public function getPaymentMethods()
    {
        /** @var \SimpleXMLElement $category */
        $category = $this->response->Category;
        /** @var \SimpleXMLElement $site */
        $site = $this->response->SiteDefaults;

        $result = XmlHelper::getArrayAsStrings($site, 'PaymentMethod');

        return fn_array_merge($result, XmlHelper::getArrayAsStrings($category, 'PaymentMethod'));
    }

    /**
     * Return conditions values list
     * @return array
     */
    public function getConditions()
    {
        /** @var \SimpleXMLElement $category */
        $category = $this->response->Category;
        /** @var \SimpleXMLElement $site */
        $site = $this->response->SiteDefaults;
        $result = array();

        if (isset($site->ConditionValues)) {
            foreach ($site->ConditionValues->Condition as $condition) {
                $result[(string) $condition->ID] = (string) $condition->DisplayName;
            }
        }

        if (isset($category->ConditionValues)) {
            foreach ($category->ConditionValues->Condition as $condition) {
                $result[(string) $condition->ID] = (string) $condition->DisplayName;
            }
        }

        return $result;
    }

    /**
     * Get field value of key as boolean
     *
     * @param string $key
     * @return bool|null
     */
    public function getFieldBoolean($key)
    {
        /** @var \SimpleXMLElement $category */
        $category = $this->response->Category;
        /** @var \SimpleXMLElement $site */
        $site = $this->response->SiteDefaults;

        if (!empty($category->$key)) {
            return XmlHelper::getAsBoolean($category, $key, false);
        }

        return XmlHelper::getAsBoolean($site, $key, false);
    }

    /**
     * Check is field required
     *
     * @param string $key
     * @return bool
     */
    public function isFieldRequired($key)
    {
        /** @var \SimpleXMLElement $category */
        $category = $this->response->Category;
        /** @var \SimpleXMLElement $site */
        $site = $this->response->SiteDefaults;

        $value = '';

        if (!empty($category->$key)) {
            $value = (string) $category->$key;
        } elseif (!empty($site->$key)) {
            $value = (string) $site->$key;
        }

        return strtolower($value) === 'required';
    }
}
