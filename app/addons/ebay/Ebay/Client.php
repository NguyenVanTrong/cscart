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

namespace Ebay;

use Ebay\requests\AddItemRequest;
use Ebay\requests\AddItemsRequest;
use Ebay\requests\EndItemRequest;
use Ebay\requests\EndItemsRequest;
use Ebay\requests\GetCategoriesRequest;
use Ebay\requests\GetCategoryFeaturesRequest;
use Ebay\requests\GeteBayDetailsRequest;
use Ebay\requests\GetItemRequest;
use Ebay\requests\RelistItemRequest;
use Ebay\requests\Request;
use Ebay\requests\ReviseItemRequest;
use Ebay\requests\UploadSiteHostedPicturesRequest;
use Ebay\responses\AddItemResponse;
use Ebay\responses\AddItemsResponse;
use Ebay\responses\EndItemResponse;
use Ebay\responses\EndItemsResponse;
use Ebay\responses\GetCategoriesResponse;
use Ebay\responses\GetCategoryFeaturesResponse;
use Ebay\responses\GeteBayDetailsResponse;
use Ebay\responses\GetItemResponse;
use Ebay\responses\RelistItemResponse;
use Ebay\responses\Response;
use Ebay\responses\ReviseItemResponse;
use Ebay\responses\UploadSiteHostedPicturesResponse;
use Tygh\Http;
use Tygh\Registry;

/**
 * Class Client
 * @package Ebay
 */
class Client
{
    /** @var Client[] */
    protected static $instances = array();
    /** @var bool Sandbox flag */
    protected $is_sandbox = false;
    /** @var string|null Application token */
    protected $token;
    /** @var string|null Version api */
    protected $version = '957';
    /** @var string|null Application id */
    protected $application_id;
    /** @var string|null Developer id */
    protected $developer_id;
    /** @var string|null Certificate id */
    protected $certificate_id;
    /** @var string|null Site id */
    protected $site_id;
    /** @var Http http client */
    protected $http_client;
    /** @var array Errors */
    protected $errors = array();

    /**
     * Constructor
     *
     * @param string $token
     * @param string $application_id
     * @param string $developer_id
     * @param string $certificate_id
     * @param int $site_id
     * @param Http $http
     * @param bool $is_sandbox
     */
    public function __construct($token, $application_id, $developer_id, $certificate_id, $site_id, Http $http = null, $is_sandbox = false)
    {
        $this->token = $token;
        $this->application_id = $application_id;
        $this->developer_id = $developer_id;
        $this->certificate_id = $certificate_id;
        $this->site_id = $site_id;
        $this->is_sandbox = $is_sandbox;
        $this->http_client = $http;

        libxml_use_internal_errors(true);
    }

    /**
     * Create and publish items on ebay
     *
     * @param  Product[] $products
     * @return AddItemsResponse|false
     * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/AddItems.html
     */
    public function addItems(array $products)
    {
        return $this->makeRequest(
            new AddItemsRequest($products),
            AddItemsResponse::className()
        );
    }

    /**
     * Create and publish item on ebay
     *
     * @param  Product $product
     * @return AddItemResponse|false
     * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/AddItem.html
     */
    public function addItem(Product $product)
    {
        return $this->makeRequest(
            new AddItemRequest($product),
            AddItemResponse::className()
        );
    }

    /**
     * Update active item on ebay
     *
     * @param  Product $product
     * @return ReviseItemResponse|false
     * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/ReviseItem.html
     */
    public function reviseItem(Product $product)
    {
        return $this->makeRequest(
            new ReviseItemRequest($product),
            ReviseItemResponse::className()
        );
    }

    /**
     * Activate item on ebay
     *
     * @param  Product $product
     * @return RelistItemResponse|false
     * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/RelistItem.html
     */
    public function relistItem(Product $product)
    {
        return $this->makeRequest(
            new RelistItemRequest($product),
            RelistItemResponse::className()
        );
    }

    /**
     * End active items
     *
     * @param  Product[] $products
     * @return AddItemsResponse|false
     * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/EndItems.html
     */
    public function endItems(array $products)
    {
        return $this->makeRequest(
            new EndItemsRequest($products),
            EndItemsResponse::className()
        );
    }

    /**
     * End active item
     *
     * @param  Product $product
     * @return EndItemResponse|false
     * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/EndItem.html
     */
    public function endItem(Product $product)
    {
        return $this->makeRequest(
            new EndItemRequest($product),
            EndItemResponse::className()
        );
    }

    /**
     * Get item data
     *
     * @param Product $product
     * @return GetItemResponse|false
     * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/GetItem.html
     */
    public function getItem(Product $product)
    {
        return $this->makeRequest(
            new GetItemRequest($product),
            GetItemResponse::className()
        );
    }

    /**
     * Upload image on ebay hosting
     *
     * @param string $url Absolute url for picture
     * @param string $name Picture name
     * @return bool|UploadSiteHostedPicturesResponse
     */
    public function uploadImage($url, $name)
    {
        return $this->makeRequest(
            new UploadSiteHostedPicturesRequest($url, $name),
            UploadSiteHostedPicturesResponse::className()
        );
    }

