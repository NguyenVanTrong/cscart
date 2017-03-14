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


use Tygh\Common\OperationResult;
use Tygh\Exceptions\InputException;
use Tygh\ExSimpleXmlElement;
use Tygh\Template\Snippet\Table\Column;
use Tygh\Template\Snippet\Table\ColumnRepository;
use Tygh\Template\Snippet\Table\ColumnService;

/**
 * The class that implements the logic of import and export of snippet templates.
 *
 * @package Tygh\Template\Snippet
 */
class Exim
{
    /** @var Service */
    protected $service;

    /** @var ColumnService */
    protected $column_service;

    /** @var Repository */
    protected $repository;

    /** @var ColumnRepository */
    protected $column_repository;

    protected $default_snippet_data;
    protected $default_column_data;
    protected $handlers_included = false;

    /**
     * Snippet export/import constructor.
     *
     * @param Service               $service            Instance of snippets service.
     * @param ColumnService         $column_service     Instance of table columns service.
     * @param Repository            $repository         Instance of snippet repository.
     * @param ColumnRepository      $column_repository  Instance of table column repository.
     */
    public function __construct(Service $service, Repository $repository, ColumnService $column_service, ColumnRepository $column_repository)
    {
        $this->service = $service;
        $this->column_service = $column_service;
        $this->repository = $repository;
        $this->column_repository = $column_repository;
    }

    /**
     * Import snippets from data.
     *
     * @param array     $snippets   List of snippet data.
     * @param string    $lang_code  Language code.
     *
     * @return OperationResult
     */
    public function import(array $snippets, $lang_code = DESCR_SL)
    {
        $result = new OperationResult();
        $counter = array(
            'success_snippets' => 0,
            'fail_snippets' => 0
        );

        foreach ($snippets as $key => $item) {
            $import_result = $this->importSnippet($item, $lang_code);

            foreach ($import_result->getErrors() as $code => $error) {
                $result->addError('snippet_' . $key . '_' . $code, "Snippet #{$key}: " . $error);
            }

            if ($import_result->isSuccess()) {
                $counter['success_snippets']++;
            } else {
                $counter['fail_snippets']++;
            }
        }

        $result->setData($counter);

        return $result;
    }

    /**
     * Import snippet from data.
     *
     * @param array     $data       Snippet data.
     * @param string    $lang_code  Language code.
     *
     * @return OperationResult
     */
    public function importSnippet(array $data, $lang_code = DESCR_SL)
    {
        list($data, $descriptions) = $this->prepareSnippetData($data, $lang_code);

        $snippet = $this->repository->findByTypeAndCode($data['type'], $data['code'], $lang_code);

        if ($snippet) {
            $result = $this->service->updateSnippet($snippet, $data, $lang_code);
        } else {
            $result = $this->service->createSnippet($data);
            $snippet = $result->getData();
        }

        if ($result->isSuccess()) {
            foreach ($descriptions as $descr_lang_code => $items) {
                $this->repository->updateDescription($snippet->getId(), $items, $descr_lang_code);
            }

            if (!empty($data['extra'])) {
                $this->includeSnippetHandlers();

                foreach ($data['extra'] as $type => $extra_data) {
                    if ($type === 'table_columns') {
                        $this->column_repository->removeBySnippet($snippet->getType(), $snippet->getCode());

                        foreach ($extra_data as $key => $column) {
                            $column['snippet_type'] = $snippet->getType();
                            $column['snippet_code'] = $snippet->getCode();

                            $extra_result = $this->importTableColumn($column, $lang_code);

                            foreach ($extra_result->getErrors() as $code => $error) {
                                $result->addError('column_' . $key . '_' . $code, "Table column #{$key}: " . $error);
                            }
                        }
                    } else {
                        $function_name = 'fn_snippet_import_extra_' . $type;

                        if (function_exists($function_name)) {
                            $extra_result = call_user_func_array($function_name, array($snippet, $extra_data, $lang_code));

                            if ($extra_result instanceof OperationResult) {
                                foreach ($extra_result->getErrors() as $code => $error) {
                                    $result->addError('extra_' . $type . '_' . $code, "{$type}: " . $error);
                                }
                            }
                        }
                    }
                }
            }

            /**
             * Allows to perform additional actions after importing a snippet.
             *
             * @param self                  $this       Instance of snippet exim service.
             * @param array                 $data       Imported snippet data.
             * @param Snippet               $snippet    Instance of snippet.
             * @param OperationResult       $result     Export result.
             */
            fn_set_hook('template_snippet_import', $this, $data, $snippet, $result);
        }

        return $result;
    }

    /**
     * Import table column from data.
     *
     * @param array     $data       Table column data.
     * @param string    $lang_code  Language code.
     *
     * @return OperationResult
     */
    public function importTableColumn(array $data, $lang_code = DESCR_SL)
    {
        list($data, $descriptions) = $this->prepareColumnData($data, $lang_code);

        $column = $this->column_repository->findBySnippetAndCode($data['snippet_type'], $data['snippet_code'], $data['code']);

        if ($column) {
            $result = $this->column_service->updateColumn($column, $data, $lang_code);
        } else {
            $result = $this->column_service->createColumn($data);
            $column = $result->getData();
        }

        if ($result->isSuccess()) {
            foreach ($descriptions as $descr_lang_code => $items) {
                $this->column_repository->updateDescription($column->getId(), $items, $descr_lang_code);
            }
        }

        return $result;
    }

