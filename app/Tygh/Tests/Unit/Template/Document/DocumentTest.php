<?php


namespace Tygh\Tests\Unit\Template\Document;


use Tygh\Template\Document\Document;

class DocumentTest extends \PHPUnit_Framework_TestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;
    
    public function testLoadFromArray()
    {
        $data = array(
            'document_id' => 100,
            'code' => 'code',
            'template' => 'template',
            'default_template' => 'default_template',
            'type' => 'order',
            'addon' => '',
            'created' => 1234567,
            'updated' => 1234568,
        );

        $document = Document::fromArray($data);

        $this->assertEquals($data, $document->toArray());
    }

    public function testDefaultTemplate()
    {
        $document = new Document();
        $document->setTemplate('template');
        $data = $document->toArray();

        $this->assertEquals($document->getTemplate(), 'template');
        $this->assertEquals($document->getDefaultTemplate(), 'template');
        $this->assertNull($data['template']);

        $document = new Document();
        $document->setDefaultTemplate('template');
        $data = $document->toArray();

        $this->assertEquals($document->getTemplate(), 'template');
        $this->assertEquals($document->getDefaultTemplate(), 'template');
        $this->assertNull($data['template']);

        $document = new Document();
        $document->setDefaultTemplate('template');
        $document->setTemplate('template2');
        $data = $document->toArray();

        $this->assertEquals($document->getTemplate(), 'template2');
        $this->assertEquals($document->getDefaultTemplate(), 'template');
        $this->assertEquals($data['template'], 'template2');
        $this->assertEquals($data['default_template'], 'template');

        $document = new Document();
        $document->setTemplate('template');
        $document->setDefaultTemplate('template2');

        $this->assertEquals($document->getTemplate(), 'template');
        $this->assertEquals($document->getDefaultTemplate(), 'template2');

        $document = new Document();
        $document->setDefaultTemplate('template1');
        $document->setDefaultTemplate('template2');
        $data = $document->toArray();

        $this->assertEquals($document->getTemplate(), 'template2');
        $this->assertEquals($document->getDefaultTemplate(), 'template2');
        $this->assertNull($data['template']);

        $document = new Document();
        $document->setTemplate('template');
        $document->setDefaultTemplate('template');
        $data = $document->toArray();

        $this->assertEquals($document->getTemplate(), 'template');
        $this->assertEquals($document->getDefaultTemplate(), 'template');
        $this->assertNull($data['template']);
    }

    public function testIsModified()
    {
        $snippet = Document::fromArray(array(
            'document_id' => 100,
            'code' => 'code',
            'template' => 'template',
            'default_template' => 'default_template',
            'type' => 'order',
            'addon' => '',
            'created' => 1234567,
            'updated' => 1234568,
        ));

        $this->assertTrue($snippet->isModified());

        $snippet->setTemplate('default_template');
        $this->assertFalse($snippet->isModified());

        $snippet->setTemplate(null);
        $this->assertFalse($snippet->isModified());

        $snippet->setTemplate('template');
        $this->assertTrue($snippet->isModified());
    }
}