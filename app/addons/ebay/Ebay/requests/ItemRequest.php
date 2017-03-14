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

namespace Ebay\requests;
use Ebay\Product;
use Ebay\Template;

/**
 * Class ItemRequest
 *
 * Parent class for AddItemRequest, RelistItemRequest, ReviseItemRequest
 * @package Ebay\requests
 */
abstract class ItemRequest extends Request
{
    protected static $companies = array();

    /** @var Product  */
    protected $product;

    /** @var Template  */
    protected $template;

    /** @var array */
    protected $product_identifiers = array();

    /** @var int */
    protected $external_category_id;

    /** @var int */
    protected $site_id;

    /**
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
        $this->template = $product->getTemplate();
        $this->product_identifiers = $product->getIdentifiers();
        $this->external_category_id = $product->getExternalCategoryId();
        $this->site_id = $this->template->site_id;
    }

    /**
     * Return company data
     * @param  int   $company_id
     * @return array
     */
    protected function getCompany($company_id)
    {
        if (!isset(static::$companies[$company_id])) {
            static::$companies[$company_id] = fn_get_company_placement_info($company_id);
        }

        return static::$companies[$company_id];
    }

    /**
     * Get location
     *
     * @param string $country
     * @return string
     */
    protected function getLocation($country)
    {
        return fn_get_country_name($country);
    }

    /**
     * @return string
     */
    protected function getCurrency()
    {
        return CART_PRIMARY_CURRENCY;
    }

    /**
     * @inheritdoc
     */
    public function xml()
    {
        $template = $this->template;
        $company = $this->getCompany($template->company_id);
        $location = $this->getLocation($company['company_country']);

        $secondary_category = $this->product->getExternalSecondCategoryId();
        $secondary_category_xml = "";

        if (!empty($secondary_category)) {
            $secondary_category_xml = <<<XML
            <SecondaryCategory>
                <CategoryID>{$secondary_category}</CategoryID>
            </SecondaryCategory>
XML;
        }

        return <<<XML
        <Item>
            {$this->getItemIdXml()}
            <Site>{$template->site}</Site>
            <ListingType>FixedPriceItem</ListingType>
            <Currency>{$this->getCurrency()}</Currency>
            <PrimaryCategory>
                <CategoryID>{$this->product->getExternalCategoryId()}</CategoryID>
            </PrimaryCategory>
            {$secondary_category_xml}
            <ConditionID>{$template->condition_id}</ConditionID>
            <CategoryMappingAllowed>true</CategoryMappingAllowed>
            <Country>{$company['company_country']}</Country>
            <PostalCode>{$company['company_zipcode']}</PostalCode>
            <Location><![CDATA[{$location}]]></Location>
            <Title><![CDATA[{$this->product->title}]]></Title>
            <Description><![CDATA[{$this->product->description}]]></Description>
            <ListingDuration><![CDATA[{$template->ebay_duration}]]></ListingDuration>
            <DispatchTimeMax><![CDATA[{$template->dispatch_days}]]></DispatchTimeMax>
            <ReturnPolicy>
                <ReturnsAcceptedOption><![CDATA[{$template->return_policy}]]></ReturnsAcceptedOption>
                <RefundOption><![CDATA[{$template->refund_method}]]></RefundOption>
                <ReturnsWithinOption><![CDATA[{$template->contact_time}]]></ReturnsWithinOption>
                <Description><![CDATA[{$template->return_policy_descr}]]></Description>
                <ShippingCostPaidByOption><![CDATA[{$template->cost_paid_by}]]></ShippingCostPaidByOption>
            </ReturnPolicy>
            {$this->getProductListingDetailsXml()}
            {$this->getPaymentXml()}
            {$this->getShippingXml()}
            {$this->getPictureDetailsXml()}
            {$this->getFeaturesXml()}
            {$this->getProductOptionsXml()}
            {$this->getAdditionalXml()}
        </Item>
XML;
    }

    protected function getAdditionalXml()
    {
        return '';
    }

    protected function getItemIdXml()
    {
        $id = $this->product->getExternalId();

        if (empty($id)) {
            return '';
        }

        return "<ItemID>{$id}</ItemID>";
    }

    protected function getPaymentXml()
    {
        $template = $this->product->getTemplate();

        $result = '<PaymentMethods>'
            . implode("</PaymentMethods>\n<PaymentMethods>", $template->payment_methods)
            . '</PaymentMethods>';

        if (in_array('PayPal', $template->payment_methods)) {
            $result .= "\n<PayPalEmailAddress>{$template->paypal_email}</PayPalEmailAddress>";
        }

        return $result;
    }

