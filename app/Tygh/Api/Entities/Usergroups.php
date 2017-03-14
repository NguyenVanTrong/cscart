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

class Usergroups extends AEntity
{
    public function privileges()
    {
        return array(
            'create' => 'manage_usergroups',
            'update' => 'manage_usergroups',
            'delete' => 'manage_usergroups',
            'index'  => 'view_usergroups'
        );
    }

    public function isAccessable($method_name)
    {
        $is_accessable = parent::isAccessable($method_name);
        if ($is_accessable && in_array($method_name, array('create', 'update', 'delete'))) {
            $is_accessable = fn_check_permissions('usergroups', 'update', 'admin');
        }

        return $is_accessable;
    }

    public function index($id = '', $params = array())
    {
        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);
        if (isset($params['lang_code'])) {
            unset($params['lang_code']);
        }

        if ($this->getParentName() == 'users') {
            if (empty($id)) {
                return $this->showForUser($params);
            } else {
                return array('status' => Response::STATUS_BAD_REQUEST);
            }
        }

        if (empty($id)) {
            $data = fn_get_usergroups($params, $lang_code);
            $status = Response::STATUS_OK;
        } else {
            $data = fn_get_usergroups(
                array(
                    'usergroup_id'    => (int)$id,
                    'with_privileges' => true,
                    'include_default' => true
                ),
                $lang_code
            );
            $data = reset($data);
            $status = empty($data) ? Response::STATUS_NOT_FOUND : Response::STATUS_OK;
        }

        return array(
            'status' => $status,
            'data'   => $data
        );
    }

    public function create($params)
    {
        return $this->createOrUpdate($params);
    }

    public function update($id, $params)
    {
        return $this->createOrUpdate($params, $id);
    }

    public function delete($id)
    {
        if ($this->getParentName() == 'users') {
            return $this->changeUserToGroupLink($id, array('status' => 'F'));
        }

        if (!fn_is_usergroup_exists($id)) {
            return array('status' => Response::STATUS_NOT_FOUND);
        }

        fn_delete_usergroups((array)$id);

        return array('status' => Response::STATUS_NO_CONTENT, 'data' => array());
    }

    protected function showForUser($params = array())
    {
        $user = $this->getParentData();

        return array(
            'status' => Response::STATUS_OK,
            'data'   => fn_get_user_usergroup_links($user['user_id'], $params)
        );
    }

    protected function createOrUpdate($params, $id = null)
    {
        $data = array();
        $status = Response::STATUS_BAD_REQUEST;
        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);
        unset($params['lang_code']);

        if ($this->getParentName() == 'users') {
            if (!empty($id)) {
                return $this->changeUserToGroupLink($id, $params);
            } else {
                return array('status' => Response::STATUS_BAD_REQUEST);
            }
        }

        $valid_params = true;
        if (empty($params['status'])) {
            $data['message'] = __('api_required_field', array('[field]' => 'status'));
            $valid_params = false;
        }
        if (empty($params['type'])) {
            $data['message'] = __('api_required_field', array('[field]' => 'type'));
            $valid_params = false;
        }

        if ($valid_params) {
            // We shouldn't allow to change or specify an ID of record
            unset($params['usergroup_id']);

            // Sanitize and make input data compatible with fn_update_usergroup internals
            if (isset($params['privileges'])) {
                $params['privileges'] = array_unique($params['privileges']);
                foreach ($params['privileges'] as $k => &$privilege) {
                    $privilege = (string)$privilege;
                    if (empty($privilege)) {
                        unset($params['privileges'][$k]);
                    }
                }
                $params['privileges'] = array_flip($params['privileges']);
            }

            $usergroup_id = fn_update_usergroup($params, $id, $lang_code);

            if ($usergroup_id == false) {
                return array('status' => Response::STATUS_INTERNAL_SERVER_ERROR);
            }

            return array(
                'status' => $id == null ? Response::STATUS_CREATED : Response::STATUS_OK,
                'data'   => $id == null ? array('usergroup_id' => (int)$usergroup_id) : array('message' => 'OK')
            );
        }

        return array('data' => $data, 'status' => $status);
    }


    protected function changeUserToGroupLink($group_id, $params)
    {
        $status = Response::STATUS_BAD_REQUEST;
        $data = array();

        $user_data = $this->getParentData();
        $valid_params = true;

        if (empty($params['status'])) {
            $data['message'] = __('api_required_field', array('[field]' => 'status'));
            $valid_params = false;
        }

        $group_type = db_get_field("SELECT `type` FROM ?:usergroups WHERE usergroup_id = ?i", $group_id);

        if (empty($group_type) || ($group_type == 'A' && !in_array($user_data['user_type'], array('A', 'V')))) {
            $valid_params = false;
        }

        $runtime_company_id = Registry::get('runtime.company_id');

        if ((
                ((!fn_check_user_type_admin_area($user_data) || !$user_data['user_id']) && !$runtime_company_id)
                || (fn_check_user_type_admin_area($user_data)
                    && $user_data['user_id']
                    && !$runtime_company_id
                    && $this->auth['is_root'] == 'Y'
                    && ($user_data['company_id'] != 0 || ($user_data['company_id'] == 0 && $user_data['is_root'] != 'Y'))
                )
                || ($user_data['user_type'] == 'V' && $runtime_company_id
                    && $this->auth['is_root'] == 'Y'
                    && $user_data['user_id'] != $this->auth['user_id']
                    && $user_data['company_id'] == $runtime_company_id
                )
            )
            && $valid_params
        ) {
            fn_change_usergroup_status(
                $params['status'],
                $user_data['user_id'],
                $group_id,
                fn_get_notification_rules($params)
            );
            if ($params['status'] == 'F') {
                $status = Response::STATUS_NO_CONTENT;
                $data['message'] = 'OK';
            } else {
                $status = Response::STATUS_OK;
                $data['message'] = __('status_changed');
            }
        }

        return array('status' => $status, 'data' => $data);
    }
}