    /**
     * Get meta-data for the specified eBay site
     *
     * @param string|array $details
     * @return bool|GeteBayDetailsResponse
     * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/GeteBayDetails.html
     */
    public function getEbayDetails($details)
    {
        return $this->makeRequest(
            new GeteBayDetailsRequest($details),
            GeteBayDetailsResponse::className()
        );
    }

    /**
     * Return category features
     *
     * @param string $category_id
     * @param array $feature_ids
     * @return bool|GetCategoryFeaturesResponse
     * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/GetCategoryFeatures.html
     *
     */
    public function getCategoryFeatures($category_id, $feature_ids)
    {
        return $this->makeRequest(
            new GetCategoryFeaturesRequest($category_id, $feature_ids),
            GetCategoryFeaturesResponse::className()
        );
    }

    /**
     * Return categories
     *
     * @param  string|array $parents Category parents
     * @param  null|int $level
     * @param  string $detail Detail level
     * @return bool|GetCategoriesResponse
     * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/GetCategories.html
     */
    public function getCategories($parents = array(), $level = null, $detail = '')
    {
        return $this->makeRequest(
            new GetCategoriesRequest($parents, $level, $detail),
            GetCategoriesResponse::className()
        );
    }

    /**
     * Clear errors
     */
    public function clearErrors()
    {
        $this->errors = array();
    }

    /**
     * Return errors
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Return api url
     * @return string
     */
    public function getUrl()
    {
        if ($this->is_sandbox) {
            return 'https://api.sandbox.ebay.com/ws/api.dll';
        } else {
            return 'https://api.ebay.com/ws/api.dll';
        }
    }

    /**
     * Return api version
     * @return null|string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Return application id
     * @return null|string
     */
    public function getApplicationId()
    {
        return $this->application_id;
    }

    /**
     * Return developer id
     * @return null|string
     */
    public function getDeveloperId()
    {
        return $this->developer_id;
    }

    /**
     * Return certificate id
     * @return null|string
     */
    public function getCertificateId()
    {
        return $this->certificate_id;
    }

    /**
     * Return token
     * @return null|string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Return site id
     * @return null|string
     */
    public function getSiteId()
    {
        return $this->site_id;
    }

    /**
     * Set site id
     * @param int $site_id
     */
    public function setSiteId($site_id)
    {
        $this->site_id = $site_id;
    }

    /**
     * Make request
     * @param Request $request
     * @param string $response_class
     * @return bool|Response
     */
    protected function makeRequest(Request $request, $response_class)
    {
        $transaction_id = TransactionLogger::startRequest($request);

        $this->clearErrors();
        $request->setToken($this->getToken());

        $response = $this->http_client->post($this->getUrl(), $request->getXml(), array(
            'headers' => array(
                'X-EBAY-API-COMPATIBILITY-LEVEL: ' . $this->getVersion(),
                'X-EBAY-API-CALL-NAME: ' . $request->getMethodName(),
                'X-EBAY-API-APP-NAME: ' . $this->getApplicationId(),
                'X-EBAY-API-DEV-NAME: ' . $this->getDeveloperId(),
                'X-EBAY-API-CERT-NAME: ' . $this->getCertificateId(),
                'X-EBAY-API-SITEID: ' . $this->getSiteId(),
                'Content-Type: text/xml'
            )
        ));

        $status = $this->http_client->getStatus();

        if ($status != '200') {
            $this->errors[] = __('ebay_failed_request', array('[url]' => $this->getUrl(), '[code]' => $status));
            $result = false;
        } elseif ($response !== false) {
            $result = simplexml_load_string($response);

            if ($result === false) {
                foreach (libxml_get_errors() as $error) {
                    /** @var \LibXMLError $error */
                    $this->errors[] = $error->message;
                }

                libxml_clear_errors();
            }
        } else {
            $this->errors[] = $this->http_client->getError();
            $result = false;
        }

        if ($result) {
            $response = new $response_class($result);
            TransactionLogger::endRequest($transaction_id, $response);

            return $response;
        }

        TransactionLogger::failRequest($transaction_id, count($this->errors));

        return false;
    }

    /**
     * Initialization api
     *
     * Object cached by internal cache
     * @param Template|null $template
     * @param Http|null $http_client
     * @return Client
     */
    public static function instance(Template $template = null, Http $http_client = null)
    {
        $key = $template !== null ? $template->id : 0;

        if (!isset(static::$instances[$key])) {
            if ($http_client === null) {
                $http_client = new Http();
            }

            $api = new self(
                Registry::get('addons.ebay.token'),
                Registry::get('addons.ebay.app_id'),
                Registry::get('addons.ebay.dev_id'),
                Registry::get('addons.ebay.cert_id'),
                $template !== null ? $template->site_id : Registry::get('addons.ebay.site_id'),
                $http_client,
                Registry::get('addons.ebay.listing_mode') != 'P'
            );

            static::$instances[$key] = $api;
        }

        return static::$instances[$key];
    }
}
