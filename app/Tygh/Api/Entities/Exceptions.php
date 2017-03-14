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

/**
 * Examples:
 *
 * -----===== GET =====-----
 * http://example.com/api/exceptions/?product_id=12
 *      product_id is required field if exception_id is empty
 *
 * Response:
 *      {
 *           exception_id: "1",
 *           product_id: "12",
 *           combination: {
 *              3: "12",
 *              4: "17"
 *           }
 *      },
 *      {
 *           exception_id: "2",
 *           product_id: "12",
 *           combination: {
 *               3: "13",
 *               4: "17"
 *           }
 *      }
 *
 *
 * http://example.com/api/exceptions/123
 *
 * Response:
 *      {
 *          exception_id: "2",
 *          product_id: "12",
 *          combination: {
 *              3: "13",
 *              4: "17"
 *          }
 *      }
 *
 * -----===== POST =====-----
 *
 * http://example.com/api/exceptions/
 *      product_id          4
 *      combination[3]      12
 *      combination[4]      14
 *      combination[7]      -2
 *
 * Response:
 *      {
 *          status: 200,
 *          data: {
 *              exception_id: 456
 *          }
 *      }
 *
 *
 * -----===== PUT =====-----
 *
 * http://example.com/api/exceptions/34
 *      product_id          4
 *      combination[3]      12
 *      combination[4]      14
 *      combination[7]      -2
 *
 * Response:
 *      {
 *          status: 200,
 *          data: {
 *              exception_id: 34
 *          }
 *      }
 *
 *
 * -----===== DELETE =====-----
 *
 * http://example.com/api/exceptions/34?product_id=12
 *      product_id is required field (permissions checking)
 *
 * Response:
 *
 *      {
 *          status: 204,
 *          data: {
 *              message: "Ok"
 *          }
 *      }
 *
 */

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;

class Exceptions extends AEntity
{
    public function index($id = 0, $params = array())
    {
        if (empty($id) && empty($params['product_id'])) {
            $status = Response::STATUS_BAD_REQUEST;
            $data['message'] = __('api_required_field', array(
                '[field]' => 'product_id',
            ));

        } else {
            $product_id = $this->safeGet($params, 'product_id', 0);

            if (empty($product_id)) {
                $product_id = db_get_field('SELECT product_id FROM ?:product_options_exceptions WHERE exception_id = ?i', $id);
            }

            $product_data = fn_get_product_data($product_id, $this->auth, DESCR_SL, '', false, false, false, false, false, false, false);
            if (empty($product_data)) {
                $status = Response::STATUS_NOT_FOUND;
                $data = array();
            } else {
                if (!empty($id)) {
                    $data = fn_get_product_exception_data($id);
                    $status = Response::STATUS_OK;

                } else {
                    $data = fn_get_product_exceptions($product_id);
                    $status = Response::STATUS_OK;
                }

                if (empty($data)) {
                    $status = Response::STATUS_NOT_FOUND;
                }
            }
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function create($params)
    {
        $status = Response::STATUS_OK;
        $data = array();

        $product_id = $this->safeGet($params, 'product_id', 0);

        list($_status, $message) = $this->checkProductId($product_id);

        if ($_status != Response::STATUS_OK) {
            return array(
                'status' => $status,
                'data' => array(
                    'message' => $message,
                ),
            );
        }

        $exception_id = fn_update_exception($params, 0);

        if ($exception_id) {
            $status = Response::STATUS_CREATED;
            $data = array(
                'exception_id' => $exception_id,
            );
        } else {
            $status = Response::STATUS_BAD_REQUEST;
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function update($id, $params)
    {
        $status = Response::STATUS_OK;
        $data = array();

        $product_id = $this->safeGet($params, 'product_id', 0);
        if (empty($product_id)) {
            $product_id = db_get_field('SELECT product_id FROM ?:product_options WHERE option_id = ?i', $id);
        }

        list($_status, $message) = $this->checkProductId($product_id);

        if ($_status != Response::STATUS_OK) {
            return array(
                'status' => $status,
                'data' => array(
                    'message' => $message,
                ),
            );
        }

        $exception_id = fn_update_exception($params, $id);

        if ($exception_id) {
            $data = array(
                'exception_id' => $exception_id,
            );
        } else {
            $status = Response::STATUS_BAD_REQUEST;
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function delete($id)
    {
        $data = array();
        $status = Response::STATUS_OK;

        $product_id = db_get_field('SELECT product_id FROM ?:product_options_exceptions WHERE exception_id = ?i', $id);

        list($_status, $message) = $this->checkProductId($product_id);

        if ($_status != Response::STATUS_OK) {
            return array(
                'status' => $status,
                'data' => array(
                    'message' => $message,
                ),
            );
        }

        if (fn_delete_exception($id)) {
            $status = Response::STATUS_NO_CONTENT;
            $data = array();
        } else {
            $status = Response::STATUS_NOT_FOUND;
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function privileges()
    {
        return array(
            'create' => 'manage_catalog',
            'update' => 'manage_catalog',
            'delete' => 'manage_catalog',
            'index'  => 'view_catalog'
        );
    }

    public function privilegesCustomer()
    {
        return array(
            'index' => true
        );
    }

    protected function checkProductId($product_id)
    {
        $status = Response::STATUS_OK;
        $message = '';

        if (!empty($product_id)) {
            $product_data = fn_get_product_data($product_id, $this->auth, DESCR_SL, '', false, false, false, false, false, false, false);
            if (empty($product_data)) {
                $status = Response::STATUS_FORBIDDEN;
                $message = __('access_denied');
            }
        } else {
            $status = Response::STATUS_BAD_REQUEST;
            $message = __('api_required_field', array(
                '[field]' => 'product_id',
            ));
        }

        return array($status, $message);
    }
}
