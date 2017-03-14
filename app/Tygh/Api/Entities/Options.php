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
 * http://example.com/api/options/?product_id=12
 *      product_id is required field if exception_id is empty
 *
 * Response:
 *      {
 *           option_id: "3",
 *           option_type: "S",
 *           option_name: "Size"
 *           ...
 *           variants: {
 *              3: {...},
 *              4: {...}
 *           }
 *      },
 *      {
 *          ...
 *      }
 *
 *
 * http://example.com/api/options/3
 *
 * Response:
 *      {
 *           option_id: "3",
 *           option_type: "S",
 *           option_name: "Size"
 *           ...
 *           variants: {
 *              3: {...},
 *              4: {...}
 *           }
 *      }
 *
 * -----===== POST =====-----
 *
 * http://example.com/api/options/
 *      product_id          12      (required field)
 *      option_name         Color
 *      required            Y
 *      allowed_extensions  jpg,png
 *      inventory           N
 *      ...                 (any field from :?product_options)
 *      option_type         S
 *
 *      variants[1][variant_name]       Red
 *      variants[1][modifier_type]      P
 *      variants[1][modifier]           12
 *      ...                             (any field from :?product_option_variants)
 *      variants[2][variant_name]       Green
 *
 *      main_pair[icon][image_path][1]  exim/backup/images/1000156675_f_icon.jpg
 *      main_pair[icon][alt][1]         Some ALT text
 *      main_pair[icon][image_path][2]  exim/backup/images/15456_icon.jpg
 *      main_pair[icon][alt][2]         Some ALT text2
 *
 *
 *
 * Response:
 *      {
 *          status: 200,
 *          data: {
 *              option_id: 456
 *          }
 *      }
 *
 *
 * -----===== PUT =====-----
 *
 * http://example.com/api/options/34
 *      product_id          12      (required field)
 *      option_name         Color
 *      required            Y
 *      allowed_extensions  jpg,png
 *      inventory           N
 *      ...                 (any field from :?product_options)
 *      option_type         S
 *
 *      variants[1][variant_name]  Red
 *      variants[1][modifier_type] P
 *      variants[1][modifier]      12
 *      ...                 (any field from :?product_option_variants)
 *      variants[2][variant_name]  Green
 *
 *      ALL VARIANTS MUST BE SPECIFIED (!) otherwise unspecified variants will be removed
 *
 * Response:
 *      {
 *          status: 200,
 *          data: {
 *              option_id: 34
 *          }
 *      }
 *
 *
 * -----===== DELETE =====-----
 *
 * http://example.com/api/options/34
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
use Tygh\Registry;

class Options extends AEntity
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
                $product_id = db_get_field('SELECT product_id FROM ?:product_options WHERE option_id = ?i', $id);
            }

            $product_data = fn_get_product_data($product_id, $this->auth, $lang_code, '', false, false, false, false, false, false, false);
            if (empty($product_data)) {
                $status = Response::STATUS_NOT_FOUND;
                $data = array();
            } else {
                if (!empty($id)) {
                    $data = fn_get_product_option_data($id, $product_id, $lang_code);
                    $status = Response::STATUS_OK;

                } else {
                    $data = fn_get_product_options($product_id, $lang_code);
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

        $params['company_id'] = $this->getCompanyId();
        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);

        $this->prepareImages($params, 0, 'variant_image', 'V');

        unset($params['option_id']);

        $option_id = fn_update_product_option($params, 0, $lang_code);

        if ($option_id) {
            $status = Response::STATUS_CREATED;
            $data = array(
                'option_id' => $option_id,
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

        $params['company_id'] = $this->getCompanyId();
        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);

        $this->prepareImages($params, 0, 'variant_image', 'V');

        $option_id = fn_update_product_option($params, $id, $lang_code);

        if ($option_id) {
            $data = array(
                'option_id' => $option_id,
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
        $status = Response::STATUS_NO_CONTENT;

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

        if (fn_delete_product_option($id)) {
            $status = Response::STATUS_NO_CONTENT;
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

    protected function getCompanyId()
    {
        if (Registry::get('runtime.simple_ultimate')) {
            $company_id = Registry::get('runtime.forced_company_id');
        } else {
            $company_id = Registry::get('runtime.company_id');
        }

        return $company_id;
    }
}
