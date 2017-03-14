<?php

namespace Tygh\Tests\Unit\Template\Snippet;


use Tygh\Common\OperationResult;
use Tygh\Template\Snippet\Exim;
use Tygh\Template\Snippet\Repository;
use Tygh\Template\Snippet\Service;
use Tygh\Template\Snippet\Snippet;
use Tygh\Template\Snippet\Table\Column;
use Tygh\Template\Snippet\Table\ColumnRepository;
use Tygh\Template\Snippet\Table\ColumnService;
use Tygh\Tests\Unit\ATestCase;

class EximTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    /** @var  Service|\PHPUnit_Framework_MockObject_MockObject*/
    protected $snippet_service;
    /** @var  Repository|\PHPUnit_Framework_MockObject_MockObject*/
    protected $snippet_repository;
    /** @var  ColumnService|\PHPUnit_Framework_MockObject_MockObject*/
    protected $column_service;
    /** @var  ColumnRepository|\PHPUnit_Framework_MockObject_MockObject*/
    protected $column_repository;
    /** @var Exim */
    protected $exim;

    public function setUp()
    {
        define('DESCR_SL', 'en');
        define('CART_LANGUAGE', 'en');

        $this->requireMockFunction('fn_set_hook');
        $this->requireMockFunction('fn_get_schema');
        parent::setUp();

        $this->snippet_repository = $this->getMockBuilder('\Tygh\Template\Snippet\Repository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->column_repository = $this->getMockBuilder('\Tygh\Template\Snippet\Table\ColumnRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->snippet_service = $this->getMockBuilder('\Tygh\Template\Snippet\Service')
            ->disableOriginalConstructor()
            ->getMock();

        $this->column_service = $this->getMockBuilder('\Tygh\Template\Snippet\Table\ColumnService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->exim = new Exim($this->snippet_service, $this->snippet_repository, $this->column_service, $this->column_repository);

        $this->snippet_service->method('updateSnippet')->willReturnCallback(array($this, 'snippetServiceUpdateSnippet'));
        $this->snippet_service->method('createSnippet')->willReturnCallback(array($this, 'snippetServiceCreateSnippet'));
        $this->snippet_repository->method('findByTypeAndCode')->willReturnCallback(array($this, 'snippetRepositoryFindByTypeAndCode'));
        $this->snippet_repository->method('getDescriptions')->willReturnCallback(array($this, 'snippetRepositoryGetDescriptions'));
        $this->column_repository->method('getDescriptions')->willReturnCallback(array($this, 'columnRepositoryGetDescriptions'));
        $this->column_repository->method('findBySnippet')->willReturnCallback(array($this, 'columnRepositoryFindBySnippet'));
        $this->column_repository->method('findBySnippetAndCode')->willReturnCallback(array($this, 'columnRepositoryFindBySnippetAndCode'));
        $this->column_service->method('createColumn')->willReturnCallback(array($this, 'columnServiceCreateColumn'));
        $this->column_service->method('updateColumn')->willReturnCallback(array($this, 'columnServiceUpdateColumn'));
    }

    public function testExport()
    {
        $expected_data = $this->getData();
        $snippets = array();

        foreach ($expected_data as $snippet_id => $snippet_data) {
            $snippets[] = $this->getSnippetFromData($snippet_data, $snippet_id);;
        }

        $this->assertEquals($expected_data, $this->exim->export($snippets));
    }

    /**
     * @param array $snippet_data
     * @param Snippet $snippet
     * @dataProvider dpImportSnippet
     */
    public function testImportSnippet($snippet_data, $snippet)
    {
        if (!empty($snippet_data['params']['exist'])) {
            $this->snippet_service->expects($this->once())->method('updateSnippet');
        } else {
            $this->snippet_service->expects($this->once())->method('createSnippet');
        }

        if (!empty($snippet_data['extra']['table_columns'])) {
            $this->column_repository->expects($this->at(0))->method('removeBySnippet')->with($snippet_data['type'], $snippet_data['code']);

            foreach ($snippet_data['extra']['table_columns'] as $key => $item) {
                $result = new OperationResult(true);
                $result->setData(self::getColumnFromData($item));

                if (strpos($item['code'], 'exist') !== false) {
                    $this->column_service->expects($this->at($key))->method('updateColumn')->willReturn($result);
                } else {
                    $this->column_service->expects($this->at($key))->method('createColumn')->willReturn($result);
                }
            }

            $this->column_repository->expects($this->exactly(count($snippet_data['extra']['table_columns'])))
                ->method('findBySnippetAndCode')
                ->withConsecutive($this->callback(function() use($snippet_data) {
                    $result = array();

                    foreach ($snippet_data['extra']['table_columns'] as $key => $item) {
                        $result[] = array($snippet_data['type'], $snippet_data['code'], $item['code']);
                    }

                    return $result;
                }));

            $this->column_repository->expects($this->atLeastOnce())
                ->method('updateDescription')
                ->withConsecutive($this->callback(function() use($snippet_data) {
                    $result = array();

                    foreach ($snippet_data['extra']['table_columns'] as $key => $item) {
                        $names = array_slice($item['name'], 1);

                        foreach ($names as $lang_code => $name) {
                            $result[] = array(null, $name, $lang_code);
                        }
                    }

                    return $result;
                }));
        }

        $result = $this->exim->importSnippet($snippet_data);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals($snippet->toArray(array('snippet_id', 'updated', 'created')), $result->getData()->toArray(array('snippet_id', 'updated', 'created')));
    }

    public function dpImportSnippet()
    {
        $result = array();
        $data = $this->getData();

        foreach ($data as $key => $item) {
            $snippet = $this->getSnippetFromData($item, $key);
            $result[] = array($item, $snippet);
        }

        return $result;
    }

    public function snippetRepositoryGetDescriptions($snippet_id)
    {
        $result = array();
        $data = $this->getData();

        if (isset($data[$snippet_id])) {
            $names = $data[$snippet_id]['name'];

            foreach ($names as $key => $val) {
                $result[$key] = array(
                    'name' => $val
                );
            }
        }

        return $result;
    }

    public function columnRepositoryGetDescriptions($column_id)
    {
        list($snippet_id, $column_id) = explode('909', $column_id);

        $result = array();
        $data = $this->getData();

        if (isset($data[$snippet_id]['extra']['table_columns'][$column_id])) {
            $names = $data[$snippet_id]['extra']['table_columns'][$column_id]['name'];

            foreach ($names as $key => $val) {
                $result[$key] = array(
                    'name' => $val
                );
            }
        }

        return $result;
    }

    public function columnRepositoryFindBySnippet($type, $code)
    {
        $result = array();
        $data = $this->getData();

        foreach ($data as $snippet_id => $snippet) {
            if ($snippet['type'] == $type && $snippet['code'] == $code) {

                if (isset($snippet['extra']['table_columns'])) {
                    $columns = $snippet['extra']['table_columns'];

                    foreach ($columns as $column_id => $column) {
                        $result[] = $this->getColumnFromData($column, $snippet_id . '909' . $column_id);
                    }
                }
                break;
            }
        }

        return $result;
    }

    public function snippetRepositoryFindByTypeAndCode($type, $code)
    {
        $data = $this->getData();

        foreach ($data as $key => $snippet_data) {
            if (!empty($snippet_data['params']['exist']) && $snippet_data['type'] == $type && $snippet_data['code'] == $code) {
                return $this->getSnippetFromData($snippet_data, $key);
            }
        }

        return false;
    }

    public function snippetServiceCreateSnippet($data)
    {
        $snippet = Snippet::fromArray($data);

        $result = new OperationResult();
        $result->setSuccess(true);
        $result->setData($snippet);

        return $result;
    }

    public function snippetServiceUpdateSnippet(Snippet $snippet, $data)
    {
        $snippet->loadFromArray($data);

        $result = new OperationResult();
        $result->setSuccess(true);
        $result->setData($snippet);

        return $result;
    }

    public function columnRepositoryFindBySnippetAndCode($snippet_type, $snippet_code, $code)
    {
        if (strpos($code, 'exist') == false) {
            return false;
        }

        $data = $this->getData();

        foreach ($data as $snippet_id => $snippet_data) {
            if (!empty($snippet_data['extra']['table_columns']) && $snippet_data['type'] == $snippet_type && $snippet_data['code'] == $snippet_code) {
                foreach ($snippet_data['extra']['table_columns'] as $key => $column) {
                    if ($column['code'] === $code) {
                        return $this->getColumnFromData($column, $snippet_id . '909' . $key);
                    }
                }
            }
        }

        return false;
    }

    public function columnServiceCreateColumn($data)
    {
        $column = Column::fromArray($data);

        $result = new OperationResult();
        $result->setSuccess(true);
        $result->setData($column);

        return $result;
    }

    public function columnServiceUpdateColumn(Column $column, $data)
    {
        $column->loadFromArray($data);

        $result = new OperationResult();
        $result->setSuccess(true);
        $result->setData($column);

        return $result;
    }

    private function getData()
    {
        return array(
            array(
                'code' => 'address',
                'type' => 'order_invoice',
                'template' => 'company_address_template',
                'default_template' => 'company_address_default_template',
                'status' => 'D',
                'handler' => 'handler',
                'addon' => 'addon',
                'extra' => array(),
                'params' => array('exist' => true),
                'name' => array(
                    'en' => 'address',
                    'ru'=> 'address_ru',
                ),
            ),
            array(
                'code' => 'products_table',
                'type' => 'order_invoice',
                'template' => 'products_table_template',
                'default_template' => 'products_table_default_template',
                'status' => 'A',
                'handler' => 'handler',
                'addon' => 'addon',
                'params' => array(
                    'used_table' => true,
                    'schema' => 'order_invoice_products_table'
                ),
                'name' => array(
                    'en' => 'products',
                    'ru'=> 'products_ru',
                ),
                'extra' => array(
                    'table_columns' => array(
                        array(
                            'status' => 'A',
                            'code' => 'name',
                            'position' => 10,
                            'template' => 'template',
                            'default_template' => 'default_template',
                            'addon' => 'addon',
                            'name' => array(
                                'en' => 'name',
                                'ru'=> 'name_ru',
                            )
                        ),
                        array(
                            'status' => 'A',
                            'code' => 'total',
                            'position' => 20,
                            'template' => 'template',
                            'default_template' => 'default_template',
                            'addon' => 'addon',
                            'name' => array(
                                'en' => 'total',
                                'ru'=> 'total_ru',
                            )
                        ),
                        array(
                            'status' => 'A',
                            'code' => 'exist',
                            'position' => 30,
                            'template' => 'template',
                            'default_template' => 'default_template',
                            'addon' => 'addon',
                            'name' => array(
                                'en' => 'exist',
                                'ru'=> 'exist_ru',
                            )
                        ),
                    )
                )
            )
        );
    }

    private function getSnippetFromData($snippet_data, $snippet_id = null)
    {
        if ($snippet_id !== null) {
            $snippet_data['snippet_id'] = $snippet_id;
        }

        $snippet_data['name'] = reset($snippet_data['name']);

        return Snippet::fromArray($snippet_data);
    }

    private function getColumnFromData($column_data, $column_id = null)
    {
        if ($column_id !== null) {
            $column_data['column_id'] = $column_id;
        }

        $column_data['name'] = reset($column_data['name']);

        return Column::fromArray($column_data);
    }
}