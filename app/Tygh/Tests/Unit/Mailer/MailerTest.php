<?php
namespace Tygh\Tests\Unit\Mailer;


use Tygh\Mailer\IMessageBuilder;
use Tygh\Mailer\IMessageBuilderFactory;
use Tygh\Mailer\ITransport;
use Tygh\Mailer\ITransportFactory;
use Tygh\Mailer\Mailer;
use Tygh\Mailer\Message;
use Tygh\Mailer\SendResult;
use Tygh\Tests\Unit\ATestCase;

class MailerTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;
    protected $transport_factory;
    protected $message_builder_factory;

    protected function setUp()
    {
        $this->requireMockFunction('fn_set_hook');
        $this->requireMockFunction('fn_set_notification');
        $this->transport_factory = new TransportFactory();
        $this->message_builder_factory = new MessageBuilderFactory();
    }


    /**
     * @param $params
     * @param $transport_settings
     * @param $allow_db_templates
     * @param $expected
     * @dataProvider dpSend
     */
    public function testSend($params, $transport_settings, $allow_db_templates, $expected)
    {
        $mailer = new Mailer($this->message_builder_factory, $this->transport_factory, $transport_settings, $allow_db_templates, 'en');

        $this->assertEquals($expected, $mailer->send($params));
    }

    public function dpSend()
    {
        return array(
            array(
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'body' => 'body',
                    'subject' => 'subject'
                ),
                array('result' => true),
                true,
                true,
            ),
            array( //Transport not sent
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'body' => 'body',
                    'subject' => 'subject'
                ),
                array('result' => false),
                true,
                false,
            ),
            array( //undefined to address
                array(
                    'from' => 'from@example.com',
                    'body' => 'body',
                    'subject' => 'subject'
                ),
                array('result' => true),
                true,
                false,
            ),
            array( //undefined from address
                array(
                    'to' => 'to@example.com',
                    'body' => 'body',
                    'subject' => 'subject'
                ),
                array('result' => true),
                true,
                false,
            ),
            array( //empty body
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'subject' => 'subject'
                ),
                array('result' => true),
                true,
                false,
            ),
            array( //db template and empty body and disallow db templates
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'template_code' => 'example'
                ),
                array('result' => true),
                false,
                false,
            ),
            array( //db template and empty body and allow db templates
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'template_code' => 'example'
                ),
                array('result' => true),
                true,
                true,
            ),
            array( //db template and file tpl and empty body and disallow db templates
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'template_code' => 'example',
                    'tpl' => 'example.tpl'
                ),
                array('result' => true),
                false,
                true,
            ),
            array( //db template and file tpl and empty body and disallow db templates and undefined tpl
                array(
                    'to' => 'to@example.com',
                    'from' => 'from@example.com',
                    'template_code' => 'example',
                    'tpl' => 'undefined.tpl'
                ),
                array('result' => true),
                false,
                false,
            ),
        );
    }

    public function testSendSeparateTransport()
    {
        $mailer = new Mailer($this->message_builder_factory, $this->transport_factory, array('result' => false), true, 'en');

        $this->assertFalse($mailer->send(array(
            'to' => 'to@example.com',
            'from' => 'from@example.com',
            'body' => 'body',
            'subject' => 'subject'
        )));

        $this->assertTrue($mailer->send(
            array(
                'to' => 'to@example.com',
                'from' => 'from@example.com',
                'body' => 'body',
                'subject' => 'subject'
            ),
            'C', 'en', array('result' => true)
        ));
    }
}

class Transport implements ITransport
{
    public $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @inheritDoc
     */
    public function sendMessage(Message $message)
    {
        return new SendResult($this->settings['result']);
    }
}

class TransportFactory implements ITransportFactory
{
    /**
     * @inheritDoc
     */
    public function createTransport($type, $settings)
    {
        return new Transport($settings);
    }
}

class MailerMessageBuilder implements IMessageBuilder
{
    public $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * @inheritDoc
     */
    public function createMessage($params, $area, $lang_code)
    {
        $message = new Message();

        if (isset($params['from'])) {
            $message->setFrom($params['from']);
        }

        if (isset($params['to'])) {
            $message->addTo($params['to']);
        }

        if ($this->type === 'default') {
            if (isset($params['body'])) {
                $message->setBody($params['body']);
            }

            if (isset($params['subject'])) {
                $message->setSubject($params['subject']);
            }
        } elseif ($this->type === 'db_template') {
            if ($params['template_code'] === 'example') {
                $message->setBody('example1_body');
                $message->setSubject('example1_subject');
            }
        } elseif ($this->type === 'file_template') {
            if ($params['tpl'] === 'example.tpl') {
                $message->setBody('example1_body');
                $message->setSubject('example1_subject');
            }
        }


        return $message;
    }
}

class MessageBuilderFactory implements IMessageBuilderFactory
{
    /**
     * @inheritDoc
     */
    public function createBuilder($type)
    {
        return new MailerMessageBuilder($type);
    }
}