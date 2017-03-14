<?php


namespace Tygh\Tests\Unit\Template\Document;


use Tygh\Template\Document\Document;
use Tygh\Template\Document\Repository;
use Tygh\Tests\Unit\ATestCase;

class RepositoryTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    public function setUp()
    {
        define('DESCR_SL', 'en');
        $this->requireMockFunction('fn_set_hook');
        parent::setUp();
    }

    protected function getConnection()
    {
        $connection = $this->getMockBuilder('\Tygh\Database\Connection')
            ->setMethods(array('error', 'getRow', 'query', 'getArray', 'hasError'))
            ->disableOriginalConstructor()
            ->getMock();

        return $connection;
    }

    public function testFind()
    {
        $connection = $this->getConnection();
        $repository = new Repository($connection);
        $conditions = array('code' => 'code');

        $connection->expects($this->once())
            ->method('getArray')
            ->with("SELECT * FROM ?:template_documents WHERE ?w ORDER BY type, code", $conditions)
            ->willReturnCallback(array($this, 'findResult'));

        $result = $repository->find($conditions);

        $this->assertCount(2, $result);

        foreach ($result as $item) {
            $this->assertInstanceOf('\Tygh\Template\Document\Document', $item);
        }
    }

    public function testInsert()
    {
        $connection = $this->getConnection();
        $repository = new Repository($connection);

        $document = Document::fromArray(array(
            'code' => 'code',
            'template' => 'template',
            'default_template' => 'default_template',
            'type' => 'order',
            'created' => 1234567,
            'updated' => 1234568,
            'addon' => 'addon',
        ));

        $connection->expects($this->once())
            ->method('query')
            ->with("INSERT INTO ?:template_documents ?e", $document->toArray(array('document_id')));

        $repository->save($document);
    }

    public function testUpdate()
    {
        $connection = $this->getConnection();
        $repository = new Repository($connection);

        $document = Document::fromArray(array(
            'document_id' => 100,
            'code' => 'code',
            'template' => 'template',
            'default_template' => 'default_template',
            'type' => 'order',
            'created' => 1234567,
            'updated' => 1234568,
        ));

        $connection->expects($this->once())
            ->method('query')
            ->with("UPDATE ?:template_documents SET ?u WHERE document_id = ?i", $document->toArray(array('document_id')), $document->getId());

        $repository->save($document);
    }

    public function findResult()
    {
        return array(
            array(
                'document_id' => 100,
                'code' => 'code',
                'template' => 'template',
                'default_template' => 'default_template',
                'type' => 'order',
                'created' => 1234567,
                'updated' => 1234568,
            ),
            array(
                'document_id' => 120,
                'code' => 'code2',
                'template' => 'template',
                'default_template' => 'default_template',
                'type' => 'order',
                'created' => 1234567,
                'updated' => 1234568,
            ),
        );
    }
}