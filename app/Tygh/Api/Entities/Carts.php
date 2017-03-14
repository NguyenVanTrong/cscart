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

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Registry;

class Carts extends AEntity
{
    public function index($id = 0, $params = array())
    {
        $data = array();

        if (!empty($id)) {

            $data = $this->getCart($id, true);
            if ($data) {
                $status = Response::STATUS_OK;
            } else {
                $status = Response::STATUS_NOT_FOUND;
            }

        } else {

            $params['company_id'] = $this->getCompanyId();
            $items_per_page = $this->safeGet(
                $params, 'items_per_page', Registry::get('settings.Appearance.admin_elements_per_page')
            );

            unset($params['online_only']);
            list($carts, $search) = fn_get_carts($params, $items_per_page);

            $data = array(
                'carts' => array_values($carts),
                'params' => $search
            );
            $status = Response::STATUS_OK;
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function create($params)
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        );
    }

    public function update($id, $params)
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        );
    }

    public function delete($id)
    {
        $status = Response::STATUS_NOT_FOUND;
        $data = array();

        $cart = $this->getCart($id);
        if ($cart) {
            $data = '';
            if (fn_allowed_for('ULTIMATE')) {
                $data = $cart['company_id'];
            }
            if (fn_delete_user_cart($id, $data)) {
                $status = Response::STATUS_NO_CONTENT;
            }
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function privileges()
    {
        return array(
            'create' => 'manage_users',
            'update' => 'manage_users',
            'delete' => 'manage_users',
            'index'  => 'view_users'
        );
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

    protected function getCart($user_id, $get_cart_products = false)
    {
        list($carts) = fn_get_carts(array(
            'user_id' => $user_id,
            'company_id' => $this->getCompanyId(),
        ));

        if (!$carts) {
            return array();
        }

        $cart = reset($carts);

        if ($get_cart_products) {
            $params = array();
            if (fn_allowed_for('ULTIMATE')) {
                $params['c_company_id'] = $cart['company_id'];
            }
            $cart['products'] = fn_get_cart_products($cart['user_id'], $params);
        }

        return $cart;
    }

}
