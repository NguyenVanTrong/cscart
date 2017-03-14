<?php


namespace Tygh\Tests\Unit\Template\Mail;

use Tygh\Common\OperationResult;
use Tygh\Template\Mail\Exim as MailExim;
use Tygh\Template\Mail\Template;
use Tygh\Template\Snippet\Exim as SnippetExim;
use Tygh\Template\Mail\Service as MailService;
use Tygh\Template\Snippet\Repository;
use Tygh\Tests\Unit\ATestCase;

class EximTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    /** @var  Repository|\PHPUnit_Framework_MockObject_MockObject*/
    protected $snippet_repository;
    /** @var  SnippetExim|\PHPUnit_Framework_MockObject_MockObject*/
    protected $snippet_exim;
    /** @var  MailService|\PHPUnit_Framework_MockObject_MockObject*/
    protected $mail_service;
    /** @var  Values|\PHPUnit_Framework_MockObject_MockObject*/
    protected $translation;
    /** @var  \Tygh\Template\Mail\Repository|\PHPUnit_Framework_MockObject_MockObject*/
    protected $repository;
    /** @var MailExim */
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

        $this->snippet_exim = $this->getMockBuilder('\Tygh\Template\Snippet\Exim')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mail_service = $this->getMockBuilder('\Tygh\Template\Mail\Service')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('\Tygh\Template\Mail\Repository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translation = new Values($this->getData());

        $this->snippet_exim->method('export')->willReturn(array());
        $this->mail_service->method('getRepository')->willReturn($this->repository);
        $this->repository->method('findByCodeAndArea')->willReturnCallback(array($this, 'returnRepositoryFindByCodeAndArea'));
        $this->mail_service->method('createTemplate')->willReturnCallback(array($this, 'returnServiceCreateTemplate'));
        $this->mail_service->method('updateTemplate')->willReturnCallback(array($this, 'returnServiceUpdateTemplate'));

        $this->exim = new MailExim($this->mail_service, $this->snippet_repository, $this->snippet_exim, array('en', 'ru'), $this->translation);
    }

    public function testExport()
    {
        $templates_data = $this->getData();
        $templates = array();
        $snippets = array(array('code' => 'snippet_1'));
        $expected = array('templates' => $templates_data, 'snippets' => array());

        foreach ($templates_data as $template) {
            $templates[] = Template::fromArray($template);
        }
        $this->snippet_exim->expects($this->once())->method('export')->with($snippets);
        $this->assertEquals($expected, $this->exim->export($templates, $snippets));
    }

    /**
     * @param array $template_data
     * @param Template $template
     * @dataProvider dpImportTemplate
     */
    public function testImportTemplate($template_data, $template)
    {
        if (!empty($template_data['params']['exist'])) {
            $this->mail_service->expects($this->once())->method('updateTemplate');
        } else {
            $this->mail_service->expects($this->once())->method('createTemplate');
        }

        $result = $this->exim->importTemplate($template_data);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals($template->toArray(array('snippet_id', 'updated', 'created')), $result->getData()->toArray(array('snippet_id', 'updated', 'created')));
    }

    public function dpImportTemplate()
    {
        $result = array();

        foreach ($this->getData() as $item) {
            $result[] = array(
                $item,
                Template::fromArray($item)
            );
        }

        return $result;
    }

    public function getData()
    {
        return array(
            array(
                'code' => 'create_order',
                'area' => 'A',
                'subject' => 'subject',
                'default_subject' => 'default_subject',
                'template' => 'template',
                'default_template' => 'default_template',
                'params_schema' => array('check' => array('type' => 'checkbox', 'title' => 'title')),
                'params' => array('check' => 'Y', 'exist' => true),
                'status' => 'A',
                'addon' => 'test',
                'name' => array(
                    'en' => 'create_order',
                    'ru' => 'create_order_ru',
                )
            ),
            array(
                'code' => 'create_order',
                'area' => 'C',
                'subject' => 'subject',
                'default_subject' => 'default_subject',
                'template' => 'template',
                'default_template' => 'default_template',
                'params_schema' => array(),
                'params' => array(),
                'status' => 'A',
                'addon' => 'test',
                'name' => array(
                    'en' => 'create_order',
                    'ru' => 'create_order_ru',
                )
            )
        );
    }

    public function returnRepositoryFindByCodeAndArea($code, $area)
    {
        $result = false;

        foreach ($this->getData() as $item) {
            if ($item['code'] === $code && $item['area'] === $area && !empty($item['params']['exist'])) {
                $result = Template::fromArray($item);
                break;
            }
        }

        return $result;
    }

    public function returnServiceCreateTemplate($data)
    {
        $template = Template::fromArray($data);

        $result = new OperationResult();
        $result->setSuccess(true);
        $result->setData($template);

        return $result;
    }

    public function returnServiceUpdateTemplate(Template $template, $data)
    {
        $template->loadFromArray($data);

        $result = new OperationResult();
        $result->setSuccess(true);
        $result->setData($template);

        return $result;
    }
}

class Values extends \Tygh\Languages\Values
{
    public static $data;

    public function __construct($data)
    {
        self::$data = $data;
    }

    public static function getByName($name, $lang_code = CART_LANGUAGE)
    {
        list(, $code) = explode('.', $name);
        $result = array();

        foreach (self::$data as $item) {
            if ($item['code'] === $code) {
                foreach ($item['name'] as $lang_code => $name) {
                    $result[] = array(
                        'lang_code' => $lang_code,
                        'value' => $name,
                    );
                }

                break;
            }
        }

        return $result;
    }

    public static function updateLangVar($lang_data, $lang_code = DESCR_SL, $params = array())
    {

    }
}