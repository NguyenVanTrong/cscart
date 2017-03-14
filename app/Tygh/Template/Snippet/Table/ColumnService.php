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

namespace Tygh\Template\Snippet\Table;
use Tygh\Common\OperationResult;
use Tygh\Exceptions\DatabaseException;
use Tygh\Template\Renderer;
use Tygh\Template\Snippet\Repository;

/**
 * The service class that implements the logic of table column management.
 *
 * @package Tygh\Template\Snippet\Table
 */
class ColumnService
{
    /** @var ColumnRepository */
    protected $repository;

    /** @var Repository */
    protected $snippet_repository;

    /** @var Renderer */
    protected $renderer;

    /**
     * ColumnService constructor.
     *
     * @param ColumnRepository  $repository         Instance of column repository.
     * @param Repository        $snippet_repository Instance of snippet repository.
     * @param Renderer          $renderer           Instance of renderer.
     */
    public function __construct(ColumnRepository $repository, Repository $snippet_repository, Renderer $renderer)
    {
        $this->repository = $repository;
        $this->renderer = $renderer;
        $this->snippet_repository = $snippet_repository;
    }

    /**
     * Create column.
     *
     * @param array $data Column data.
     *
     * @return OperationResult
     */
    public function createColumn(array $data)
    {
        $result = new OperationResult();
        $data += array(
            'status' => 'A',
            'position' => 100,
            'template' => ''
        );

        $errors = $this->validateData($data);
        $result->setErrors($errors);

        if (empty($errors)) {
            $column = Column::fromArray($data);

            try {
                $this->repository->save($column);
                $result->setSuccess(true);
                $result->setData($column);
            } catch (DatabaseException $e) {
                $result->addError($e->getCode(), $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Update column.
     *
     * @param Column    $column     Instance of column.
     * @param array     $data       Column data.
     * @param string    $lang_code  Language code.
     *
     * @return OperationResult
     */
    public function updateColumn(Column $column, array $data, $lang_code = DESCR_SL)
    {
        $result = new OperationResult();
        $errors = $this->validateData($data, $column);

        $result->setErrors($errors);

        if (empty($errors)) {
            $column->loadFromArray($data);

            try {
                $this->repository->save($column, $lang_code);
                $result->setSuccess(true);
                $result->setData($column);
            } catch (DatabaseException $e) {
                $result->addError($e->getCode(), $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Remove column.
     *
     * @param Column $column Instance of column.
     */
    public function removeColumn(Column $column)
    {
        $this->repository->remove($column);
    }

    /**
     * Validate column data.
     *
     * @param array  $data      Column data.
     * @param Column $column    Updated column.
     *
     * @return array
     */
    public function validateData(array $data, $column = null)
    {
        if ($column === null) {
            $required_fields = array('template', 'name', 'snippet_type', 'snippet_code');
            $data += array_fill_keys($required_fields, '');
        }

        $errors = array();

        if (array_key_exists('template', $data)) {
            $errors['template'] = $this->validateTemplate($data['template']);
        }

        if (array_key_exists('name', $data) && empty($data['name'])) {
            $errors['name'] = __('error_validator_required', array('[field]' => __('name')));
        }

        if (array_key_exists('status', $data)) {
            $errors['status'] = $this->validateStatus($data['status']);
        }

        if (array_key_exists('snippet_type', $data) && array_key_exists('snippet_code', $data)) {
            if (!$this->snippet_repository->exists($data['snippet_type'], $data['snippet_code'])) {
                $errors['snippet_type'] = __('error_validator_message', array('[field]' => __('snippet')));
            }
        }

        return array_filter($errors, function ($val) {
            return $val !== true;
        });
    }

    /**
     * Validate column template.
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
     * Validate column status.
     *
     * @param string $status Column status.
     *
     * @return string|true
     */
    public function validateStatus($status)
    {
        $error = true;

        if (empty($status)) {
            $error = __('error_validator_required', array('[field]' => __('status')));
        } elseif (!in_array($status, array('A', 'D'), true)) {
            $error = __('error_validator_message', array('[field]' => __('status')));
        }

        return $error;
    }

    /**
     * Restore column template to default.
     *
     * @param Column $column
     * @return bool
     */
    public function restoreTemplate(Column $column)
    {
        $column->setTemplate($column->getDefaultTemplate());

        return $this->repository->save($column);
    }
}