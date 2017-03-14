<?php


namespace Tygh\Tests\Unit\Mailer\MessageBuilders;


use Tygh\SmartyEngine\Core;
use Tygh\Tests\Unit\ATestCase;

class FileTemplateMessageBuilderTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|Core */
    protected $smarty;

    public function setUp()
    {
        define('CART_LANGUAGE', 'en');
        define('AREA', 'C');
        $this->smarty = $this->getMockBuilder('\Tygh\SmartyEngine\Core')
            ->setMethods(array('displayMail'))
            ->getMock();

        $this->smarty->method('displayMail')->willReturnCallback(array($this, 'smartyRender'));
    }

    public function smartyRender($template, $to_screen, $area, $company_id, $lang_code)
    {
        $result = '';

        switch ($template) {
            case 'example.tpl':
                $result = 'example_body';
                break;
            case 'example_subj.tpl':
                $result = 'example_subj';
                break;
        }

        return $result;
    }

    public function testCreateMessage()
    {
        $builder = new FileTemplateMessageBuilder($this->smarty, array());

        $message = $builder->createMessage(
            array(
                'to' => 'example@example.com',
                'from' => 'example@example.com',
            ),
            'C',
            'en'
        );

        $this->assertEmpty($message->getBody());
        $this->assertEmpty($message->getSubject());


        $message = $builder->createMessage(
            array(
                'to' => 'example@example.com',
                'from' => 'example@example.com',
                'tpl' => 'example.tpl',
            ),
            'C',
            'en'
        );

        $this->assertEquals('example.tpl', $message->getId());
        $this->assertEquals('example_body', $message->getBody());
        $this->assertEquals('example_subj', $message->getSubject());
    }
}