    protected function getShippingXml()
    {
        $template = $this->template;

        if ($template->shipping_type === 'C') {
            return <<<XML
            <ShippingDetails>
                <ShippingType>Calculated</ShippingType>
                <CalculatedShippingRate>
                    <PackageDepth>{$this->product->shipping_box_height}</PackageDepth>
                    <PackageLength>{$this->product->shipping_box_length}</PackageLength>
                    <PackageWidth>{$this->product->shipping_box_width}</PackageWidth>
                    <ShippingPackage>{$this->product->package_type}</ShippingPackage>
                    <WeightMajor>{$this->product->weight_major}</WeightMajor>
                    <WeightMinor>{$this->product->weight_minor}</WeightMinor>
                    <MeasurementUnit>{$template->getMeasureType()}</MeasurementUnit>
                </CalculatedShippingRate>
                <ShippingServiceOptions>
                    <ShippingService>{$template->shippings}</ShippingService>
                    <ShippingServicePriority>1</ShippingServicePriority>
                </ShippingServiceOptions>
            </ShippingDetails>
XML;
        } else {
            $shipping_cost_additional = '<ShippingServiceAdditionalCost currencyID="' . $this->getCurrency() . '">'
                . number_format($template->shipping_cost_additional, 2, '.', '')
                . '</ShippingServiceAdditionalCost>';

            if ($template->free_shipping === 'N') {
                $free_shipping = "false";
                $shipping_cost = '<ShippingServiceCost currencyID="' . $this->getCurrency() . '">'
                    . number_format($template->shipping_cost, 2, '.', '')
                    . '</ShippingServiceCost>';
            } else {
                $shipping_cost = null;
                $free_shipping = 'true';
            }

            return <<<XML
            <ShippingDetails>
                <ShippingType>Flat</ShippingType>
                <ShippingServiceOptions>
                    <FreeShipping>{$free_shipping}</FreeShipping>
                    {$shipping_cost}
                    <ShippingService>{$template->shippings}</ShippingService>
                    <ShippingServicePriority>1</ShippingServicePriority>
                    {$shipping_cost_additional}
                </ShippingServiceOptions>
            </ShippingDetails>
XML;
        }
    }

    protected function getPictureDetailsXml()
    {
        $result = '';

        if (!empty($this->product->pictures)) {
            foreach ($this->product->pictures as $path) {
                $external_path = $this->product->getExternalPicture($path);

                if (!empty($external_path)) {
                    $result .= "<PictureURL>{$external_path}</PictureURL>\n";
                }
            }
        }

        if (!empty($result)) {
            $result = "<PictureDetails>{$result}</PictureDetails>\n";
        }

        return $result;
    }

