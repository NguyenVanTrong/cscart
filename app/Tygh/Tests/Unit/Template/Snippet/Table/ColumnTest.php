<?php


namespace Tygh\Tests\Unit\Template\Snippet\Table;


use Tygh\Template\Snippet\Table\Column;

class ColumnTest extends \PHPUnit_Framework_TestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;
    
    public function testLoadFromArray()
    {
        $column = new Column();
        $data = array(
            'column_id' => 100,
            'code' => 'image',
            'snippet_type' => 'order',
            'snippet_code' => 'product_table',
            'position' => 500,
            'template' => 'template',
            'default_template' => 'default_template',
            'status' => 'A',
            'addon' => '',
            'name' => 'name',
        );

        $column->loadFromArray($data);
        $this->assertEquals($data, $column->toArray());
    }

    public function testDefaultTemplate()
    {
        $column = new Column();
        $column->setTemplate('template');
        $data = $column->toArray();

        $this->assertEquals($column->getTemplate(), 'template');
        $this->assertEquals($column->getDefaultTemplate(), 'template');
        $this->assertNull($data['template']);

        $column = new Column();
        $column->setDefaultTemplate('template');
        $data = $column->toArray();

        $this->assertEquals($column->getTemplate(), 'template');
        $this->assertEquals($column->getDefaultTemplate(), 'template');
        $this->assertNull($data['template']);

        $column = new Column();
        $column->setDefaultTemplate('template');
        $column->setTemplate('template2');
        $data = $column->toArray();

        $this->assertEquals($column->getTemplate(), 'template2');
        $this->assertEquals($column->getDefaultTemplate(), 'template');
        $this->assertEquals($data['template'], 'template2');
        $this->assertEquals($data['default_template'], 'template');

        $column = new Column();
        $column->setTemplate('template');
        $column->setDefaultTemplate('template2');

        $this->assertEquals($column->getTemplate(), 'template');
        $this->assertEquals($column->getDefaultTemplate(), 'template2');

        $column = new Column();
        $column->setDefaultTemplate('template1');
        $column->setDefaultTemplate('template2');
        $data = $column->toArray();

        $this->assertEquals($column->getTemplate(), 'template2');
        $this->assertEquals($column->getDefaultTemplate(), 'template2');
        $this->assertNull($data['template']);

        $column = new Column();
        $column->setTemplate('template');
        $column->setDefaultTemplate('template');
        $data = $column->toArray();

        $this->assertEquals($column->getTemplate(), 'template');
        $this->assertEquals($column->getDefaultTemplate(), 'template');
        $this->assertNull($data['template']);
    }

    public function testIsModified()
    {
        $column = Column::fromArray(array(
            'column_id' => 100,
            'code' => 'image',
            'snippet_type' => 'order',
            'snippet_code' => 'product_table',
            'position' => 500,
            'template' => 'template',
            'default_template' => 'default_template',
            'status' => 'A',
            'addon' => '',
            'name' => 'name',
        ));

        $this->assertTrue($column->isModified());

        $column->setTemplate('default_template');
        $this->assertFalse($column->isModified());

        $column->setTemplate(null);
        $this->assertFalse($column->isModified());

        $column->setTemplate('template');
        $this->assertTrue($column->isModified());
    }
}