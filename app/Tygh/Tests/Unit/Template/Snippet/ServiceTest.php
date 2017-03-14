<?php


namespace Tygh\Tests\Unit\Template\Snippet;

use Tygh\Template\Renderer;
use Tygh\Template\Snippet\Repository;
use Tygh\Template\Snippet\Service;
use Tygh\Template\Snippet\Snippet;
use Tygh\Common\OperationResult;
use Tygh\Template\Snippet\Table\ColumnRepository;
use Tygh\Tests\Unit\ATestCase;

class ServiceTest extends ATestCase
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
    /** @var Service */
    protected $service;

    public function setUp()
    {
        define('CART_LANGUAGE', 'en');
        define('DESCR_SL', 'en');

        $this->renderer = $this->getMockBuilder('\Tygh\Template\Renderer')
            ->setMethods(array('validate'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('\Tygh\Template\Snippet\Repository')
            ->setMethods(array('save', 'find', 'exists', 'remove'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->column_repository = $this->getMockBuilder('\Tygh\Template\Snippet\Table\ColumnRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requireMockFunction('__');

        $this->service = new Service($this->repository, $this->renderer, $this->column_repository);

        parent::setUp();
    }

    /**
     * @param $data
     * @param $expected_data
     * @param $unsafe_fields
     * @dataProvider dpFilterData
     */
    public function testFilterData($data, $unsafe_fields, $expected_data)
    {
        $data = $this->service->filterData($data, array(), $unsafe_fields);

        $this->assertEquals($expected_data, $data);
    }

    public function dpFilterData()
    {
        return array(
            array(
                array('code' => 'code', 'created' => 145677, 'updated' => 100000, 'snippet_id' => 10),
                array(),
                array('code' => 'code'),
            ),
            array(
                array('code' => 'code', 'created' => 145677, 'updated' => 100000, 'snippet_id' => 10),
                array('code'),
                array(),
            ),
        );
    }

    /**
     * @param $code
     * @param $type
     * @param $expected_error
     * @dataProvider dpValidateCode
     */
    public function testValidateCode($code, $type, $expected_error)
    {
        $this->repository->method('exists')->willReturnCallback(array($this, 'repositoryExists'));

        $this->assertEquals($expected_error, $this->service->validateCode($code, $type));
    }

    public function dpValidateCode()
    {
        return array(
            array('', 'order', 'error_validator_required'),
            array('code', 'order', true),
            array('code.code', 'order', true),
            array('1code2-_3Code4', 'order', true),
            array('(code)', 'order', 'error_validator_message'),
            array('code code', 'order', 'error_validator_message'),
            array('Код', 'order', 'error_validator_message'),
            array('exists', 'order', 'snippet_exists'),
        );
    }

    /**
     * @param $data
     * @param $snippet
     * @param $expected_errors
     * @dataProvider dpValidateData
     */
    public function testValidateData($data, $snippet, $expected_errors)
    {
        $this->repository->method('exists')->willReturnCallback(array($this, 'repositoryExists'));
        $this->renderer->method('validate')->willReturnCallback(array($this, 'rendererValidate'));

        $this->assertEquals($expected_errors, $this->service->validateData($data, $snippet));
    }

    public function dpValidateData()
    {
        $snippet = new Snippet();

        return array(
            array(
                array(),
                null,
                array(
                    'code' => 'error_validator_required',
                    'template' => 'error_validator_required',
                    'name' => 'error_validator_required',
                    'status' => 'error_validator_required'
                )
            ),
            array(
                array(
                    'type' => 'order',
                    'code' => 'SNIPPET_code'
                ),
                $snippet,
                array()
            ),
        );
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

    public function testCreateSnippet()
    {
        $this->repository->expects($this->once())->method('save');
        $this->renderer->method('validate')->willReturnCallback(array($this, 'rendererValidate'));

        $time = time();
        $data = array(
            'code' => 'random_image',
            'template' => "<img src=\"http://lorempixel.com/200/50/\">",
            'name' => 'Рандомное изображение',
            'status' => 'A',
            'type' => 'order',
            'params' => array('test' => 'value')
        );

        $result = $this->service->createSnippet($data);

        /** @var Snippet $snippet */
        $snippet = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertGreaterThanOrEqual($time, $snippet->getCreated());
        $this->assertGreaterThanOrEqual($time, $snippet->getUpdated());

        $this->assertEquals($data['code'], $snippet->getCode());
        $this->assertEquals($data['template'], $snippet->getTemplate());
        $this->assertEquals($data['template'], $snippet->getDefaultTemplate());
        $this->assertEquals($data['name'], $snippet->getName());
        $this->assertEquals($data['status'], $snippet->getStatus());
        $this->assertEquals($data['type'], $snippet->getType());
        $this->assertEquals($data['params'], $snippet->getParams());
    }

    public function testUpdateSnippet()
    {
        $this->repository->method('save');
        $this->renderer->method('validate')->willReturnCallback(array($this, 'rendererValidate'));

        $time = time();
        $data = array(
            'code' => 'random_image',
            'template' => "<img src=\"http://lorempixel.com/200/50/\">",
            'name' => 'Рандомное изображение',
            'status' => 'A',
            'type' => 'order',
            'params' => array('test' => 'value')
        );
        $snippet = new Snippet();
        $snippet->setId(100);
        $snippet->setCreated(1345678);
        $snippet->setUpdated(1345678);
        $snippet->setTemplate('original_template');

        $result = $this->service->updateSnippet($snippet, $data);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(1345678, $snippet->getCreated());
        $this->assertGreaterThanOrEqual($time, $snippet->getUpdated());
        $this->assertEquals('original_template', $snippet->getDefaultTemplate());
        $this->assertEquals($data['code'], $snippet->getCode());
        $this->assertEquals($data['template'], $snippet->getTemplate());
        $this->assertEquals($data['name'], $snippet->getName());
        $this->assertEquals($data['status'], $snippet->getStatus());
        $this->assertEquals($data['type'], $snippet->getType());
        $this->assertEquals($data['params'], $snippet->getParams());
    }

    public function testRemoveSnippet()
    {
        $snippet = new Snippet();
        $snippet->setId(100);
        $snippet->setType('order');
        $snippet->setCode('address');

        $this->repository->expects($this->once())->method('remove')->with($snippet);
        $this->column_repository->expects($this->once())->method('removeBySnippet')->with($snippet->getType(), $snippet->getCode());

        $this->service->removeSnippet($snippet);
    }

    public function testRestoreTemplate()
    {
        $this->repository->expects($this->once())->method('save');

        $snippet = new Snippet();
        $snippet->setDefaultTemplate('default_template');
        $snippet->setTemplate('custom_template');

        $this->service->restoreTemplate($snippet);

        $this->assertEquals('default_template', $snippet->getTemplate());
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