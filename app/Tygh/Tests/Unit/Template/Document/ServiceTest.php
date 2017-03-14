<?php


namespace Tygh\Tests\Unit\Template\Document;


use Tygh\Common\OperationResult;
use Tygh\Template\Document\Document;
use Tygh\Template\Document\Repository;
use Tygh\Template\Document\Service;
use Tygh\Template\Renderer;
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

    protected $types = array('order', 'gif_certificate');

    protected $type_factory;

    /** @var Service */
    protected $service;

    public function setUp()
    {
        define('DESCR_SL', 'en');

        $this->renderer = $this->getMockBuilder('\Tygh\Template\Renderer')
            ->setMethods(array('validate'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('\Tygh\Template\Document\Repository')
            ->setMethods(array('save', 'find', 'exists'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->type_factory = $this->getMockBuilder('\Tygh\Template\Document\TypeFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requireMockFunction('__');

        $this->service = new Service($this->repository, $this->types, $this->renderer, $this->type_factory);

        parent::setUp();
    }

    /**
     * @param $template
     * @param $expected
     * @dataProvider dpValidateTemplate
     */
    public function testValidateTemplate($template, $expected)
    {
        $this->renderer->method('validate')->willReturnCallback(array($this, 'rendererValidate'));

        $this->assertEquals($expected, $this->service->validateTemplate($template));
    }

    public function dpValidateTemplate()
    {
        return array(
            array('fail', 'error_validator_message'),
            array('success', true),
        );
    }

    /**
     * @param $code
     * @param $type
     * @param $expected
     * @dataProvider dpValidateCode
     */
    public function validateCode($code, $type, $expected)
    {
        $this->repository->method('exists')->willReturnCallback(array($this, 'repositoryExists'));

        $this->assertEquals($expected, $this->service->validateCode($code, $type));
    }

    public function dpValidateCode()
    {
        return array(
            array('', 'order', 'error_validator_required'),
            array('code 1', 'order', 'error_validator_message'),
            array('Код', 'order', 'error_validator_message'),
            array('exists', 'order', 'document_exists'),
            array('code_-Code', 'order', true),
        );
    }

    /**
     * @param $data
     * @param $document
     * @param $expected_errors
     * @dataProvider dpValidateData
     */
    public function testValidateData($data, $document, $expected_errors)
    {
        $this->repository->method('exists')->willReturnCallback(array($this, 'repositoryExists'));
        $this->renderer->method('validate')->willReturnCallback(array($this, 'rendererValidate'));

        $this->assertEquals($expected_errors, $this->service->validateData($data, $document));
    }

    public function dpValidateData()
    {
        $document = new Document();

        return array(
            array(
                array(),
                null,
                array(
                    'code' => 'error_validator_required',
                )
            ),
            array(
                array(
                    'code' => 'exists',
                    'type' => 'order',
                    'template' => 'success'
                ),
                $document,
                array(
                    'code' => 'document_exists',
                )
            ),
            array(
                array(
                    'code' => 'code',
                    'type' => 'order',
                    'template' => 'success'
                ),
                null,
                array()
            ),
        );
    }

    /**
     * @param $data
     * @param $safe_fields
     * @param $unsafe_fields
     * @param $expected
     * @dataProvider dpFilterData
     */
    public function testFilterData($data, $safe_fields, $unsafe_fields, $expected)
    {
        $this->assertEquals($expected, $this->service->filterData($data, $safe_fields, $unsafe_fields));
    }

    public function dpFilterData()
    {
        return array(
            array(
                array('document_id' => 10, 'code' => 'code', 'template' => 'template', 'default_template' => 'template', 'type' => 'type'),
                array(),
                array(),
                array('code' => 'code', 'template' => 'template', 'default_template' => 'template', 'type' => 'type'),
            ),
            array(
                array('document_id' => 10, 'code' => 'code', 'template' => 'template', 'default_template' => 'template', 'type' => 'type'),
                array('document_id', 'template'),
                array(),
                array('document_id' => 10, 'template' => 'template'),
            ),
            array(
                array('document_id' => 10, 'code' => 'code', 'template' => 'template', 'default_template' => 'template', 'type' => 'type'),
                array(),
                array('code', 'template'),
                array('default_template' => 'template', 'type' => 'type'),
            ),
        );
    }
    
    public function testRestoreTemplate()
    {
        $this->repository->expects($this->once())->method('save');

        $document = new Document();
        $document->setDefaultTemplate('default_template');
        $document->setTemplate('custom_template');

        $this->service->restoreTemplate($document);

        $this->assertEquals('default_template', $document->getTemplate());
    }

    public function testUpdateDocument()
    {
        $this->repository->method('exists')->willReturnCallback(array($this, 'repositoryExists'));
        $this->renderer->method('validate')->willReturnCallback(array($this, 'rendererValidate'));
        $this->repository->method('save');

        $document = Document::fromArray(array(
            'document_id' => 100,
            'code' => 'default',
            'type' => 'order',
            'template' => 'template'
        ));

        $data = array(
            'template' => 'custom_template',
        );

        $result = $this->service->updateDocument($document, $data);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($data['template'], $document->getTemplate());
        $this->assertEquals('template', $document->getDefaultTemplate());
    }

    public function testCreateDocument()
    {
        $this->repository->method('exists')->willReturnCallback(array($this, 'repositoryExists'));
        $this->renderer->method('validate')->willReturnCallback(array($this, 'rendererValidate'));
        $this->repository->expects($this->once())->method('save');

        $time = time();
        $data = array(
            'code' => 'code',
            'type' => 'order',
            'template' => 'template'
        );

        $result = $this->service->createDocument($data);
        /** @var Document $document */
        $document = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($data['code'], $document->getCode());
        $this->assertEquals($data['type'], $document->getType());
        $this->assertEquals($data['template'], $document->getTemplate());
        $this->assertEquals($data['template'], $document->getDefaultTemplate());
        $this->assertGreaterThanOrEqual($time, $document->getCreated());
        $this->assertGreaterThanOrEqual($time, $document->getUpdated());

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
            return new Document();
        }

        return false;
    }
}