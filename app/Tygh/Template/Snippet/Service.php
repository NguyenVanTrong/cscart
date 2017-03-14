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


namespace Tygh\Template\Snippet;


use Tygh\Exceptions\DatabaseException;
use Tygh\Template\Collection;
use Tygh\Template\IContext;
use Tygh\Template\Renderer;
use Tygh\Common\OperationResult;
use Tygh\Template\Snippet\Table\ColumnRepository;

/**
 * The service class that implements the logic of snippet template management.
 *
 * @package Tygh\Template\Snippet
 */
class Service
{
    /** @var Repository  */
    protected $repository;

    /** @var Renderer  */
    protected $renderer;

    /** @var ColumnRepository */
    protected $column_repository;

    /**
     * Service constructor.
     *
     * @param Repository            $repository         Instance of snippet repository.
     * @param Renderer              $renderer           Instance of template renderer.
     * @param ColumnRepository      $column_repository  Instance of table column repository.
     */
    public function __construct(
        Repository $repository,
        Renderer $renderer,
        ColumnRepository $column_repository
    )
    {
        $this->repository = $repository;
        $this->renderer = $renderer;
        $this->column_repository = $column_repository;
    }

    /**
     * Create snippet.
     *
     * @param array $data Snippet data.
     *
     * @return OperationResult
     */
    public function createSnippet(array $data)
    {
        $result = new OperationResult();

        $data = $this->filterData($data);
        $errors = $this->validateData($data);
        $result->setErrors($errors);

        if (empty($errors)) {
            $snippet = Snippet::fromArray($data);
            $snippet->setCreated(time());
            $snippet->setUpdated(time());

            try {
                $this->repository->save($snippet);
                $result->setSuccess(true);
                $result->setData($snippet);
            } catch (DatabaseException $e) {
                $result->addError($e->getCode(), $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Update snippet.
     *
     * @param Snippet   $snippet    Instance of snippet.
     * @param array     $data       Snippet data.
     * @param string    $lang_code  Language code.
     *
     * @return OperationResult
     */
    public function updateSnippet(Snippet $snippet, array $data, $lang_code = DESCR_SL)
    {
        $result = new OperationResult();

        $data = $this->filterData($data, array());
        $errors = $this->validateData($data, $snippet);
        $result->setErrors($errors);

        if (empty($errors)) {
            $snippet->loadFromArray($data);
            $snippet->setUpdated(time());

            try {
                $this->repository->save($snippet, $lang_code);
                $result->setSuccess(true);
                $result->setData($snippet);
            } catch (DatabaseException $e) {
                $result->addError($e->getCode(), $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Update snippet status.
     *
     * @param Snippet   $snippet    Instance of snippet.
     * @param string    $status    New snippet status.
     *
     * @return bool
     */
    public function updateSnippetStatus(Snippet $snippet, $status)
    {
        if ($this->validateStatus($status) === true) {
            if ($this->repository->updateStatus($snippet, $status)) {
                $snippet->setStatus($status);
                return true;
            }
        }

        return false;
    }

    /**
     * Update snippet status by add-on.
     *
     * @param string $addon     Add-on code.
     * @param string $status    New snippet status.
     */
    public function updateSnippetStatusByAddon($addon, $status)
    {
        $snippets = $this->repository->findByAddon($addon);

        foreach ($snippets as $snippet) {
            $this->updateSnippetStatus($snippet, $status);
        }
    }

    /**
     * Filter data.
     *
     * @param array $data           Raw snippet data.
     * @param array $safe_fields    Safe snippet fields.
     * @param array $unsafe_fields  Unsafe snippet fields.
     *
     * @return array
     */
    public function filterData(array $data, array $safe_fields = array(), array $unsafe_fields = array())
    {
        if (empty($safe_fields)) {
            $safe_fields = array('code', 'template', 'default_template', 'name', 'status', 'type', 'handler', 'params', 'addon');
        }

        $data = array_intersect_key($data, array_flip($safe_fields));

        foreach ($unsafe_fields as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * Validate snippet data.
     *
     * @param array         $data       Snippet data.
     * @param Snippet|null  $snippet    Current instance of snippet.
     *
     * @return array
     */
    public function validateData(array $data, Snippet $snippet = null)
    {
        $errors = array();

        if ($snippet == null) {
            $required_fields = array('code', 'template', 'name', 'type', 'status');
            $data += array_fill_keys($required_fields, '');
        }

        if (array_key_exists('code', $data)) {
            $errors['code'] = $this->validateCode($data['code'], $data['type'], $snippet ? $snippet->getId() : null);
        }

        if (array_key_exists('template', $data)) {
            if (empty($data['template']) && empty($data['default_template'])) {
                $errors['template'] = __('error_validator_required', array('[field]' => __('template')));
            } else {
                $errors['template'] = $this->validateTemplate($data['template']);
            }
        }

        if (array_key_exists('name', $data) && empty($data['name'])) {
            $errors['name'] = __('error_validator_required', array('[field]' => __('name')));
        }

        if (array_key_exists('status', $data)) {
            $errors['status'] = $this->validateStatus($data['status']);
        }

        return array_filter($errors, function ($val) {
            return $val !== true;
        });
    }

    /**
     * Validate snippet template.
     *
     * @param string $template Template
     *
     * @return string|true
     */
    public function validateTemplate($template)
    {
        $error = true;

        if (!empty($template)) {
            $result = $this->renderer->validate($template);

            if (!$result->isSuccess()) {
                $error = $result->getFirstError();
            }
        }

        return $error;
    }

    /**
     * Validate snippet code.
     *
     * @param string        $code       Snippet code
     * @param null|string   $type       Snippet type
     * @param null|int      $snippet_id Current snippet id
     *
     * @return string|true
     */
    public function validateCode($code, $type = null, $snippet_id = null)
    {
        $error = true;

        if (empty($code)) {
            $error = __('error_validator_required', array('[field]' => __('code')));
        } elseif (preg_match('/[^-_a-z0-9.]/i', $code)) {
            $error = __('error_validator_message', array('[field]' => __('code')));
        } elseif ($type) {
            if ($this->repository->exists($type, $code, $snippet_id ? array($snippet_id) : array())) {
                $error = __('snippet_exists');
            }
        }

        return $error;
    }

    /**
     * Validate snippet status.
     *
     * @param string $status Snippet status.
     *
     * @return string|true
     */
    public function validateStatus($status)
    {
        $error = true;

        if (empty($status)) {
            $error = __('error_validator_required', array('[field]' => __('status')));
        } elseif (!in_array($status, array(Snippet::STATUS_ACTIVE, Snippet::STATUS_DISABLE), true)) {
            $error = __('error_validator_message', array('[field]' => __('status')));
        }

        return $error;
    }

    /**
     * Remove snippet.
     *
     * @param Snippet $snippet  Instance of snippet.
     *
     * @return bool
     */
    public function removeSnippet(Snippet $snippet)
    {
        $this->repository->remove($snippet);
        $this->column_repository->removeBySnippet($snippet->getType(), $snippet->getCode());

        return true;
    }

    /**
     * Remove snippet by type and code.
     *
     * @param string $type  Snippet type.
     * @param string $code  Snippet code.
     *
     * @return bool
     */
    public function removeSnippetByTypeAndCode($type, $code)
    {
        $snippet = $this->repository->findByTypeAndCode($type, $code);

        if ($snippet) {
            return $this->removeSnippet($snippet);
        }

        return false;
    }

    /**
     * Remove snippets by type.
     *
     * @param string $type Snippet type.
     */
    public function removeSnippetByType($type)
    {
        $snippets = $this->repository->findByType($type);

        foreach ($snippets as $snippet) {
            $this->removeSnippet($snippet);
        }
    }

    /**
     * Remove snippets by add-on.
     *
     * @param string $addon Add-on name.
     */
    public function removeSnippetByAddon($addon)
    {
        $snippets = $this->repository->findByAddon($addon);

        foreach ($snippets as $snippet) {
            $this->removeSnippet($snippet);
        }
    }

    /**
     * Render snippet.
     *
     * @param Snippet       $snippet                Instance of snippet.
     * @param IContext      $context                Instance of context.
     * @param Collection    $variable_collection    Instance of variable collection.
     *
     * @return string
     */
    public function renderSnippet(Snippet $snippet, IContext $context, Collection $variable_collection)
    {
        fn_get_schema('snippets', 'handlers.functions');

        /**
         * Allows to affect the snippet rendering in some way.
         *
         * @param Snippet       $snippet                Instance of snippet.
         * @param IContext      $context                Instance of context.
         * @param Collection    $variable_collection    Instance of variable collection.
         */
        fn_set_hook('template_snippet_render_pre', $snippet, $context, $variable_collection);

        $handler = $snippet->getHandler();

        if ($handler && is_callable($handler)) {
            call_user_func_array($handler, array($snippet, $context, $variable_collection));
        }

        $result = $this->renderer->renderTemplate($snippet, $context, $variable_collection);

        /**
         * Allows to affect the results of snippet rendering.
         *
         * @param Snippet       $snippet                Instance of snippet.
         * @param IContext      $context                Instance of context.
         * @param Collection    $variable_collection    Instance of variable collection.
         * @param string        $result                 Result of render.
         */
        fn_set_hook('template_snippet_render_post', $snippet, $context, $variable_collection, $result);

        return $result;
    }

    /**
     * Render snippet by code.
     *
     * @param string        $type                   Snippet type (order, mail, etc).
     * @param string        $code                   Snippet code.
     * @param IContext      $context                Instance of parent context.
     * @param Collection    $variable_collection    Instance of variable collection.
     *
     * @return string
     */
    public function renderSnippetByTypeAndCode($type, $code, IContext $context, Collection $variable_collection)
    {
        $snippet = $this->repository->findActiveByTypeAndCode($type, $code);

        if ($snippet) {
            return $this->renderSnippet($snippet, $context, $variable_collection);
        }

        return '';
    }

    /**
     * Restore snippet template to default.
     *
     * @param Snippet $snippet
     * @return bool
     */
    public function restoreTemplate(Snippet $snippet)
    {
        $snippet->setTemplate($snippet->getDefaultTemplate());

        return $this->repository->save($snippet);
    }

    /**
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @return ColumnRepository
     */
    public function getColumnRepository()
    {
        return $this->column_repository;
    }
}