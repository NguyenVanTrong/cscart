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

class Pages extends AEntity
{
    public function index($id = 0, $params = array())
    {
        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);

        if (!empty($id)) {
            $data = fn_get_page_data($id, $lang_code, false, $this->area);

            if ($data) {
                $status = Response::STATUS_OK;
            } else {
                $status = Response::STATUS_NOT_FOUND;
            }

        } else {
            $items_per_page = $this->safeGet(
                $params, 'items_per_page', Registry::get('settings.Appearance.admin_elements_per_page')
            );
            if (empty($params['page_type'])) {
                $params['full_search'] = true;
            }
            list($pages, $search) = fn_get_pages($params, $items_per_page, $lang_code);

            $data = array(
                'pages' => array_values($pages),
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

        $required_fields = array('page_type', 'page');
        foreach ($required_fields as $field) {
            if (!isset($params[$field])) {
                $data['message'] = __('api_required_field', array(
                    '[field]' => $field
                ));
                $valid_params = false;
                break;
            }
        }

        if ($valid_params) {
            $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);

            $params['company_id'] = $this->getCompanyId();
            if (empty($params['parent_id'])) {
                $params['parent_id'] = 0;
            }

            $page_id = fn_update_page($params, 0, $lang_code);

            if ($page_id) {
                $status = Response::STATUS_CREATED;
                $data = array(
                    'page_id' => $page_id,
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

        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);

        $params['company_id'] = $this->getCompanyId();
        unset($params['page_type'], $params['page_id']);
        $page_id = fn_update_page($params, $id, $lang_code);

        if ($page_id) {
            $status = Response::STATUS_OK;
            $data = array(
                'page_id' => $page_id
            );
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function delete($id)
    {
        $data = array();
        $status = Response::STATUS_BAD_REQUEST;

        if (!fn_page_exists($id)) {
            $status = Response::STATUS_NOT_FOUND;
        } elseif (fn_delete_page($id)) {
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
            'create' => 'manage_pages',
            'update' => 'manage_pages',
            'delete' => 'manage_pages',
            'index'  => 'view_pages'
        );
    }

    protected function getCompanyId()
    {
        if (Registry::get('runtime.simple_ultimate')) {
            $company_id = Registry::get('runtime.forced_company_id');
        } else {
            $company_id = Registry::get('runtime.company_id');
        }

        if ($company_id) {
            Registry::set('sharing_owner.pages', $company_id);
        }

        return $company_id;
    }

}