    /**
     * Export snippets to data.
     *
     * @param Snippet[] $snippets       List of snippet instances.
     * @param array     $exclude_fields List of snippet field to exclude from export data.
     *
     * @return array
     */
    public function export($snippets, array $exclude_fields = array())
    {
        $result = array();

        foreach ($snippets as $snippet) {
            $result[] = $this->exportSnippet($snippet, $exclude_fields);
        }

        return $result;
    }

    /**
     * Export snippets to xml.
     *
     * @param Snippet[] $snippets       List of snippet instances.
     * @param array     $exclude_fields List of snippet field to exclude from export data.
     *
     * @throws InputException
     * @return string
     */
    public function exportToXml($snippets, array $exclude_fields = array())
    {
        if (!class_exists('\SimpleXMLElement')) {
            throw new InputException("Class 'SimpleXMLElement' not found ");
        }

        $xml_root = new ExSimpleXmlElement('<snippets></snippets>');
        $xml_root->addAttribute('scheme', '1.0');

        $result = $this->export($snippets);

        foreach ($result as $item) {
            $xml_root->addChildFromArray(array('snippet' => $item));
        }

        return $xml_root->toString();
    }

    /**
     * Export snippet to data.
     *
     * @param Snippet $snippet          Instance of snippet.
     * @param array   $exclude_fields   List of snippet field to exclude from export data.
     *
     * @return array
     */
    public function exportSnippet(Snippet $snippet, array $exclude_fields = array())
    {
        $descriptions = $this->repository->getDescriptions($snippet->getId());
        $result = $snippet->toArray(array_merge($exclude_fields, array('snippet_id', 'created', 'updated')));
        $result['extra'] = $result['name'] = array();

        foreach ($descriptions as $lang_code => $item) {
            $result['name'][$lang_code] = $item['name'];
        }

        if ($snippet->getParam('used_table', false)) {
            $result['extra']['table_columns'] = array();

            $columns = $this->column_repository->findBySnippet($snippet->getType(), $snippet->getCode());

            foreach ($columns as $column) {
                $column_descriptions = $this->column_repository->getDescriptions($column->getId());
                $column_data = $column->toArray(array('column_id', 'snippet_type', 'snippet_code'));
                $column_data['name'] = array();

                foreach ($column_descriptions as $lang_code => $item) {
                    $column_data['name'][$lang_code] = $item['name'];
                }

                $result['extra']['table_columns'][] = $column_data;
            }
        }

        /**
         * Allows to change snippet data before the export.
         *
         * @param self      $this       Instance of snippet exim service.
         * @param Snippet   $snippet    Instance of snippet.
         * @param array     $result     Export result.
         */
        fn_set_hook('template_snippet_export', $this, $snippet, $result);

        return $result;
    }

    /**
     * Prepare improted data.
     *
     * @param array     $data
     * @param string    $lang_code
     *
     * @return array
     */
    protected function prepareColumnData(array $data, $lang_code)
    {
        if ($this->default_column_data === null) {
            $column = new Column();
            $this->default_column_data = $column->toArray();
        }

        $data = array_filter($data) + $this->default_column_data;
        $descriptions = array();

        if (is_array($data['name'])) {
            $names = $data['name'];

            if (isset($names[$lang_code])) {
                $name = $names[$lang_code];
                unset($names[$lang_code]);
            } else {
                $name = array_shift($names);
            }

            $data['name'] = $name;

            foreach ($names as $lang_code => $name) {
                $descriptions[$lang_code]['name'] = $name;
            }
        }

        return array($data, $descriptions);
    }

    /**
     * Prepare improted data.
     *
     * @param array     $data
     * @param string    $lang_code
     *
     * @return array
     */
    protected function prepareSnippetData(array $data, $lang_code)
    {
        if ($this->default_snippet_data === null) {
            $snippet = new Snippet();
            $this->default_snippet_data = $snippet->toArray();
        }

        $data = array_filter($data) + $this->default_snippet_data;
        $descriptions = array();

        if (is_array($data['name'])) {
            $names = $data['name'];

            if (isset($names[$lang_code])) {
                $name = $names[$lang_code];
                unset($names[$lang_code]);
            } else {
                $name = array_shift($names);
            }

            $data['name'] = $name;

            foreach ($names as $lang_code => $name) {
                $descriptions[$lang_code]['name'] = $name;
            }
        }

        return array($data, $descriptions);
    }

    /**
     * Including snippet handlers.
     */
    protected function includeSnippetHandlers()
    {
        if (!$this->handlers_included) {
            $this->handlers_included = true;
            fn_get_schema('snippets', 'handlers.functions');
        }
    }
}