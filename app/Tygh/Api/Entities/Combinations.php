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
 * http://example.com/api/combinations/?product_id=12
 *      product_id is required field if combination_id is empty
 *
 * Response:
 *      {
 *           product_id: "12",
 *           product_code: "BW/S"
 *           combination_hash: "822274303",
 *           combination: {
 *              3: "12",
 *              4: "17"
 *           },
 *           amount: "50",
 *           position: "0",
 *           image_pairs: { skipped }
 *      },
 *      {
 *           combination_hash: "345234623",
 *           combination: {
 *              3: "14",
 *              4: "18"
 *           },
 *           ...
 *           image_pairs: { skipped }
 *      }
 *
 *
 * http://example.com/api/combinations/822274303
 *
 * Response:
 *      {
 *           product_id: "12",
 *           product_code: "BW/S"
 *           combination_hash: "822274303",
 *           combination: {
 *              3: "12",
 *              4: "17"
 *           },
 *           amount: "50",
 *           position: "0",
 *           image_pairs: { skipped }
 *      },
 *
 * -----===== POST =====-----
 *
 * http://example.com/api/combinations/
 *      product_id          4
 *      combination[3]      12
 *      combination[4]      14
 *      product_code        BBM/L
 *      amount              34
 *      position            10
 *
 *      * Image data cannot be updated here. Combination hash required first.
 *
 * Response:
 *      {
 *          status: 201,
 *          data: {
 *              combination_hash: "3833095923"
 *          }
 *      }
 *
 *
 * -----===== PUT =====-----
 *
 * http://example.com/api/combinations/3833095923
 *      product_id          4
 *      amount              2
 *      product_code        'NEW_CODE'
 *      ...                 (any field from :?product_options_inventory, except combination. The "combination" field cannot be updated directly)\
 *
 *      main_pair[icon][image_path]         exim/backup/images/1000156675_f_icon.jpg
 *      main_pair[icon][alt]                Icon ALT text description
 *      main_pair[detailed][image_path]     exim/backup/images/1000156675_f_detailed.jpg
 *      main_pair[detailed][alt]            Detailed image description
 *
 *
 *
 * Response:
 *      {
 *          status: 200,
 *          data: {
 *              combination_hash: "3833095923"
 *          }
 *      }
 *
 *
 * -----===== DELETE =====-----
 *
 * http://example.com/api/combinations/3833095923?product_id=12
 *      product_id is required field (permissions checking)
 *
 * Response:
 *
 *      {
 *          status: 204,
 *          data: {}
 *      }
 *
 */

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;

class Combinations extends AEntity
{
    public function index($id = 0, $params = array())
    {
        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);

        if (empty($id) && empty($params['product_id'])) {
            $status = Response::STATUS_BAD_REQUEST;
            $data['message'] = __('api_required_field', array(
                '[field]' => 'product_id',
            ));

        } else {
            $product_id = $this->safeGet($params, 'product_id', 0);

            if (empty($product_id)) {
                $product_id = db_get_field('SELECT product_id FROM ?:product_options_inventory WHERE combination_hash = ?s', $id);
            }

            $product_data = fn_get_product_data($product_id, $this->auth, $lang_code, '', false, false, false, false, false, false, false);
            if (empty($product_data)) {
                $status = Response::STATUS_NOT_FOUND;
                $data = array();
            } else {
                if (!empty($id)) {
                    $data = fn_get_product_options_combination_data($id, $lang_code);
                    $status = Response::STATUS_OK;

                } else {
                    $params['product_id'] = $product_id;
                    list($data) = fn_get_product_options_inventory($params, 0, $lang_code);

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

        $combination_hash = fn_update_option_combination($params, 0);

        if ($combination_hash) {
            $status = Response::STATUS_CREATED;
            $data = array(
                'combination_hash' => $combination_hash,
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
            $product_id = db_get_field('SELECT product_id FROM ?:product_options_inventory WHERE combination_hash = ?s', $id);
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

        $this->prepareImages($params, $id, 'combinations');

        $combination_hash = fn_update_option_combination($params, $id);

        if ($combination_hash) {
            $data = array(
                'combination_hash' => $combination_hash,
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

        $product_id = db_get_field('SELECT product_id FROM ?:product_options_inventory WHERE combination_hash = ?s', $id);
        list($_status, $message) = $this->checkProductId($product_id);

        if ($_status != Response::STATUS_OK) {
            return array(
                'status' => $status,
                'data' => array(
                    'message' => $message,
                ),
            );
        }

        if (fn_delete_option_combination($id)) {
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
