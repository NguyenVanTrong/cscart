<?php
/***************************************************************************
*                                                                          *
*    Copyright (c) 2009 Simbirsk Technologies Ltd. All rights reserved.    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

namespace Tygh;

/**
 * Pest lib wrapper
 *
 * Usage example:
 *
 * try {
 *     $client = new RestClient('http://example.com/api/', 'admin@example.com', 'YOUAPIKEY');
 *     $res = $client->get('products');
 *     $client->put('products/1', []);
 *     $client->delete('products/2');
 *     $client->post('products', []);
 * } catch (\Pest_Exception $e) {
 *     //
 * }
 *
 * All requests must be wrapped with try { ... } catch.
 *
 * # Available exceptions:
 * ## Common:
 *     Pest_Exception - all exceptions
 *     Pest_UnknownResponse
 * ## HTTP Errors:
 *     Pest_ClientError - 401-499
 *     Pest_BadRequest - 400
 *     Pest_Unauthorized - 401
 *     Pest_Forbidden - 403
 *     Pest_NotFound - 404
 *     Pest_MethodNotAllowed - 405
 *     Pest_Conflict - 409
 *     Pest_Gone - 401
 *     Pest_InvalidRecord - 422
 *     Pest_ServerError - 500-599
 * ## CURL Errors:
 *     Pest_Curl_Init
 *     Pest_Curl_Meta
 *     Pest_Curl_Exec
 * ## JSON
 *     Pest_Json_Decode
 *     Pest_Json_Encode
 * ## XML
 *     PestXML_Exception
 */

class RestClient
{
    protected $rest_client;
    protected $headers = array();

    public function __construct($url, $user = null, $password = null, $auth_type = 'basic', $headers = array(), $type = 'json')
    {
        if ($type == 'json') {
            $this->rest_client = new \PestJSON($url);
        } elseif ($type == 'xml') {
            $this->rest_client = new \PestXML($url);
        } else {
            $this->rest_client = new \Pest($url);
        }

        if (!empty($user) || !empty($password)) {
            $this->rest_client->setupAuth($user, $password, $auth_type);
        }

        $this->headers = $headers;
    }

    public function get($url, $data = array())
    {
        return $this->request('get', $url, $data);
    }

    public function post($url, $data = array())
    {
        return $this->request('post', $url, $data);
    }

    public function put($url, $data = array())
    {
        return $this->request('put', $url, $data);
    }

    public function delete($url)
    {
        return $this->request('delete', $url);
    }

    protected function request($method, $url, $data = array())
    {
        if (is_array($url)) {
            $url = '?' . http_build_query($url);
        }

        if ($method == 'delete') {
            $data = $this->headers;
        }

        $res = $this->rest_client->$method($url, $data, $this->headers);

        return $res;
    }

}
