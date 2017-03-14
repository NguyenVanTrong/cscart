<?php


namespace Tygh\Tests\Unit\Template\Document;

use Tygh\Common\OperationResult;
use Tygh\Template\Document\Document;
use Tygh\Template\Document\Repository;
use Tygh\Template\Snippet\Repository as SnippetRepository;
use Tygh\Template\Document\Exim as DocumentExim;
use Tygh\Template\Snippet\Exim as SnippetExim;
use Tygh\Template\Document\Service as DocumentService;
use Tygh\Tests\Unit\ATestCase;

class EximTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    /** @var  SnippetExim|\PHPUnit_Framework_MockObject_MockObject*/
    protected $snippet_exim;
    /** @var  SnippetRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $snippet_repository;
    /** @var  DocumentService|\PHPUnit_Framework_MockObject_MockObject*/
    protected $document_service;
    /** @var  Values|\PHPUnit_Framework_MockObject_MockObject*/
    protected $translation;
    /** @var  Repository|\PHPUnit_Framework_MockObject_MockObject*/
    protected $repository;
    /** @var DocumentExim */
    protected $exim;

    public function setUp()
    {
        define('DESCR_SL', 'en');
        define('CART_LANGUAGE', 'en');
        $this->requireMockFunction('fn_set_hook');
        $this->requireMockFunction('fn_get_schema');
        parent::setUp();

        $this->snippet_exim = $this->getMockBuilder('\Tygh\Template\Snippet\Exim')
            ->disableOriginalConstructor()
            ->getMock();

        $this->document_service = $this->getMockBuilder('\Tygh\Template\Document\Service')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('\Tygh\Template\Document\Repository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->snippet_repository = $this->getMockBuilder('\Tygh\Template\Snippet\Repository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translation = new Values($this->getData());
        
        $this->document_service->method('getRepository')->willReturn($this->repository);
        $this->repository->method('findByTypeAndCode')->willReturnCallback(array($this, 'returnRepositoryFindByTypeAndCode'));
        $this->document_service->method('createDocument')->willReturnCallback(array($this, 'returnServiceCreateDocument'));
        $this->document_service->method('updateDocument')->willReturnCallback(array($this, 'returnServiceUpdateDocument'));

        $this->exim = new DocumentExim($this->document_service, $this->snippet_repository, $this->snippet_exim, array('en', 'ru'), $this->translation);
    }

    public function testExport()
    {
        $documents_data = $this->getData();
        $documents = array();

        foreach ($documents_data as $key => $item) {
            $documents[] = Document::fromArray($item);

            $this->snippet_repository->expects($this->at($key))->method('findByType')->willReturn($item['snippets']);
            $this->snippet_exim->expects($this->at($key))->method('export')->willReturn($item['snippets']);
        }

        $this->assertEquals($documents_data, $this->exim->export($documents));
    }

    /**
     * @param array $document_data
     * @param Document $document
     * @dataProvider dpImportDocument
     */
    public function testImportDocument($document_data, $document)
    {
        if ($document_data['addon'] === 'exist') {
            $this->document_service->expects($this->once())->method('updateDocument');
        } else {
            $this->document_service->expects($this->once())->method('createDocument');
        }

        $snippet_import_result = new OperationResult(true);
        $this->snippet_exim->expects($this->any())->method('importSnippet')->willReturn($snippet_import_result);

        $result = $this->exim->importDocument($document_data, 'en');
        $this->assertTrue($result->isSuccess());
        $this->assertEquals($document->toArray(array('document_id', 'updated', 'created')), $result->getData()->toArray(array('document_id', 'updated', 'created')));
    }

    public function dpImportDocument()
    {
        $result = array();

        foreach ($this->getData() as $item) {
            $result[] = array(
                $item,
                Document::fromArray($item)
            );
        }

        return $result;
    }

    public function getData()
    {
        return array(
            array(
                'code' => 'summary',
                'template' => 'template',
                'default_template' => 'default_template',
                'type' => 'order',
                'addon' => 'exist',
                'name' => array(
                    'en' => 'invoice',
                    'ru' => 'invoice_ru',
                ),
                'snippets' => array(
                    array(
                        'code' => 'address'
                    ),
                )
            ),
            array(
                'code' => 'invoice',
                'template' => 'template',
                'default_template' => 'default_template',
                'type' => 'order',
                'addon' => 'test',
                'name' => array(
                    'en' => 'invoice',
                    'ru' => 'invoice_ru',
                ),
                'snippets' => array(
                    array(
                        'code' => 'address'
                    ),
                    array(
                        'code' => 'products'
                    ),
                )
            )
        );
    }

    public function returnRepositoryFindByTypeAndCode($type, $code)
    {
        $result = false;

        foreach ($this->getData() as $item) {
            if ($item['code'] === $code && $item['type'] === $type && $item['addon'] === 'exist') {
                $result = Document::fromArray($item);
                break;
            }
        }

        return $result;
    }

    public function returnServiceCreateDocument($data)
    {
        $template = Document::fromArray($data);

        $result = new OperationResult();
        $result->setSuccess(true);
        $result->setData($template);

        return $result;
    }

    public function returnServiceUpdateDocument(Document $template, $data)
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
        list(, $code) = explode('template_document_', $name);
        list($type, $code) = explode('_', $code);

        $result = array();

        foreach (self::$data as $item) {
            if ($item['code'] === $code && $item['type'] === $type) {
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