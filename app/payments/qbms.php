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

if (!defined('BOOTSTRAP')) {
    require './init_payment.php';
}

use Tygh\Http;

/**
 * Class QuickbooksAuth
 *
 * Performs OAuth authentication and requests signing
 */
class QuickbooksAuth
{
    /**
     * Signing method constants
     */
    const REQUEST_GET_REQUEST_TOKEN  = 1;
    const REQUEST_GET_ACCESS_TOKEN   = 2;
    const REQUEST_CHARGE             = 4;

    /**
     * OAuth Request Token URL
     */
    const OAUTH_REQUEST_URL = 'https://oauth.intuit.com/oauth/v1/get_request_token';

    /**
     * OAuth Access Token URL
     */
    const OAUTH_ACCESS_URL = 'https://oauth.intuit.com/oauth/v1/get_access_token';

    /**
     * OAuth Authorize URL
     */
    const OAUTH_AUTHORIZE_URL = 'https://appcenter.intuit.com/Connect/Begin';

    /**
     * OAuth Token Renewal URL
     */
    const OAUTH_RENEWAL_URL = 'https://appcenter.intuit.com/api/v1/connection/reconnect';

    /**
     * @var string App Token - obtained from Qucikbooks Dashboard
     */
    public $app_token;

    /**
     * @var string OAuth Consumer Key  - obtained from Qucikbooks Dashboard
     */
    public $consumer_key;

    /**
     * @var string OAuth Token
     */
    public $token;

    /**
     * @var string Realm ID (ex-Customer ID)
     */
    public $realm_id;

    /**
     * @var int Payment identifier
     */
    private $payment_id;

    /**
     * @var string OAuth consumer secret - obtained from Qucikbooks Dashboard
     */
    private $consumer_secret;

    /**
     * @var string OAuth access token secret
     */
    private $token_secret;

    /**
     * QuickbooksAuth constructor
     *
     * @param int   $payment_id Payment method identifier
     * @param array $auth_data  Processor parameters
     */
    public function __construct($payment_id = 0, $auth_data = array())
    {
        $this->app_token       = empty($auth_data['app_token'])             ? '' : $auth_data['app_token'];
        $this->consumer_key    = empty($auth_data['oauth_consumer_key'])    ? '' : $auth_data['oauth_consumer_key'];
        $this->consumer_secret = empty($auth_data['oauth_consumer_secret']) ? '' : $auth_data['oauth_consumer_secret'];
        $this->token           = empty($auth_data['oauth_token'])           ? '' : $auth_data['oauth_token'];
        $this->token_secret    = empty($auth_data['oauth_token_secret'])    ? '' : $auth_data['oauth_token_secret'];
        $this->realm_id        = empty($auth_data['realm_id'])              ? '' : $auth_data['realm_id'];
        $this->payment_id      = empty($payment_id)                         ?  0 : $payment_id;
    }

    /**
     * Prepare data for OAuth-based request
     *
     * @param int    $type   Request type
     * @param string $method Request method (GET/POST)
     * @param string $url    Request URL
     * @param array  $data   Request data
     *
     * @return array Pair of request fields and authentication header
     */
    public function signRequest($type = self::REQUEST_GET_REQUEST_TOKEN, $method = Http::POST, $url = '', $data = array())
    {
        $fields = array(
            'oauth_consumer_key' => $this->consumer_key,
            'oauth_nonce' => TIME,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => TIME,
            'oauth_version' => '1.0',
        );

        switch ($type) {
            case self::REQUEST_CHARGE:
                $fields['oauth_token'] = $this->token;
                break;
            case self::REQUEST_GET_REQUEST_TOKEN:
                $fields['oauth_callback'] = self::getCallbackUrl($this->payment_id);
                break;
            case self::REQUEST_GET_ACCESS_TOKEN:
                $fields['oauth_callback'] = self::getCallbackUrl($this->payment_id);
                $fields['oauth_verifier'] = $data['oauth_verifier'];
                $fields['oauth_token']    = $data['oauth_token'];
                break;
        }

        ksort($fields);

        $encodedFields = array();
        foreach ($fields as $key => $value) {
            $encodedFields[] = rawurlencode($key) . '=' . rawurlencode($value);
        }

        $signatureData = strtoupper($method) . '&'
            . rawurlencode($url) . '&'
            . rawurlencode(implode('&', $encodedFields));

        $key = rawurlencode($this->consumer_secret) . '&';
        if ($type != self::REQUEST_GET_REQUEST_TOKEN) {
            $key .= rawurlencode($this->token_secret);
        }

        $fields['oauth_signature'] = base64_encode(hash_hmac('SHA1', $signatureData, $key, 1));

        $auth_header = '';
        switch ($type) {
            case self::REQUEST_CHARGE:
                // for some reason, signature has to be urlencoded for charges
                $fields['oauth_signature'] = rawurlencode($fields['oauth_signature']);
            case self::REQUEST_GET_ACCESS_TOKEN:
                $auth_header = 'Authorization: OAuth '
                    . 'oauth_token="' . $fields['oauth_token'] . '",'
                    . 'oauth_nonce="' . $fields['oauth_nonce'] . '",'
                    . 'oauth_consumer_key="' . $fields['oauth_consumer_key'] . '",'
                    . 'oauth_signature_method="' . $fields['oauth_signature_method'] . '",'
                    . 'oauth_timestamp="' . $fields['oauth_timestamp'] . '",'
                    . 'oauth_version="' . $fields['oauth_version'] . '",'
                    . 'oauth_signature="' . $fields['oauth_signature'] . '"';
                break;
        }

        return array(
            $fields,
            $auth_header
        );
    }

