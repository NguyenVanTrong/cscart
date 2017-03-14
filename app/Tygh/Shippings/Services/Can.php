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
 * Canada Post shipping service.
 * Uses Canada Post Web Services API (REST implementation)
 */
class Can implements IService
{
    /**
     * Production service URL
     */
    const URL_PRODUCTION  = 'https://soa-gw.canadapost.ca';
    /**
     * Development service URL
     */
    const URL_DEVELOPMENT = 'https://ct.soa-gw.canadapost.ca';
    /**
     * Domestic parcel
     */
    const DEST_DOMESTIC = 'DOM';
    /**
     * Parcel sent to USA
     */
    const DEST_US = 'US';
    /**
     * International parcel
     */
    const DEST_INTERNATIONAL = 'INT';
    /**
     * Canadian dollar currency code
     */
    const CURRENCY_CODE = 'CAD';
    /**
     * Convert to Canadian dollar
     */
    const CONVERT_TO_CAD = 'TO_CAD';
    /**
     * Conver to primary currency
     */
    const CONVERT_TO_PRIMARY = 'TO_PRIMARY';
    /**
     * @var bool Availability multithreading in this module
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
     * @var \SimpleXMLElement Response XML object
     */
    private $response;
    /**
     * @var float Exchange rate for Canadian dollar
     */
    private static $exchange_rate;

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

        if (!isset(self::$exchange_rate)) {
            $currencies = fn_get_currencies();
            self::$exchange_rate = !empty($currencies[self::CURRENCY_CODE]) ? $currencies[self::CURRENCY_CODE]['coefficient'] : -1;
        }
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
            'zipcode' => '',
            'country' => ''
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

        $errors = $this->processErrors($response);
        if ($errors) {
            $return['error'] = $errors;
        } else {
            $rates = $this->processRates($response);
            if ($rates['cost']) {
                $return['cost'] = $rates['cost'];
                if ($rates['delivery_time']) {
                    $return['delivery_time'] = $rates['delivery_time'];
                }
            }
        }

