<?php

namespace Tygh\Tests\Unit\Template\Mail;


use Tygh\Template\Mail\Template;

class TemplateTest extends \PHPUnit_Framework_TestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    public function testLoadFromArray()
    {
        $data = array(
            'template_id' => 100,
            'status' => 'A',
            'code' => 'default',
            'area' => 'A',
            'template' => 'template',
            'default_template' => 'template2',
            'subject' => 'subject',
            'default_subject' => 'subject2',
            'params_schema' => array('test' => array('type' => 'checkbox')),
            'params' => array('test' => 'Y'),
            'addon' => '',
            'created' => 1234567,
            'updated' => 1234568,
        );

        $template = Template::fromArray($data);

        $this->assertEquals($data, $template->toArray());
    }

    public function testDefaultTemplate()
    {
        $template = new Template();
        $template->setTemplate('template');
        $template->setSubject('subject');
        $data = $template->toArray();

        $this->assertEquals($template->getTemplate(), 'template');
        $this->assertEquals($template->getDefaultTemplate(), 'template');
        $this->assertEquals($template->getSubject(), 'subject');
        $this->assertEquals($template->getDefaultSubject(), 'subject');
        $this->assertNull($data['template']);
        $this->assertNull($data['subject']);

        $template = new Template();
        $template->setDefaultTemplate('template');
        $template->setDefaultSubject('subject');
        $data = $template->toArray();

        $this->assertEquals($template->getTemplate(), 'template');
        $this->assertEquals($template->getDefaultTemplate(), 'template');
        $this->assertEquals($template->getSubject(), 'subject');
        $this->assertEquals($template->getDefaultSubject(), 'subject');
        $this->assertNull($data['template']);
        $this->assertNull($data['subject']);

        $template = new Template();
        $template->setDefaultSubject('subject');
        $template->setSubject('subject2');
        $template->setDefaultTemplate('template');
        $template->setTemplate('template2');
        $data = $template->toArray();

        $this->assertEquals($template->getTemplate(), 'template2');
        $this->assertEquals($template->getDefaultTemplate(), 'template');
        $this->assertEquals($template->getSubject(), 'subject2');
        $this->assertEquals($template->getDefaultSubject(), 'subject');
        $this->assertEquals($data['template'], 'template2');
        $this->assertEquals($data['default_template'], 'template');
        $this->assertEquals($data['subject'], 'subject2');
        $this->assertEquals($data['default_subject'], 'subject');

        $template = new Template();
        $template->setTemplate('template');
        $template->setDefaultTemplate('template2');
        $template->setSubject('subject');
        $template->setDefaultSubject('subject2');

        $this->assertEquals($template->getTemplate(), 'template');
        $this->assertEquals($template->getDefaultTemplate(), 'template2');
        $this->assertEquals($template->getSubject(), 'subject');
        $this->assertEquals($template->getDefaultSubject(), 'subject2');

        $template = new Template();
        $template->setDefaultTemplate('template1');
        $template->setDefaultTemplate('template2');
        $template->setDefaultSubject('subject1');
        $template->setDefaultSubject('subject2');
        $data = $template->toArray();

        $this->assertEquals($template->getTemplate(), 'template2');
        $this->assertEquals($template->getDefaultTemplate(), 'template2');
        $this->assertEquals($template->getSubject(), 'subject2');
        $this->assertEquals($template->getDefaultSubject(), 'subject2');
        $this->assertNull($data['template']);
        $this->assertNull($data['subject']);

        $template = new Template();
        $template->setTemplate('template');
        $template->setDefaultTemplate('template');
        $template->setSubject('subject');
        $template->setDefaultSubject('subject');
        $data = $template->toArray();

        $this->assertEquals($template->getTemplate(), 'template');
        $this->assertEquals($template->getDefaultTemplate(), 'template');
        $this->assertEquals($template->getSubject(), 'subject');
        $this->assertEquals($template->getDefaultSubject(), 'subject');
        $this->assertNull($data['template']);
        $this->assertNull($data['subject']);
    }

    public function testIsModified()
    {
        $snippet = Template::fromArray(array(
            'template_id' => 100,
            'status' => 'A',
            'code' => 'default',
            'area' => 'A',
            'template' => 'template',
            'default_template' => 'default_template',
            'subject' => 'subject',
            'default_subject' => 'default_subject',
            'params_schema' => array('test' => array('type' => 'checkbox')),
            'params' => array('test' => 'Y'),
            'addon' => '',
            'created' => 1234567,
            'updated' => 1234568,
        ));

        $this->assertTrue($snippet->isModified());

        $snippet->setTemplate('default_template');
        $snippet->setSubject('default_subject');
        $this->assertFalse($snippet->isModified());

        $snippet->setTemplate(null);
        $snippet->setSubject(null);
        $this->assertFalse($snippet->isModified());

        $snippet->setTemplate('template');
        $this->assertTrue($snippet->isModified());

        $snippet->setTemplate(null);
        $snippet->setSubject('subject');
        $this->assertTrue($snippet->isModified());
    }
}