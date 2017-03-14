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

class CallRequests extends AEntity
{
    public function index($id = 0, $params = array())
    {
        $data = array();

        if (!empty($id)) {
            $data = $this->getCallRequest($id);
            if ($data) {
                $status = Response::STATUS_OK;
            } else {
                $status = Response::STATUS_NOT_FOUND;
            }

        } else {
            $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);

            $params['company_id'] = $this->getCompanyId($params);
            $params['items_per_page'] = $this->safeGet(
                $params, 'items_per_page', Registry::get('settings.Appearance.admin_elements_per_page')
            );

            list($call_requests, $search) = fn_get_call_requests($params, $lang_code);

            $data = array(
                'call_requests' => array_values($call_requests),
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
        $valid_params = true;

        $data = array();
        $status = Response::STATUS_BAD_REQUEST;

        if (empty($params['phone']) && empty($params['email'])) {
            $data['message'] = __('api_required_field', array('[field]' => 'phone'));
        } else {

            $params['company_id'] = $this->getCompanyId($params);
            $request_id = fn_update_call_request($params);

            if ($request_id) {
                $status = Response::STATUS_CREATED;
                $data = array(
                    'request_id' => $request_id,
                );
            }

        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function update($id, $params)
    {
        $data = array();
        $status = Response::STATUS_BAD_REQUEST;

        if ($this->getCallRequest($id)) {
            unset($params['company_id']);

            if (fn_update_call_request($params, $id)) {
                $status = Response::STATUS_OK;
                $data = array(
                    'request_id' => $id
                );
            }

        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function delete($id)
    {
        $status = Response::STATUS_NOT_FOUND;
        $data = array();

        if ($this->getCallRequest($id) && fn_delete_call_request($id)) {
            $status = Response::STATUS_NO_CONTENT;
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function privileges()
    {
        return array(
            'create' => 'manage_call_requests',
            'update' => 'manage_call_requests',
            'delete' => 'manage_call_requests',
            'index'  => 'view_call_requests'
        );
    }

    protected function getCompanyId($params = array())
    {
        if (Registry::get('runtime.simple_ultimate')) {
            $company_id = Registry::get('runtime.forced_company_id');
        } else {
            $company_id = Registry::get('runtime.company_id');
        }

        if (empty($company_id) && !empty($params['company_id'])) {
            $company_id = $params['company_id'];
        }

        return $company_id;
    }

    protected function getCallRequest($id)
    {
        list($call_requests) = fn_get_call_requests(array(
            'id' => $id,
            'company_id' => $this->getCompanyId(),
        ));
        if ($call_requests) {
            return reset($call_requests);
        }

        return false;
    }

}
