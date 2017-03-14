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

namespace Tygh\Shippings\Services;

use Tygh\Shippings\IService;
use Tygh\Http;

/**
 * FedEx shipping service.
 * Uses FedEx Web Services v18 (SOAP)
 *
 * EMAIL_NOTIFICATION, RETURN_SHIPMENT and PENDING_SHIPMENT delivery options
 * are not implemented due to data they use
 */
class Fedex implements IService
{
    /**
     * API version
     */
    const VERSION = 18;
    /**
     * Production service URL
     */
    const URL_PRODUCTION = 'https://ws.fedex.com:443/web-services';
    /**
     * Development service URL
     */
    const URL_DEVELOPMENT = 'https://wsbeta.fedex.com:443/web-services';
    /**
     * Address type: Shipper
     */
    const INFO_SHIPPER = 'shipper';
    /**
     * Address type: Recipient
     */
    const INFO_RECIPIENT = 'recipient';
    /**
     * Special services type: Package
     */
    const SPECIAL_PACKAGE = 'package';
    /**
     * Special services type: Shipment
     */
    const SPECIAL_SHIPMENT = 'shipment';
    /**
     * Availability multithreading in this module
     *
     * @var array $_allow_multithreading
     */
    private $_allow_multithreading = true;
    /**
     * @var string Service URL
     */
    private $service_url;
    /**
     * @var array Shipping settings
     */
    private $settings;
    /**
     * @var array Package info
     */
    private $package;
    /**
     * Stored shipping information
     *
     * @var array $_shipping_info
     */
    private $_shipping_info = array();

    /**
     * @inheritdoc
     */
    public function prepareData($shipping_info)
    {
        $this->_shipping_info = $shipping_info;

        $this->settings = $shipping_info['service_params'];

        $this->package = $shipping_info['package_info'];
        $this->package['origination'] = $this->prepareAddress($this->package['origination']);
        $this->package['location'] = $this->prepareAddress($this->package['location']);

        $this->service_url = isset($this->settings['test_mode']) && $this->settings['test_mode'] == 'Y' ?
            self::URL_DEVELOPMENT :
            self::URL_PRODUCTION;
    }

    /**
     * Fill required address fields
     * TODO: Add to \Tygh\Shippings\IService
     *
     * @param array $address Address data
     *
     * @return array Filled address data
     */
    public function prepareAddress($address)
    {
        $default_fields = array(
            'address' => '',
            'zipcode' => '',
            'city' => '',
            'state' => '',
            'country' => '',
            'name' => '',
        );

        return array_merge($default_fields, $address);
    }

