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
use Tygh\BlockManager\Block;
use Tygh\BlockManager\SchemesManager;

class Blocks extends AEntity
{
    public function index($id = 0, $params = array())
    {
        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);

        if ($id) {

            $data = Block::instance()->getById($id, 0, array(), $lang_code);
            if ($data) {
                unset(
                    $data['snapping_id'],
                    $data['object_id'],
                    $data['object_type']
                );
                $status = Response::STATUS_OK;
            } else {
                $status = Response::STATUS_NOT_FOUND;
            }

        } else {

            $data = Block::instance()->getAllUnique($lang_code);
            $data = array_values($data);

            $status = Response::STATUS_OK;

        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function create($params)
    {
        $status = Response::STATUS_BAD_REQUEST;
        $data = array();

        if (empty($params['name'])) {
            $data['message'] = __('api_required_field', array(
                '[field]' => 'name'
            ));

        } elseif (empty($params['type'])) {
            $data['message'] = __('api_required_field', array(
                '[field]' => 'type'
            ));

        } else {

            $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);

            unset($params['block_id']);
            $params['apply_to_all_langs'] = 'Y';
            $params['company_id'] = $this->getCompanyId($params);
            
            if (empty($params['company_id'])) {
                $data['message'] = __('api_need_store');
            } else {

                $params['content_data']['lang_code'] = $lang_code;
                if (!empty($params['content'])) {
                    $params['content_data']['content'] = $params['content'];
                }
                
                $description = $this->prepareDescription($params, $lang_code);

                $block_id = Block::instance()->update($params, $description);

                if ($block_id) {
                    $status = Response::STATUS_CREATED;
                    $data = array(
                        'block_id' => $block_id,
                    );
                }
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

        if (empty($params['type'])) {
            $data['message'] = __('api_required_field', array(
                '[field]' => 'type'
            ));

        } else {
            
            if (Block::instance()->getById($id)) {

                $params['block_id'] = $id;
                unset($params['company_id']);
                $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);

                $params['content_data']['lang_code'] = $lang_code;
                if (!empty($params['content'])) {
                    $params['content_data']['content'] = $params['content'];
                }
                
                $description = $this->prepareDescription($params, $lang_code);

                $block_id = Block::instance()->update($params, $description);

                if ($block_id) {
                    $status = Response::STATUS_OK;
                    $data = array(
                        'block_id' => $id
                    );
                }
            }
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function delete($id)
    {
        $data = array();
        $status = Response::STATUS_NOT_FOUND;

        if (Block::instance()->remove($id)) {
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
            'create' => 'edit_blocks',
            'update' => 'edit_blocks',
            'delete' => 'edit_blocks',
            'index'  => 'edit_blocks'
        );
    }

    protected function getCompanyId($params)
    {
        if (!empty($params['company_id'])) {
            $company_id = $params['company_id'];
        } elseif (Registry::get('runtime.simple_ultimate')) {
            $company_id = Registry::get('runtime.forced_company_id');
        } else {
            $company_id = Registry::get('runtime.company_id');
        }

        return $company_id;
    }

    protected function prepareDescription($data, $lang_code = DEFAULT_LANGUAGE)
    {
        $description = array();

        if (!empty($data['description'])) {
            $description = $data['description'];
        }

        $fields = array('name');
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $description[$field] = $data[$field];
            }
        }

        $description['lang_code'] = $lang_code;

        return $description;
    }

}