    /**
     * Get OAuth Request token data
     *
     * @return array Pair of request token data and authentication URL
     */
    public function getRequestToken()
    {
        list($request_data) = $this->signRequest(self::REQUEST_GET_REQUEST_TOKEN, Http::POST, self::OAUTH_REQUEST_URL);

        $request_token = Http::post(self::OAUTH_REQUEST_URL, $request_data);
        parse_str($request_token, $request_token);

        return array(
            $request_token,
            isset($request_token['oauth_token']) ?
                self::OAUTH_AUTHORIZE_URL . '?oauth_token=' . $request_token['oauth_token'] :
                ''
        );
    }

    /**
     * Get OAuth callback URL
     *
     * @param  int    $payment_id Payment identifier
     * @return string OAuth callback URL
     */
    public static function getCallbackUrl($payment_id = 0)
    {
        return fn_payment_url('current', basename(__FILE__)) . '?qb_action=auth_callback&payment_id=' . $payment_id;
    }

    /**
     * Get OAuth Access token data
     *
     * @param string $token    OAuth token
     * @param string $verifier OAuth token verifier
     * @param int    $realm_id Realm ID
     *
     * @return array OAuth access token data
     */
    public function getAccessToken($token = '', $verifier = '', $realm_id = 0)
    {
        list($request_data) = $this->signRequest(self::REQUEST_GET_ACCESS_TOKEN, Http::POST, self::OAUTH_ACCESS_URL, array(
            'oauth_verifier' => $verifier,
            'oauth_token' => $token,
        ));

        $access_token = Http::post(self::OAUTH_ACCESS_URL, $request_data);
        parse_str($access_token, $access_token);
        $access_token['realm_id'] = $realm_id;
        $access_token['token_expire_time'] = TIME + 180 * SECONDS_IN_DAY;

        return $this->setAccessToken($access_token);
    }

    /**
     * Store OAuth Access token data
     *
     * @param array $access_token OAuth access token data
     *
     * @return array OAuth access token data
     */
    private function setAccessToken($access_token = array())
    {
        $this->token        = empty($access_token['oauth_token'])        ? '' : $access_token['oauth_token'];
        $this->token_secret = empty($access_token['oauth_token_secret']) ? '' : $access_token['oauth_token_secret'];
        $this->realm_id     = empty($access_token['realm_id'])           ? '' : $access_token['realm_id'];

        return $access_token;
    }
}

/**
 * Class QuickbooksPaymentMethod
 *
 * Perform payments via Qucikbooks Payments API
 */
class QuickbooksPaymentMethod
{
    /**
     * Payment status
     */
    const PAYMENT_STATUS_DECLINED = 'DECLINED';

    /**
     * @var int Payment identifier
     */
    private $payment_id;

    /**
     * @var string Payment gateway URL
     */
    private $gateway_url;

    /**
     * @var bool True if payments are performed on sandbox
     */
    private $test_mode = false;

    /**
     * @var array Payment processor parameter
     */
    private $processor_params;

    /**
     * QuickbooksPaymentMethod constructor
     *
     * @param int        $payment_id     Payment identifier
     * @param array|null $processor_data Payment processor data
     */
    public function __construct($payment_id = 0, $processor_data = null)
    {
        $this->payment_id = $payment_id;
        if (is_null($processor_data)) {
            $processor_data = fn_get_processor_data($payment_id);
        }
        $this->processor_params = $processor_data['processor_params'];

        $this->test_mode = $this->processor_params['mode'] == 'test';

        if ($this->test_mode) {
            $this->gateway_url = 'https://sandbox.api.intuit.com/quickbooks/v4/payments/charges';
        } else {
            $this->gateway_url = 'https://api.intuit.com/quickbooks/v4/payments/charges';
        }
    }

    /**
     * Prepare card info
     *
     * @param array $order_info Order info
     *
     * @return array Card info
     */
    private function prepareCardData($order_info)
    {
        // Quickbooks requires expYear to be specified in 4-digit format
        $year_prefix = substr(date('Y'), 0, 2);

        return array(
            'expYear' => $year_prefix . $order_info['payment_info']['expiry_year'],
            'expMonth' => $order_info['payment_info']['expiry_month'],
            'address' => $this->prepareAddress($order_info),
            'name' => $order_info['payment_info']['cardholder_name'],
            'cvc' => $order_info['payment_info']['cvv2'],
            'number' => $order_info['payment_info']['card_number']
        );
    }

