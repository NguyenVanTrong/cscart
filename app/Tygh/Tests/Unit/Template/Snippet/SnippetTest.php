<?php


namespace Tygh\Tests\Unit\Template\Snippet;


use Tygh\Template\Snippet\Snippet;

class SnippetTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadFromArray()
    {
        $snippet = new Snippet();
        $data = array(
            'snippet_id' => 1,
            'code' => 'code',
            'type' => 'type',
            'template' => 'template',
            'default_template' => 'default_template',
            'status' => 'A',
            'name' => 'name',
            'params' => array('value1'),
            'updated' => 12345678,
            'created' => 12345678,
            'handler' => '',
            'addon' => '',
        );

        $snippet->loadFromArray($data);

        $this->assertEquals($data, $snippet->toArray());
    }

    public function testDefaultTemplate()
    {
        $snippet = new Snippet();
        $snippet->setTemplate('template');
        $data = $snippet->toArray();

        $this->assertEquals($snippet->getTemplate(), 'template');
        $this->assertEquals($snippet->getDefaultTemplate(), 'template');
        $this->assertNull($data['template']);

        $snippet = new Snippet();
        $snippet->setDefaultTemplate('template');
        $data = $snippet->toArray();

        $this->assertEquals($snippet->getTemplate(), 'template');
        $this->assertEquals($snippet->getDefaultTemplate(), 'template');
        $this->assertNull($data['template']);

        $snippet = new Snippet();
        $snippet->setDefaultTemplate('template');
        $snippet->setTemplate('template2');
        $data = $snippet->toArray();

        $this->assertEquals($snippet->getTemplate(), 'template2');
        $this->assertEquals($snippet->getDefaultTemplate(), 'template');
        $this->assertEquals($data['template'], 'template2');
        $this->assertEquals($data['default_template'], 'template');

        $snippet = new Snippet();
        $snippet->setTemplate('template');
        $snippet->setDefaultTemplate('template2');

        $this->assertEquals($snippet->getTemplate(), 'template');
        $this->assertEquals($snippet->getDefaultTemplate(), 'template2');

        $snippet = new Snippet();
        $snippet->setDefaultTemplate('template1');
        $snippet->setDefaultTemplate('template2');
        $data = $snippet->toArray();

        $this->assertEquals($snippet->getTemplate(), 'template2');
        $this->assertEquals($snippet->getDefaultTemplate(), 'template2');
        $this->assertNull($data['template']);

        $snippet = new Snippet();
        $snippet->setTemplate('template');
        $snippet->setDefaultTemplate('template');
        $data = $snippet->toArray();

        $this->assertEquals($snippet->getTemplate(), 'template');
        $this->assertEquals($snippet->getDefaultTemplate(), 'template');
        $this->assertNull($data['template']);
    }

    public function testIsModified()
    {
        $snippet = Snippet::fromArray(array(
            'snippet_id' => 1,
            'code' => 'code',
            'type' => 'type',
            'template' => 'template',
            'default_template' => 'default_template',
            'status' => 'A',
            'name' => 'name',
            'params' => array('value1'),
            'updated' => 12345678,
            'created' => 12345678,
            'handler' => '',
            'addon' => '',
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