    /**
     * @inheritdoc
     */
    public function processResponse($response)
    {
        $return = array(
            'cost' => false,
            'error' => false,
            'delivery_time' => false,
        );

        $code = $this->_shipping_info['service_code'];
        // FIXME: FexEx returned GROUND for international as "FEDEX_GROUND" and not INTERNATIONAL_GROUND
        // We sent a request to clarify this situation to FedEx.
        $intl_code = str_replace('INTERNATIONAL_', 'FEDEX_', $code);
        $rates = $this->processRates($response);

        if (isset($rates[$code]) || isset($rates[$intl_code])) {
            $return['cost'] = isset($rates[$code]) ? $rates[$code] : $rates[$intl_code];
        } else {
            $return['error'] = $this->processErrors($response);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function processRates($result)
    {
        $result = str_replace(array('<', '<soapenv:', '<env:', '<SOAP-ENV:', '<ns:'), '<', $result);
        $result = str_replace(array('</', '</soapenv:', '</env:', '</SOAP-ENV:', '</ns:'), '</', $result);
        $xml = @simplexml_load_string($result);

        $return = array();

        if ($xml && $xml->Body->RateReply->RateReplyDetails) {
            foreach ($xml->Body->RateReply->RateReplyDetails as $item) {
                $service_code = (string) $item->ServiceType;
                $total_charge = (string) $item->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount;
                if (!empty($total_charge)) {
                    $return[$service_code] = $total_charge;
                }
            }
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function processErrors($result)
    {
        $result = str_replace(array('<v' . self::VERSION . ':', '<soapenv:', '<env:', '<SOAP-ENV:', '<ns:'), '<', $result);
        $result = str_replace(array('</v' . self::VERSION . ':', '</soapenv:', '</env:', '</SOAP-ENV:', '</ns:'), '</', $result);
        $xml = @simplexml_load_string($result);

        if ($xml) {
            $rate_reply = $xml->Body->RateReply;
            if ($rate_reply) {
                if ((string) $rate_reply->HighestSeverity == 'SUCCESS') {
                    $error = __('service_not_available');
                } else {
                    $notifications = array();
                    foreach ($rate_reply->Notifications as $notification) {
                        $notifications[] = sprintf("%s: %s",
                            trim((string) $notification->Code),
                            trim((string) $notification->Message)
                        );
                    }
                    $error = implode(' ', $notifications);
                }
            } else {
                $error = 'Unknown error';
            }

            return $error;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function allowMultithreading()
    {
        return $this->_allow_multithreading;
    }

    /**
     * @inheritdoc
     */
    public function getSimpleRates()
    {
        $data = $this->getRequestData();
        $response = Http::post($data['url'], $data['data'], array('headers' => 'Content-type: text/xml'));

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function getRequestData()
    {
        $rate_request = array(
            'WebAuthenticationDetail' => array(
                'UserCredential' => array(
                    'Key' => $this->settings['user_key'],
                    'Password' => $this->settings['user_key_password']
                )
            ),
            'ClientDetail' => array(
                'AccountNumber' => $this->settings['account_number'],
                'MeterNumber' => $this->settings['meter_number'],
            ),
            'TransactionDetail' => array(
                'CustomerTransactionId' => 'Rates Request',
            ),
            'Version' => array(
                'ServiceId' => 'crs',
                'Major' => self::VERSION,
                'Intermediate' => 0,
                'Minor' => 0,
            ),
            'RequestedShipment' => array(
                'DropoffType' => $this->settings['drop_off_type'],
                'ServiceType' => $this->_shipping_info['service_code'],
                'PackagingType' => $this->settings['package_type'],
                'Shipper' => $this->prepareShippingInfo($this->package['origination'], self::INFO_SHIPPER),
                'Recipient' => $this->prepareShippingInfo($this->package['location'], self::INFO_RECIPIENT, $this->_shipping_info['service_code']),
                'ShippingChargesPayment' => array(
                    'PaymentType' => 'SENDER',
                    'Payor' => array(
                        'ResponsibleParty' => array(
                            'AccountNumber' => $this->settings['account_number'],
                        ),
                    ),
                ),
                'SpecialServicesRequested' => $this->prepareSpecialServices(self::SPECIAL_SHIPMENT),
                'FreightShipmentDetail' => $this->prepareFreightShipmentDetails(),
                'SmartPostDetail' => $this->prepareSmartPostDetails(),
                'RateRequestTypes' => 'LIST',
            ),
        );

        $rate_request['RequestedShipment'] += $this->preparePackages();

        $xml_req = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<SOAP-ENV:Envelope'
            . ' xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"'
            . ' xmlns="http://fedex.com/ws/rate/v' . self::VERSION . '">'
                . '<SOAP-ENV:Body>'
                    . '<RateRequest>'
                        . fn_array_to_xml($rate_request)
                    . '</RateRequest>'
                . '</SOAP-ENV:Body>'
            . '</SOAP-ENV:Envelope>';

        return array(
            'method' => 'post',
            'url' => $this->service_url,
            'data' => $xml_req,
            'headers' => array(
                'Content-type: text/xml'
            )
        );
    }

    /**
     * Prepares shipping information for request data
     *
     * @param array  $address      Address data (Zipcode, Country, State, etc)
     * @param string $address_type 'recipient' or 'shipper'
     * @param string $code         Service code (E.g.: SMART_POST)
     *
     * @return array Shipping info
     */
    private function prepareShippingInfo($address, $address_type = self::INFO_SHIPPER, $code = '')
    {
        $shipping_info = array(
            'Address' => array(
                'StreetLines' => $address['address'],
                'City' => $address['city'],
                'StateOrProvinceCode' => (strlen($address['state']) > 2) ? '' : $address['state'],
                'PostalCode' => self::formatPostalCode($address['zipcode']),
                'CountryCode' => $address['country'],
            )
        );

        if ($address_type == self::INFO_RECIPIENT && ($code == 'GROUND_HOME_DELIVERY' || empty($address['address_type']) || $address['address_type'] == 'residential')) {
            $shipping_info['Address']['Residential'] = 'true';
        }

        if ($address_type == self::INFO_RECIPIENT && $code == 'FEDEX_GROUND') {
            $shipping_info['Address']['Residential'] = 'false';
        }

        return $shipping_info;
    }

    /**
     * Formats postal code
     *
     * @param string $code Not formatted postal code
     *
     * @return string Formatted postal code
     */
    public static function formatPostalCode($code)
    {
        if (preg_match_all("/[\d\w]/", $code, $matches)) {
            return implode('', $matches[0]);
        }

        return '';
    }

    /**
     * Formats special services details for the package
     *
     * @param string $type Special service type
     *
     * @return array Special services details
     */
    private function prepareSpecialServices($type = self::SPECIAL_SHIPMENT)
    {
        // DRY_ICE and DANGEROUS_GOODS are not allowed on the shipment level
        $special_services = array();

        if (!empty($this->settings['options'])) {

            foreach ($this->settings['options'] as $code => $value) {
                switch ($code) {
                    case 'COD_AMOUNT':
                    case 'COD_COLLECTION_TYPE':
                    case 'DANGEROUS_GOODS_OPTIONS':
                    case 'DANGEROUS_GOODS_ACCESSIBILITY': {
                        continue 2;
                    }
                    case 'COD': {
                        if ($this->isCodAvailable($this->_shipping_info['service_code'], $type)) {
                            $special_services['SpecialServiceTypes'][] = $code;
                            $special_services['CodDetail'] = array(
                                'CodCollectionAmount' => array(
                                    'Currency' => CART_PRIMARY_CURRENCY,
                                    'Amount' => floatval($this->settings['options']['COD_AMOUNT']),
                                ),
                                'CollectionType' => $this->settings['options']['COD_COLLECTION_TYPE']
                            );
                        }
                        break;
                    }
                    case 'DANGEROUS_GOODS': {
                        if ($type == self::SPECIAL_PACKAGE) {
                            $special_services['SpecialServiceTypes'][] = $code;
                            $special_services['DangerousGoodsDetail'] = array(
                                'Accessibility' => $this->settings['options']['DANGEROUS_GOODS_ACCESSIBILITY'],
                                'Options' => isset($this->settings['options']['DANGEROUS_GOODS_OPTIONS']) ?
                                    $this->settings['options']['DANGEROUS_GOODS_OPTIONS'] :
                                    array(),
                            );
                        }
                        break;
                    }
                    case 'DRY_ICE': {
                        if ($type == self::SPECIAL_PACKAGE) {
                            $special_services['SpecialServiceTypes'][] = $code;
                        }
                        break;
                    }
                    default: {
                        if ($type == self::SPECIAL_SHIPMENT) {
                            $special_services['SpecialServiceTypes'][] = $code;
                        }
                    }

                }
            }

        }

        return $special_services;
    }

    /**
     * Checks if COD could be specified at the shipment or at the package level
     *
     * @param string $service_code Service code
     * @param string $level        Level (shipment or package)
     *
     * @return bool True COD is allowed at the level
     */
    private function isCodAvailable($service_code, $level = self::SPECIAL_SHIPMENT)
    {
        if (in_array($service_code, array('FEDEX_GROUND', 'GROUND_HOME_DELIVERY'))) {
            return $level == self::SPECIAL_PACKAGE;
        }

        return $level == self::SPECIAL_SHIPMENT;
    }

    /**
     * Prepares Smart Post information
     *
     * @return array Data for SmartPost section in the request
     */
    private function prepareSmartPostDetails()
    {
        $smart_post = array();

        if ($this->_shipping_info['service_code'] == 'SMART_POST'
            && !empty($this->settings['hub_id']) && !empty($this->settings['indicia'])
        ) {
            $smart_post['Indicia'] = $this->settings['indicia'];
            if (!empty($this->settings['ancillary_endorsement'])) {
                $smart_post['AncillaryEndorsement'] = $this->settings['ancillary_endorsement'];
            }
            $smart_post['HubId'] = $this->settings['hub_id'];
            if (!empty($this->settings['customer_manifest_id'])) {
                $smart_post['CustomerManifestId'] = $this->settings['customer_manifest_id'];
            }
        }

        return $smart_post;
    }

    /**
     * Prepares packages information
     *
     * @param bool $is_freight If true, packages will be calculated for the freight shipment.
     *                                  Otherwise - for the regular shipment
     *
     * @return array Prepared packages information
     */
    private function preparePackages($is_freight = false)
    {
        $length = empty($this->settings['length']) ? 0 : $this->settings['length'];
        $width = empty($this->settings['width']) ? 0 : $this->settings['width'];
        $height = empty($this->settings['height']) ? 0 : $this->settings['height'];

        $packages = array();
        if (empty($this->package['packages'])) {
            $packages[] = array(
                'shipping_params' => array(
                    'box_length' => $length,
                    'box_width' => $width,
                    'box_height' => $height,
                ),
                'weight' => $this->package['W'],
                'cost' => $this->package['C']
            );
        } else {
            $packages = $this->package['packages'];
        }

        if ($is_freight) {
            $package_items = array();
            $property_name = 'LineItems';
            $line_item_fields = array('FreightClass', 'Weight', 'Dimensions');
        } else {
            $package_items = array(
                'PackageCount' => count($this->package['packages'])
            );
            $property_name = 'RequestedPackageLineItems';
            $line_item_fields = array('SequenceNumber', 'GroupPackageCount', 'Weight', 'Dimensions', 'SpecialServicesRequested');
        }

        $sequence_number = 1;
        foreach ($packages as $package) {
            $package_length = empty($package['shipping_params']['box_length']) ? $length : $package['shipping_params']['box_length'];
            $package_width = empty($package['shipping_params']['box_width']) ? $width : $package['shipping_params']['box_width'];
            $package_height = empty($package['shipping_params']['box_height']) ? $height : $package['shipping_params']['box_height'];
            $package_weight = fn_expand_weight($package['weight']);

            $line_item = array();
            foreach ($line_item_fields as $field) {
                switch ($field) {
                    case 'SequenceNumber':
                        $value = $sequence_number++;
                        break;
                    case 'GroupPackageCount':
                        $value = 1;
                        break;
                    case 'Weight':
                        $value = array(
                            'Units' => 'LB',
                            'Value' => $package_weight['full_pounds'],
                        );
                        break;
                    case 'Dimensions':
                        $value = array(
                            'Length' => $package_length,
                            'Width' => $package_width,
                            'Height' => $package_height,
                            'Units' => 'IN',
                        );
                        break;
                    case 'SpecialServicesRequested':
                        $value = $this->prepareSpecialServices(self::SPECIAL_PACKAGE);
                        break;
                    case 'FreightClass':
                        $value = self::getFreightClass($package_length, $package_width, $package_height, $package_weight['full_pounds']);
                        break;
                }
                if (isset($value)) {
                    $line_item[$field] = $value;
                }
            }

            if ($line_item) {
                $package_items[$property_name][] = $line_item;
            }
        }

        return $package_items;
    }

    private function prepareFreightShipmentDetails()
    {
        $freight_details = array();

        if (!empty($this->settings['freight_account_number'])) {
            $freight_details = array(
                'FedExFreightAccountNumber' => $this->settings['freight_account_number'],
                'FedExFreightBillingContactAndAddress' => $this->prepareShippingInfo($this->package['origination']),
                'Role' => 'SHIPPER',
            );

            $freight_details += $this->preparePackages(true);
        }

        return $freight_details;
    }

    /**
     * Returns shipping service information
     *
     * @return array information
     */
    public static function getInfo()
    {
        return array(
            'name' => __('carrier_fedex'),
            'tracking_url' => 'https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber=%s'
        );
    }

    /**
     * Determines freight class of the package.
     *
     * @param float $length Length in inches
     * @param float $width  Width in inches
     * @param float $height Height in inches
     * @param float $weight Weight in pounds
     *
     * @return string Freight class
     */
    protected static function getFreightClass($length, $width, $height, $weight)
    {
        $class = '500';

        $volume = $length * $width * $height / pow(12, 3); // volume in cubic feets

        if ($volume > 0) {
            $density = $weight / $volume; // density in lbs per cubic feet
            $classes = array(
                '50'   => array(50,   INF),
                '55'   => array(35,   50),
                '60'   => array(30,   35),
                '65'   => array(22.5, 30),
                '70'   => array(15,   22.5),
                '77.5' => array(13.5, 15),
                '85'   => array(12,   13.5),
                '92.5' => array(10.5, 12),
                '100'  => array( 9,   10.5),
                '110'  => array( 8,    9),
                '125'  => array( 7,    8),
                '150'  => array( 6,    7),
                '175'  => array( 5,    6),
                '200'  => array( 4,    5),
                '250'  => array( 3,    4),
                '300'  => array( 2,    3),
                '400'  => array( 1,    2),
                '500'  => array(-INF,  1)
            );
            foreach ($classes as $class => $limits) {
                if ($density >= $limits[0] && $density < $limits[1]) {
                    break;
                }
            }
        }

        return 'CLASS_' . ((float) $class < 100 ? '0' : '') . str_replace('.', '_', $class);
    }

}