    protected function getFeaturesXml()
    {
        $features = $this->product->getFeatures();
        $combinations = $this->product->getCombinations();
        $product_identifiers = $this->product_identifiers;
        $exclude_codes = array();
        $result = '';

        if (isset($product_identifiers['BRAND'])) {
            $exclude_codes[] = 'Brand';
            $result .= <<<XML
                <NameValueList>
                    <Name>Brand</Name>
                    <Value>{$product_identifiers['BRAND']}</Value>
                </NameValueList>
XML;
        }

        if (isset($product_identifiers['MPN'])) {
            $exclude_codes[] = 'MPN';
            $result .= <<<XML
                <NameValueList>
                    <Name>MPN</Name>
                    <Value>{$product_identifiers['MPN']}</Value>
                </NameValueList>
XML;
        }

        foreach ($features as $feature) {
            $code = $feature->name;
            $value = $feature->getValue();
            $identifier = $feature->getProductIdentifier();

            if (in_array($code, $exclude_codes) || ($identifier && empty($combinations))) { //Do not duplicate identifier value
                continue;
            }

            if (is_array($value)) {
                $value = implode("]]></Value>\n<Value><![CDATA[", $value);
            }

            $result .= <<<XML
                <NameValueList>
                    <Name><![CDATA[{$code}]]></Name>
                    <Value><![CDATA[{$value}]]></Value>
                </NameValueList>
XML;
        }

        if ($result) {
            $result = "<ItemSpecifics>{$result}</ItemSpecifics>";
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function getProductOptionsXml()
    {
        $combinations = $this->product->getCombinations();
        $external_combinations = $this->product->getExternalCombinations();

        if (empty($combinations)) {
            $price = fn_format_price($this->product->price);

            return <<<XML
                <StartPrice currencyID="{$this->getCurrency()}">{$price}</StartPrice>
                <Quantity>{$this->product->amount}</Quantity>
XML;
        } else {
            $result = '<Variations><VariationSpecificsSet>';
            $pictures_xml = '';

            foreach ($this->product->getOptions() as $option) {

                $result .= <<<XML
                    <NameValueList>
                        <Name><![CDATA[{$option->name}]]></Name>
XML;


                $variant_pictures_xml = '';

                foreach ($option->getVariants() as $variant) {
                    $picture_path = $this->product->getExternalPicture($variant->picture);

                    if (!empty($picture_path)) {
                        $variant_pictures_xml .= "<VariationSpecificPictureSet>";
                        $variant_pictures_xml .= "<VariationSpecificValue><![CDATA[{$variant->name}]]></VariationSpecificValue>";
                        $variant_pictures_xml .= "<PictureURL>{$picture_path}</PictureURL>";
                        $variant_pictures_xml .= "</VariationSpecificPictureSet>";
                    }

                    $result .= "<Value><![CDATA[{$variant->name}]]></Value>\n";
                }

                if (!empty($variant_pictures_xml)) {
                    $pictures_xml .= <<<XML
                    <Pictures>
                        <VariationSpecificName><![CDATA[{$option->name}]]></VariationSpecificName>
                        {$variant_pictures_xml}
                    </Pictures>
XML;
                }
                $result .= "</NameValueList>\n";

            }

            $result .= '</VariationSpecificsSet>' . $pictures_xml;

            foreach ($combinations as $combination) {
                $identifiers = $combination->getIdentifiers();
                $sku = $combination->getSku();
                $variations_xml = '';

                foreach ($combination->getCombination() as $option_id => $variant_id) {
                    $option = $combination->getOption($option_id);

                    if ($option) {
                        $variant = $option->getVariant($variant_id);
                    }

                    if (isset($option, $variant)) {
                        $variations_xml .= <<<XML
                            <NameValueList>
                                <Name><![CDATA[{$option->name}]]></Name>
                                <Value><![CDATA[{$variant->name}]]></Value>
                            </NameValueList>
XML;
                    }
                }

                $result .= <<<XML
                    <Variation>
                        <SKU><![CDATA[{$sku}]]></SKU>
                        <StartPrice>{$combination->price}</StartPrice>
                        <Quantity>{$combination->quantity}</Quantity>
                        <VariationSpecifics>
                            {$variations_xml}
                        </VariationSpecifics>
XML;


                if (!empty($identifiers)) {
                    $identifier_xml = '';

                    if (!empty($identifiers['EAN'])) {
                        $identifier_xml .= "<EAN><![CDATA[{$identifiers['EAN']}]]></EAN>";
                    }

                    if (!empty($identifiers['ISBN'])) {
                        $identifier_xml .= "<ISBN><![CDATA[{$identifiers['ISBN']}]]></ISBN>";
                    }

                    if (!empty($identifiers['UPC'])) {
                        $identifier_xml .= "<UPC><![CDATA[{$identifiers['UPC']}]]></UPC>";
                    }

                    if (!empty($identifier_xml)) {
                        $result .= "<VariationProductListingDetails>{$identifier_xml}</VariationProductListingDetails>";
                    }
                }

                $result .= "</Variation>";
            }

            foreach ($external_combinations as $item) {
                if (!empty($item['sku']) && !isset($combinations[$item['sku']])) {
                    $result .= "<Variation>";
                    $result .= "<SKU><![CDATA[{$item['sku']}]]></SKU>";
                    $result .= "<Delete>true</Delete>";
                    $result .= "<Quantity>1</Quantity>";
                    $result .= "</Variation>";
                }
            }

            $result .= '</Variations>';

            return $result;
        }
    }

    /**
     * @return \Ebay\ProductVariation[]
     */
    protected function getProductCombinations()
    {
        return $this->product->getCombinations();
    }

    /**
     * @return string
     */
    protected function getProductListingDetailsXml()
    {
        $product = $this->product;
        $identifiers = $this->product_identifiers;
        $combinations = $product->getCombinations();
        $result = '';

        if (!empty($identifiers['BRAND']) && !empty($identifiers['MPN'])) {
            $result .= "<BrandMPN>";

            if (!empty($identifiers['BRAND'])) {
                $result .= "<Brand><![CDATA[{$identifiers['BRAND']}]]></Brand>";
            }

            if (!empty($identifiers['MPN'])) {
                $result .= "<MPN><![CDATA[{$identifiers['MPN']}]]></MPN>";
            }

            $result .= "</BrandMPN>";
        }

        if (empty($combinations)) {
            if (!empty($identifiers['UPC'])) {
                $result .= "<UPC><![CDATA[{$identifiers['UPC']}]]></UPC>";
            }

            if (!empty($identifiers['EAN'])) {
                $result .= "<EAN><![CDATA[{$identifiers['EAN']}]]></EAN>";
            }

            if (!empty($identifiers['ISBN'])) {
                $result .= "<ISBN><![CDATA[{$identifiers['ISBN']}]]></ISBN>";
            }
        }

        if (!empty($result)) {
            $result = "<ProductListingDetails>{$result}</ProductListingDetails>";
        }

        return $result;
    }
}
