<?php


namespace Tygh\Tests\Unit\Template\Snippet;

use Tygh\Template\Renderer;
use Tygh\Template\Snippet\Repository;
use Tygh\Template\Snippet\Snippet;
use Tygh\Common\OperationResult;
use Tygh\Template\Snippet\Table\Column;
use Tygh\Template\Snippet\Table\ColumnRepository;
use Tygh\Template\Snippet\Table\ColumnService;
use Tygh\Tests\Unit\ATestCase;

class ColumnServiceTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Renderer */
    protected $renderer;
    /** @var \PHPUnit_Framework_MockObject_MockObject|Repository */
    protected $repository;
    /** @var \PHPUnit_Framework_MockObject_MockObject|ColumnRepository */
    protected $column_repository;

    /** @var ColumnService */
    protected $service;

    public function setUp()
    {
        define('DESCR_SL', 'en');
        define('CART_LANGUAGE', 'en');

        $this->renderer = $this->getMockBuilder('\Tygh\Template\Renderer')
            ->setMethods(array('validate'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('\Tygh\Template\Snippet\Repository')
            ->setMethods(array('save', 'find', 'exists'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->column_repository = $this->getMockBuilder('\Tygh\Template\Snippet\Table\ColumnRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = new ColumnService($this->column_repository, $this->repository, $this->renderer);

        $this->requireMockFunction('__');

        parent::setUp();
    }

    public function testCreateColumn()
    {
        $this->repository->method('exists')->willReturnCallback(array($this, 'repositoryExists'));
        $this->renderer->method('validate')->willReturnCallback(array($this, 'rendererValidate'));

        $data = array(
            'template' => 'template',
            'snippet_type' => 'order',
            'snippet_code' => 'exists',
            'name' => 'price',
            'status' => 'A'
        );

        $result = $this->service->createColumn($data);

        /** @var Column $column */
        $column = $result->getData();
        $this->assertTrue($result->isSuccess());
        $this->assertEquals($data['template'], $column->getTemplate());
        $this->assertEquals($data['template'], $column->getDefaultTemplate());
        $this->assertEquals($data['snippet_type'], $column->getSnippetType());
        $this->assertEquals($data['snippet_code'], $column->getSnippetCode());
        $this->assertEquals($data['name'], $column->getName());
        $this->assertEquals($data['status'], $column->getStatus());
    }

    public function testUpdateColumn()
    {
        $this->repository->method('exists')->willReturnCallback(array($this, 'repositoryExists'));
        $this->renderer->method('validate')->willReturnCallback(array($this, 'rendererValidate'));

        $data = array(
            'template' => 'template',
            'snippet_type' => 'order',
            'snippet_code' => 'exists',
            'name' => 'price',
            'status' => 'A'
        );

        $column = new Column();
        $column->setId(100);
        $column->setTemplate('original_template');

        $result = $this->service->updateColumn($column, $data);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('original_template', $column->getDefaultTemplate());
        $this->assertEquals($data['template'], $column->getTemplate());
        $this->assertEquals($data['name'], $column->getName());
        $this->assertEquals($data['status'], $column->getStatus());
        $this->assertEquals($data['snippet_type'], $column->getSnippetType());
        $this->assertEquals($data['snippet_code'], $column->getSnippetCode());
    }

    public function testRemoveColumn()
    {
        $column = Column::fromArray(array(
            'column_id' => 100
        ));

        $this->column_repository->method('remove')->with($column);
        $this->service->removeColumn($column);
    }
    /**
     * @param $template
     * @param $expected_errors
     * @dataProvider dpValidateTemplate
     */
    public function testValidateTemplate($template, $expected_errors)
    {
        $this->renderer->method('validate')->willReturnCallback(array($this, 'rendererValidate'));

        $this->assertEquals($expected_errors, $this->service->validateTemplate($template));
    }

    public function dpValidateTemplate()
    {
        return array(
            array('fail', 'error_validator_message'),
            array('success', true),
        );
    }

    /**
     * @param $data
     * @param $expected_errors
     * @dataProvider dpValidateData
     */
    public function testValidateData($data, $expected_errors)
    {
        $this->repository->method('exists')->willReturnCallback(array($this, 'repositoryExists'));
        $this->renderer->method('validate')->willReturnCallback(array($this, 'rendererValidate'));

        $this->assertEquals($expected_errors, $this->service->validateData($data));
    }

    public function dpValidateData()
    {
        return array(
            array(
                array(),
                array(
                    'name' => 'error_validator_required',
                    'snippet_type' => 'error_validator_message',
                )
            ),
            array(
                array(
                    'name' => 'column',
                    'template' => 'template',
                    'snippet_type' => 'order',
                    'snippet_code' => 'exists'
                ),
                array()
            ),
        );
    }

    public function testRestoreTemplate()
    {
        $this->column_repository->expects($this->once())->method('save');

        $column = new Column();
        $column->setDefaultTemplate('default_template');
        $column->setTemplate('custom_template');

        $this->service->restoreTemplate($column);

        $this->assertEquals('default_template', $column->getTemplate());
    }

    public function rendererValidate($template)
    {
        $result = new OperationResult();

        if (strpos($template, 'fail') !== false) {
            $result->setSuccess(false);
            $result->addError('fail', 'error_validator_message');
        } else {
            $result->setSuccess(true);
        }

        return $result;
    }

    public function repositoryExists($type, $code)
    {
        if ($code == 'exists') {
            return new Snippet();
        }

        return false;
    }
}