        return $return;
    }

    /**
     * Gets service rates from response
     *
     * @param string $response Response XML
     *
     * @return array Cost and delivery time
     */
    private function processRates($response)
    {
        if (empty($this->response)) {
            $this->response = @simplexml_load_string($response);
        }

        $rates = array(
            'cost' => false,
            'delivery_time' => false
        );

        if (self::$exchange_rate > 0 && !empty($this->response->{'price-quote'})) {
            $quote = $this->response->{'price-quote'};
            $rates['cost'] = self::convertAmount(floatval($quote->{'price-details'}->{'due'}), self::CONVERT_TO_PRIMARY);
            if (!empty($quote->{'service-standard'}->{'expected-delivery-date'})) {
                $rates['delivery_time'] = (string) $quote->{'service-standard'}->{'expected-delivery-date'};
            }
        }

        return $rates;
    }

    /**
     * @inheritdoc
     */
    public function processErrors($response)
    {
        if (empty($this->response)) {
            $this->response = @simplexml_load_string($response);
        }

        $errors = array();

        if (self::$exchange_rate < 0) {
            $errors[] = array(
                'code' => __('error'),
                'message' => __('canada_post_activation_error')
            );
        } else {
            foreach ($this->response as $type => $element) {
                if ($type == 'message') {
                    $errors[] = array(
                        'code' => (string) $element->code,
                        'message' => preg_replace('/{.+?}/', '', (string) $element->description)
                    );
                }
            }
        }

        return self::formatErrors($errors);
    }

    /**
     * Formats erros messages into single error message
     *
     * @param array $errors Error messages
     *
     * @return string Error message
     */
    public static function formatErrors($errors = array())
    {
        $messages = array();

        foreach ($errors as $error) {
            $messages[] = "{$error['code']}: {$error['message']}";
        }

        return implode('; ', $messages);
    }

    /**
     * @inheritdoc
     */
    public function getSimpleRates()
    {
        $data = $this->getRequestData();
        $response = Http::post($data['url'], $data['data']);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function getRequestData()
    {
        $request = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<mailing-scenario xmlns="http://www.canadapost.ca/ws/ship/rate-v3">'
                . $this->prepareQuoteTypeNode()
                . $this->prepareOptionsNode()
                . $this->prepareParcelNode()
                . $this->prepareServicesNode()
                . $this->prepareOriginationNode()
                . $this->prepareDestinationNode()
            . '</mailing-scenario>';

        $request_data = array(
            'method'  => 'post',
            'url'     => $this->getEndpointUrl('/rs/ship/price'),
            'data'    => $request,
            'headers' => array(
                'Accept: '          . $this->getContentHeader(),
                'Content-Type: '    . $this->getContentHeader(),
                'Accept-Language: ' . $this->getLanguageHeader(),
                'Authorization: '   . $this->getAuthHeader()
            )
        );

        return $request_data;
    }

    /**
     * Prepares 'quote-type' XML node for request
     *
     * @return string Prepared 'quote-type' XML node for request
     */
    private function prepareQuoteTypeNode()
    {
        $quote = array();

        if (!empty($this->settings['customer_number'])) {
            $quote['quote-type'] = 'commercial';
            $quote['customer-number'] = sprintf("%10i", $this->settings['customer_number']);
            if (!empty($this->settings['contract_id'])) {
                $quote['contract-id'] = sprintf("%10i", $this->settings['contract_id']);
            }
        } else {
            $quote['quote-type'] = 'counter';
        }

        return fn_array_to_xml($quote);
    }

    /**
     * Prepares 'options' XML node for request
     *
     * @return string Prepared 'options' XML node for request
     */
    private function prepareOptionsNode()
    {
        $options = array();

        if (!empty($this->settings['options'])) {
            foreach ($this->settings['options'] as $code => $value) {
                $option = array(
                    'option-code' => strtoupper($code)
                );

                switch ($code) {
                    case 'cov_amount': {
                        continue 2;
                    }
                    case 'cov': {
                        if (self::$exchange_rate > 0 && !empty($this->settings['options']["cov_amount"])) {
                            $option['option-amount'] = sprintf("%5.2f", self::convertAmount($this->settings['options']["cov_amount"], self::CONVERT_TO_CAD));
                        } else {
                            continue 2;
                        }
                        break;
                    }
                    case 'cod': {
                        if (self::$exchange_rate > 0) {
                            $option['option-amount'] = sprintf("%5.2f", self::convertAmount($this->package['C'], self::CONVERT_TO_CAD));
                        } else {
                            continue 2;
                        }
                        break;
                    }
                }

                $options['option'][] = $option;
            }
        }

        return empty($options) ? '' : fn_array_to_xml(array('options' => $options));
    }

    /**
     * Detects destination type
     *
     * @param string $code Country code
     *
     * @return string Destination type
     */
    public static function getDestinationType($code)
    {
        switch ($code) {
            case 'CA':
                return self::DEST_DOMESTIC;
            case 'US':
                return self::DEST_US;
            default:
                return self::DEST_INTERNATIONAL;
        }
    }

    /**
     * Prepares 'parcel-characteristics' XML node for request
     *
     * @return string Prepared 'parcel-characteristics' XML node for request
     */
    private function prepareParcelNode()
    {
        $weight_data = fn_expand_weight($this->package['W']);
        $weight = sprintf("%.3f", $weight_data['full_pounds'] * 0.4536);

        return fn_array_to_xml(array(
            'parcel-characteristics' => array(
                'weight' => $weight
            )
        ));
    }

    /**
     * Prepares 'services' XML node for request
     *
     * @return string Prepared 'services' XML node for request
     */
    private function prepareServicesNode()
    {
        return fn_array_to_xml(array(
            'services' => array(
                'service-code' => $this->_shipping_info['service_code']
            )
        ));
    }

    /**
     * Prepares 'origin-postal-code' XML node for request
     *
     * @return string Prepared 'origin-postal-code' XML node for request
     */
    private function prepareOriginationNode()
    {
        return fn_array_to_xml(array(
            'origin-postal-code' => self::formatPostalCode($this->package['origination']['zipcode'])
        ));
    }

    /**
     * Converts prices between Canadian dollar and primary currency
     *
     * @param float  $amount    Amount to be converted
     * @param string $direction Direction to convert amount
     *
     * @return float Converted amount
     */
    public static function convertAmount($amount = 0.0, $direction = self::CONVERT_TO_CAD)
    {
        if (self::$exchange_rate > 0) {
            switch ($direction) {
                case self::CONVERT_TO_CAD: {
                    return $amount / self::$exchange_rate;
                    break;
                }
                case self::CONVERT_TO_PRIMARY: {
                    return $amount * self::$exchange_rate;
                    break;
                }
            }
        }

        return -1.0;
    }

    /**
     * Formats Canadian Postal code
     *
     * @param string $code Not formatted postal code
     *
     * @return string Formatted postal code
     */
    public static function formatPostalCode($code)
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($code));
    }

    /**
     * Prepares 'destination' XML node for request
     *
     * @return string Prepared 'destination' XML node for request
     */
    private function prepareDestinationNode()
    {
        switch (self::getDestinationType($this->package['location']['country'])) {
            case self::DEST_DOMESTIC: {
                $destination = array(
                    'domestic' => array(
                        'postal-code' => self::formatPostalCode($this->package['location']['zipcode'])
                    )
                );
                break;
            }
            case self::DEST_US: {
                $destination = array(
                    'united-states' => array(
                        'zip-code' => $this->package['location']['zipcode']
                    )
                );
                break;
            }
            default: {
                $destination = array(
                    'international' => array(
                        'country-code' => $this->package['location']['country']
                    )
                );
                break;
            }
        }

        return fn_array_to_xml(array('destination' => $destination));
    }

    /**
     * Formats endpoint URL to perform API request to
     *
     * @param string $path Endpoint path
     *
     * @return string Endpoint URL
     */
    private function getEndpointUrl($path)
    {
        return $this->service_url . '/' . trim($path, '/');
    }

    /**
     * Provides content of Accept and Content-Type headers
     *
     * @return string Content of header
     */
    private function getContentHeader()
    {
        return 'application/vnd.cpc.ship.rate-v3+xml';
    }

    /**
     * Provides content of Accept-Language header
     *
     * @return string Content of header
     */
    private function getLanguageHeader()
    {
        return (CART_LANGUAGE == 'fr') ? 'fr-CA' : 'en-CA';
    }

    /**
     * Provides content of Authorization header
     *
     * @return string Content of header
     */
    private function getAuthHeader()
    {
        return 'Basic ' . base64_encode("{$this->settings['username']}:{$this->settings['password']}");
    }

    /**
     * Returns shipping service information
     * @return array information
     */
    public static function getInfo()
    {
        return array(
            'name' => __('carrier_can'),
            'tracking_url' => 'https://www.canadapost.ca/cpotools/apps/track/personal/findByTrackNumber?trackingNumber=%s'
        );
    }

}
