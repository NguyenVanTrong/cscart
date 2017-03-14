<?php


namespace Tygh\Tests\Unit\Template\Mail;


use Tygh\Common\OperationResult;
use Tygh\Template\Mail\Repository;
use Tygh\Template\Mail\Service;
use Tygh\Template\Mail\Template;
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
    /** @var Service */
    protected $service;

    public function setUp()
    {
        define('DESCR_SL', 'en');

        $this->renderer = $this->getMockBuilder('\Tygh\Template\Renderer')
            ->setMethods(array('validate'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('\Tygh\Template\Mail\Repository')
            ->setMethods(array('save', 'find', 'exists'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->requireMockFunction('__');

        $this->service = new Service($this->repository, $this->renderer);

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
     * @param $area
     * @param $expected
     * @dataProvider dpValidateCode
     */
    public function validateCode($code, $area, $expected)
    {
        $this->repository->method('exists')->willReturnCallback(array($this, 'repositoryExists'));

        $this->assertEquals($expected, $this->service->validateCode($code, $area));
    }

    public function dpValidateCode()
    {
        return array(
            array('', 'A', 'error_validator_required'),
            array('code 1', 'A', 'error_validator_message'),
            array('Код', 'A', 'error_validator_message'),
            array('exists', 'A', 'document_exists'),
            array('code_-Code', 'A', true),
            array('code.code', 'A', true),
        );
    }

    /**
     * @param $data
     * @param $template
     * @param $expected_errors
     * @dataProvider dpValidateData
     */
    public function testValidateData($data, $template, $expected_errors)
    {
        $this->repository->method('exists')->willReturnCallback(array($this, 'repositoryExists'));
        $this->renderer->method('validate')->willReturnCallback(array($this, 'rendererValidate'));

        $this->assertEquals($expected_errors, $this->service->validateData($data, $template));
    }

    public function dpValidateData()
    {
        $template = new Template();

        return array(
            array(
                array(),
                null,
                array(
                    'code' => 'error_validator_required',
                    'status' => 'error_validator_required'
                )
            ),
            array(
                array(
                    'code' => 'exists',
                    'area' => 'A',
                    'template' => 'success',
                    'subject' => 'success',
                ),
                $template,
                array(
                    'code' => 'email_template_exists',
                )
            ),
            array(
                array(
                    'code' => 'code',
                    'area' => 'A',
                    'template' => 'success',
                    'subject' => 'success',
                    'status' => 'A'
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
                array('template_id' => 10, 'code' => 'code', 'template' => 'template', 'default_template' => 'template', 'type' => 'type'),
                array(),
                array(),
                array('code' => 'code', 'template' => 'template', 'default_template' => 'template'),
            ),
            array(
                array('template_id' => 10, 'code' => 'code', 'template' => 'template', 'default_template' => 'template', 'type' => 'type'),
                array('template_id', 'template'),
                array(),
                array('template_id' => 10, 'template' => 'template'),
            ),
            array(
                array('template_id' => 10, 'code' => 'code', 'template' => 'template', 'default_template' => 'template', 'type' => 'type'),
                array(),
                array('code', 'template'),
                array('default_template' => 'template'),
            ),
        );
    }
    
    public function testRestoreTemplate()
    {
        $this->repository->expects($this->once())->method('save');

        $template = new Template();
        $template->setDefaultTemplate('default_template');
        $template->setTemplate('custom_template');
        $template->setDefaultSubject('default_subject');
        $template->setSubject('custom_subject');

        $this->service->restoreTemplate($template);

        $this->assertEquals('default_template', $template->getTemplate());
        $this->assertEquals('default_subject', $template->getSubject());
    }

    public function testUpdateTemplate()
    {
        $service = new Service($this->repository, $this->renderer);
        $this->repository->method('exists')->willReturnCallback(array($this, 'repositoryExists'));
        $this->renderer->method('validate')->willReturnCallback(array($this, 'rendererValidate'));
        $this->repository->method('save');

        $template = Template::fromArray(array(
            'template_id' => 100,
            'code' => 'default',
            'area' => 'A',
            'template' => 'template',
            'subject' => 'subject',
        ));

        $data = array(
            'template' => 'custom_template',
            'subject' => 'custom_subject',
        );

        $result = $service->updateTemplate($template, $data);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($data['template'], $template->getTemplate());
        $this->assertEquals('template', $template->getDefaultTemplate());
        $this->assertEquals('subject', $template->getDefaultSubject());
    }

    public function testCreateTemplate()
    {
        $service = new Service($this->repository, $this->renderer);
        $this->repository->method('exists')->willReturnCallback(array($this, 'repositoryExists'));
        $this->renderer->method('validate')->willReturnCallback(array($this, 'rendererValidate'));
        $this->repository->expects($this->once())->method('save');

        $time = time();
        $data = array(
            'status' => 'A',
            'code' => 'default',
            'area' => 'A',
            'template' => 'template',
            'subject' => 'subject',
            'addon' => 'addon',
            'params_schema' => array('test' => array('type' => 'checkbox')),
            'params' => array('test' => 'Y'),
        );

        $result = $service->createTemplate($data);
        /** @var Template $template */
        $template = $result->getData();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($data['code'], $template->getCode());
        $this->assertEquals($data['area'], $template->getArea());
        $this->assertEquals($data['template'], $template->getTemplate());
        $this->assertEquals($data['template'], $template->getDefaultTemplate());
        $this->assertEquals($data['subject'], $template->getSubject());
        $this->assertEquals($data['subject'], $template->getDefaultSubject());
        $this->assertEquals($data['params_schema'], $template->getParamsSchema());
        $this->assertEquals($data['params'], $template->getParams());
        $this->assertGreaterThanOrEqual($time, $template->getCreated());
        $this->assertGreaterThanOrEqual($time, $template->getUpdated());
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

    public function repositoryExists($area, $code)
    {
        if ($code == 'exists') {
            return new Template();
        }

        return false;
    }
}