    /**
     * Prepare card address
     *
     * @param array $order_info Order info
     *
     * @return array Address
     */
    private function prepareAddress($order_info = array())
    {
        $address_fields = array(
            'b_state' => '',
            'b_zipcode' => '',
            'b_address' => '',
            'b_country' => '',
            'b_city' => ''
        );
        $order_info = array_merge($address_fields, $order_info);

        return array(
            'region' => $order_info['b_state'],
            'postalCode' => $order_info['b_zipcode'],
            'streetAddress' => $order_info['b_address'],
            'country' => $order_info['b_country'],
            'city' => $order_info['b_city'],
        );
    }

    /**
     * Prepare payment request body
     *
     * @param array $order_info Order info
     *
     * @return string JSON-encoded request body
     */
    public function prepareRequestData($order_info)
    {
        return json_encode(array(
            'amount' => $order_info['total'],
            'card' => $this->prepareCardData($order_info),
            'currency' => CART_SECONDARY_CURRENCY,
        ));
    }

    /**
     * Perform payment
     *
     * @param array $order_info Order info
     *
     * @return array $pp_response
     */
    public function charge($order_info)
    {
        $request_id = $this->processor_params['order_prefix'] . $order_info['order_id'] . '_' . TIME;
        $request_data = $this->prepareRequestData($order_info);

        $qa = new QuickbooksAuth($this->payment_id, $this->processor_params);
        list($fields, $auth_header) = $qa->signRequest(QuickbooksAuth::REQUEST_CHARGE, Http::POST, $this->gateway_url);

        $response = Http::post(
            $this->gateway_url,
            $request_data,
            array(
                'headers' => array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($request_data),
                    'Accept: application/json',
                    'Request-ID: ' . $request_id,
                    $auth_header
                )
            )
        );

        return $this->processPaymentResponse($response);
    }

    /**
     * Process payment request response
     *
     * @param string $response Response text
     *
     * @return array Result of performing request
     */
    public function processPaymentResponse($response = '')
    {
        $result = array(
            'order_status' => 'P',
            'reason_text' => ''
        );

        $response = json_decode($response, 1);

        if ($this->paymentResponseHasErrors($response)) {
            if (empty($response['errors'])) {
                $response['errors'] = array();
            }
            $result['order_status'] = 'F';
            $result['reason_text'] = $this->getErrorMessage($response);
        } else {
            $result['transaction_id'] = $response['id'];
        }

        return $result;
    }

    /**
     * Check if payment response has errors
     *
     * @param array $response Response data
     *
     * @return bool True if response has errors
     */
    private function paymentResponseHasErrors($response)
    {
        return
            empty($response) || !empty($response['errors']) ||
            isset($response['status']) && $response['status'] == self::PAYMENT_STATUS_DECLINED;
    }

    /**
     * Process payment error
     *
     * @param array $response Response data
     *
     * @return string Error message
     */
    private function getErrorMessage($response = array())
    {
        $message = array();
        if (isset($response['status']) && $response['status'] == self::PAYMENT_STATUS_DECLINED) {
            $message[] = __('text_transaction_declined');
        } elseif (empty($response['errors'])) {
            $message[] = 'Payment gateway error';
        }
        foreach ($response['errors'] as $error) {
            $message[] =  "{$error['code']}: {$error['message']}" . (empty($error['moreInfo']) ? '' : " {$error['moreInfo']}");
        }

        return implode(', ', $message);
    }
}

/*****************************************************************************/

$qb_action = (!empty($_REQUEST['qb_action'])) ? $_REQUEST['qb_action'] : 'pay';
if (isset($_REQUEST['oauth_verifier'])) {
    $qb_action = 'auth_callback';
}

switch ($qb_action) {
    case 'auth_start':
        $payment_id = $_REQUEST['payment_id'];
        $payment_data = fn_get_processor_data($payment_id);

        $auth_provider = new QuickbooksAuth($payment_id, $payment_data['processor_params']);
        list($request_token, $auth_url) = $auth_provider->getRequestToken();

        if ($auth_url) {
            Tygh::$app['session']['qb_request_token'] = $request_token;
            fn_redirect($auth_url, true);
        } else {
            foreach ($request_token as $key => $value) {
                echo "<p>{$key}: {$value}</p>";
            }
        }
        exit;

    case 'auth_callback':
        $payment_id = $_REQUEST['payment_id'];
        $payment_data = fn_get_payment_method_data($payment_id);

        $processor_params = array_merge(
            $payment_data['processor_params'],
            Tygh::$app['session']['qb_request_token']
        );
        unset(Tygh::$app['session']['qb_request_token']);

        $auth_provider = new QuickbooksAuth($payment_id, $processor_params);
        $access_token = $auth_provider->getAccessToken($_REQUEST['oauth_token'], $_REQUEST['oauth_verifier'], $_REQUEST['realmId']);

        foreach ($access_token as $field => $value) {
            $payment_data['processor_params'][$field] = $value;
        }
        fn_update_payment($payment_data, $payment_id);

        // close auth pop-up
        echo '<script>window.open("", "_parent", ""); window.close();</script>';
        exit;

    default:
    case 'pay':
        $qb = new QuickbooksPaymentMethod($order_info['payment_id'], $processor_data);
        $pp_response = $qb->charge($order_info);
        break;
}
