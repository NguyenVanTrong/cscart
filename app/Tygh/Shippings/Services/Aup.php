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

use Tygh\Settings;
use Tygh\Shippings\IService;
use Tygh\Http;

/**
 * Australia Post shipping service
 * Uses PAC API
 */
class Aup implements IService
{
    /**
     * Production service URL
     */
    const URL_PRODUCTION = 'https://digitalapi.auspost.com.au/postage';
    /**
     * Australian dollar currency code
     */
    const CURRENCY_CODE = 'AUD';
    /**
     * Domestic postage
     */
    const DEST_DOMESTIC = 'domestic';
    /**
     * International postage
     */
    const DEST_INTERNATIONAL = 'international';
    /**
     * Parcel postage
     */
    const TYPE_PARCEL = 'parcel';
    /**
     * Letter postage
     */
    const TYPE_LETTER = 'letter';
    /**
     * Convert to Australian dollar
     */
    const CONVERT_TO_NATIVE = 'TO_NATIVE';
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
     * @var array Response JSON
     */
    private $response;
    /**
     * @var string Postage type
     */
    private $postage_type;
    /**
     * @var string Destination type
     */
    private $destination_type;
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

        list($this->destination_type, $this->postage_type) = $this->detectPostageDetails();

        $this->service_url = self::URL_PRODUCTION;;

        if (!isset(self::$exchange_rate)) {
            $currencies = fn_get_currencies();
            self::$exchange_rate = !empty($currencies[self::CURRENCY_CODE]) ? $currencies[self::CURRENCY_CODE]['coefficient'] : -1;
        }
    }

     /**
      * @inheritdoc
      */
    public function processResponse($response)
    {
        $return = array(
            'cost' => false,
            'error' => false,
            'delivery_time' => false
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
     * @param string $response Response JSON
     *
     * @return array Cost and delivery time
     */
    private function processRates($response)
    {
        if (empty($this->response)) {
            $this->response = json_decode($response, true);
        }

        $rates = array(
            'cost' => false,
            'delivery_time' => false
        );

        if (self::$exchange_rate > 0 && !empty($this->response['postage_result']['total_cost'])) {
            $rates['cost'] = self::convertAmount($this->response['postage_result']['total_cost'], self::CONVERT_TO_PRIMARY);
            if (!empty($this->response['postage_result']['delivery_time'])) {
                $rates['delivery_time'] = $this->response['postage_result']['delivery_time'];
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
            $this->response = json_decode($response, true);
        }

        if (self::$exchange_rate < 0) {
            $error =  __('shippings.aup.currency_not_configured');
        } else {
            $error = empty($this->response['error']['errorMessage']) ? '' : $this->response['error']['errorMessage'];
        }

        return $error;
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
        $box = $this->prepareDimensions();

        $request = array(
            'country_code'  => $this->package['location']['country'],
            'service_code'  => $this->_shipping_info['service_code'],
            'weight'        => $this->prepareWeight(),
            'length'        => $box['length'],
            'width'         => $box['width'],
            'height'        => $box['height'],
            'from_postcode' => $this->package['origination']['zipcode'],
            'to_postcode'   => $this->package['location']['zipcode'],
        );

        $request_data = array(
            'method'  => 'get',
            'url'     => $this->getEndpointUrl('/calculate.json'),
            'data'    => $request,
            'headers' => array(
                'Auth-Key: ' . $this->getAuthHeader()
            )
        );

        return $request_data;
    }

    /**
     * Detects postage type and destination
     *
     * @return array Postage type and destination
     */
    private function detectPostageDetails()
    {
        list(, $postage_type, ) = explode('_', $this->_shipping_info['service_code']);

        $postage_type = $postage_type == 'PARCEL' ? self::TYPE_PARCEL : self::TYPE_LETTER;

        if ($this->package['location']['country'] == $this->package['origination']['country']) {
            $destination_type = self::DEST_DOMESTIC;
        } else {
            $destination_type = self::DEST_INTERNATIONAL;
        }

        return array($destination_type, $postage_type);
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
        return $this->service_url . '/' . $this->postage_type . '/' . $this->destination_type . '/' . trim($path, '/');
    }

    /**
     * Provides content of Authorization header
     *
     * @return string Content of header
     */
    private function getAuthHeader()
    {
        return $this->settings['pac_api_key'];
    }

    /**
     * Formats postage weight in kilograms for parcels and in grams for letters
     *
     * @return float Postage weight
     */
    private function prepareWeight()
    {
        // weight in grams
        $weight = $this->package['W'] * Settings::instance()->getValue('weight_symbol_grams', 'General');
        if ($this->postage_type == self::TYPE_PARCEL) {
            $weight /= 1000;
        }

        return $weight;
    }

    /**
     * Converts prices between Australian dollar and primary currency
     *
     * @param float  $amount    Amount to be converted
     * @param string $direction Direction to convert amount
     *
     * @return float Converted amount
     */
    public static function convertAmount($amount = 0.0, $direction = self::CONVERT_TO_NATIVE)
    {
        if (self::$exchange_rate > 0) {
            switch ($direction) {
                case self::CONVERT_TO_NATIVE: {
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
     * Returns shipping service information
     * @return array information
     */
    public static function getInfo()
    {
        return array(
            'name' => __('carrier_aup'),
            'tracking_url' => 'http://auspost.com.au/track/track.html?exp=b&id=%s'
        );
    }

    /**
     * Calculates parcel dimensions by using the package with maximal volumetric parameters.
     *
     * @return array Parcel dimensions
     */
    private function prepareDimensions()
    {
        if (!empty($this->package['packages'])) {
            $max = array(
                'width' => 0,
                'height' => 0,
                'length' => 0,
            );

            foreach ($this->package['packages'] as $package) {
                $box = array(
                    'width' => $this->settings['width'],
                    'height' => $this->settings['height'],
                    'length' => $this->settings['length'],
                );

                foreach(array_keys($box) as $dimension) {
                    if (!empty($package['shipping_params']['box_' . $dimension])) {
                        $box[$dimension] = $package['shipping_params']['box_' . $dimension];
                    }
                }

                if ($box['width'] * $box['height'] * $box['length'] > $max['width'] * $max['height'] * $max['length']) {
                    $max = $box;
                }
            }
        } else {
            $max = array(
                'width' => $this->settings['width'],
                'height' => $this->settings['height'],
                'length' => $this->settings['length'],
            );
        }

        return $max;
    }
}
