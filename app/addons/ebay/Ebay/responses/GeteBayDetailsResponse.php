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
 * Class GeteBayDetailsResponse
 * @package Ebay\responses
 * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/GeteBayDetails.html
 */
class GeteBayDetailsResponse extends Response
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
     * Get shipping services
     * @return ShippingServiceDetails[]
     */
    public function getShippingServiceDetails()
    {
        $result = array();

        if (!empty($this->response->ShippingServiceDetails)) {
            foreach ($this->response->ShippingServiceDetails as $item) {
                $result[] = new ShippingServiceDetails($item);
            }
        }

        return $result;
    }

    /**
     * Get sites
     * @return SiteDetails[]
     */
    public function getSiteDetails()
    {
        $result = array();

        if (!empty($this->response->SiteDetails)) {
            foreach ($this->response->SiteDetails as $item) {
                $result[] = new SiteDetails($item);
            }
        }

        return $result;
    }

    /**
     * Get unavailable text for product identifier
     *
     * @return string
     */
    public function getProductIdentifierUnavailableText()
    {
        $result = '';

        if (!empty($this->response->ProductDetails->ProductIdentifierUnavailableText)) {
            $result = (string) $this->response->ProductDetails->ProductIdentifierUnavailableText;
        }

        return $result;
    }
}

/**
 * Class SiteDetails
 * @package Ebay\responses
 */
class SiteDetails
{
    public $detail_version;
    public $site;
    public $site_id;
    public $update_time;

    /**
     * Constructor
     * @param \SimpleXMLElement $response
     */
    public function __construct(\SimpleXMLElement $response)
    {
        $this->detail_version = XmlHelper::getAsString($response, 'DetailVersion');
        $this->update_time = XmlHelper::getAsString($response, 'UpdateTime');
        $this->site = XmlHelper::getAsString($response, 'Site');
        $this->site_id = XmlHelper::getAsString($response, 'SiteID');
    }
}

/**
 * Class ShippingServiceDetails
 * @package Ebay\responses
 */
class ShippingServiceDetails
{
    public $code_service;
    public $cost_group_flat;
    public $deprecation_details;
    public $description;
    public $detail_version;
    public $dimensions_required;
    public $expedited_service;
    public $international_service;
    public $mapped_to_shipping_service_id;
    public $service_type;
    public $shipping_carrier;
    public $shipping_category;
    public $shipping_package;
    public $shipping_service;
    public $shipping_service_id;
    public $shipping_service_package_details;
    public $shipping_time_max;
    public $shipping_time_min;
    public $surcharge_applicable;
    public $update_time;
    public $valid_for_selling_flow;
    public $weight_required;

    /**
     * Constructor
     * @param \SimpleXMLElement $response
     */
    public function __construct(\SimpleXMLElement $response)
    {
        $this->code_service = XmlHelper::getAsBoolean($response, 'CODService');
        $this->dimensions_required = XmlHelper::getAsBoolean($response, 'DimensionsRequired');
        $this->expedited_service = XmlHelper::getAsBoolean($response, 'ExpeditedService');
        $this->international_service = XmlHelper::getAsBoolean($response, 'InternationalService');
        $this->surcharge_applicable = XmlHelper::getAsBoolean($response, 'SurchargeApplicable');
        $this->valid_for_selling_flow = XmlHelper::getAsBoolean($response, 'ValidForSellingFlow');
        $this->weight_required = XmlHelper::getAsBoolean($response, 'WeightRequired');
        $this->service_type = XmlHelper::getArrayAsStrings($response, 'ServiceType');
        $this->shipping_carrier = XmlHelper::getArrayAsStrings($response, 'ShippingCarrier');
        $this->shipping_package = XmlHelper::getArrayAsStrings($response, 'ShippingPackage');

        if (isset($response->ShippingServicePackageDetails)) {
            $this->shipping_service_package_details = array();

            foreach ($response->ShippingServicePackageDetails as $item) {
                $this->shipping_service_package_details[] = array(
                    'dimensions_required' => XmlHelper::normalizeBoolean($item->DimensionsRequired),
                    'name' => (string) $item->Name
                );
            }
        }

        if (isset($response->DeprecationDetails)) {
            $this->deprecation_details = array();

            foreach ($response->DeprecationDetails as $item) {
                $this->deprecation_details[] = array(
                    'announcement_start_time' => isset($item->AnnouncementStartTime) ? $item->AnnouncementStartTime : null,
                    'event_time' => isset($item->EventTime) ? $item->EventTime : null,
                    'message_type' => isset($item->MessageType) ? $item->MessageType : null
                );
            }
        }

        if (isset($response->SiteID)) {
            $this->site_id = $response->SiteID;
        }

        $this->cost_group_flat = XmlHelper::getAsString($response, 'CostGroupFlat', '');
        $this->description = XmlHelper::getAsString($response, 'Description', '');
        $this->mapped_to_shipping_service_id = XmlHelper::getAsString($response, 'MappedToShippingServiceID', '');
        $this->shipping_category = XmlHelper::getAsString($response, 'ShippingCategory', '');
        $this->shipping_service = XmlHelper::getAsString($response, 'ShippingService', '');
        $this->shipping_service_id = XmlHelper::getAsString($response, 'ShippingServiceID', '');
        $this->shipping_time_max = XmlHelper::getAsString($response, 'ShippingTimeMax', '');
        $this->shipping_time_min = XmlHelper::getAsString($response, 'ShippingTimeMin', '');
        $this->detail_version = XmlHelper::getAsString($response, 'DetailVersion', '');
        $this->update_time = XmlHelper::getAsString($response, 'UpdateTime', '');
    }
}
