<?php
namespace Tygh\Tests\Unit\Mailer\Transports;

use Tygh\Mailer\Message;
use Tygh\Mailer\Transports\PhpMailerTransport;
use Tygh\Tests\Unit\ATestCase;

class PhpMailerTransportTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    protected function setUp()
    {
        $this->requireMockFunction('fn_set_hook');
    }

    /**
     * @param $data
     * @dataProvider dpCreateSmtp
     */
    public function testCreateAsSmtp($data)
    {
        $transport = new PhpMailerTransport($data);

        $this->assertEquals('smtp', $transport->Mailer);

        if ($data['mailer_smtp_auth'] === 'Y') {
            $this->assertTrue($transport->SMTPAuth);
        } else {
            $this->assertFalse($transport->SMTPAuth);
        }
        $this->assertEquals($data['mailer_smtp_host'], $transport->Host);
        $this->assertEquals($data['mailer_smtp_username'], $transport->Username);
        $this->assertEquals($data['mailer_smtp_password'], $transport->Password);
        $this->assertEquals($data['mailer_smtp_ecrypted_connection'], $transport->SMTPSecure);
    }

    public function dpCreateSmtp()
    {
        return array(
            array(array(
                'mailer_send_method' => 'smtp',
                'mailer_smtp_auth' => 'Y',
                'mailer_smtp_host' => 'example.com',
                'mailer_smtp_username' => 'smtp_user',
                'mailer_smtp_password' => 'smtp_password',
                'mailer_smtp_ecrypted_connection' => 'ssl'
            )),
            array(array(
                'mailer_send_method' => 'smtp',
                'mailer_smtp_auth' => 'N',
                'mailer_smtp_host' => 'example2.com',
                'mailer_smtp_username' => 'smtp_user2',
                'mailer_smtp_password' => 'smtp_password2',
                'mailer_smtp_ecrypted_connection' => 'tls'
            )),
        );
    }

    public function testCreateAsSendMail()
    {
        $transport = new PhpMailerTransport(array(
            'mailer_send_method' => 'sendmail',
            'mailer_sendmail_path' => 'sendmail_path'
        ));

        $this->assertEquals('sendmail', $transport->Mailer);
        $this->assertEquals('sendmail_path', $transport->Sendmail);
    }

    public function testCreateAsMail()
    {
        $transport = new PhpMailerTransport(array(
            'mailer_send_method' => 'mail',
        ));

        $this->assertEquals('mail', $transport->Mailer);
    }

    /**
     * @param $message
     * @dataProvider dpInitByMessage
     */
    public function testInitByMessage(Message $message)
    {
        $transport = new PhpMailerTransport(array());
        $transport->initByMessage($message);
        $from = $message->getFrom();
        $from_name = reset($from);
        $from_address = key($from);

        $embedded_images = array();

        foreach ($message->getEmbeddedImages() as $item) {
            $embedded_images[] = file_get_contents($item['file']);
        }

        $this->assertEquals($message->getBody(), $transport->Body);
        $this->assertEquals($message->getSubject(), $transport->Subject);
        $this->assertEquals($message->getCharset(), $transport->CharSet);
        $this->assertEquals($from_address, $transport->From);
        $this->assertEquals($from_address, $transport->Sender);
        $this->assertEquals($from_name, $transport->FromName);

        if ($message->isIsHtml()) {
            $this->assertEquals('text/html', $transport->ContentType);
        } else {
            $this->assertEquals('text/plain', $transport->ContentType);
        }

        $convertAddresses = function ($addresses) {
            $result = array();

            foreach ($addresses as $item) {
                $address = reset($item);
                $name = $item[1];

                $result[$address] = $name;
            }

            return $result;
        };

        $this->assertEquals($message->getCC(), $convertAddresses($transport->getCcAddresses()));
        $this->assertEquals($message->getBCC(), $convertAddresses($transport->getBccAddresses()));
        $this->assertEquals($message->getReplyTo(), $convertAddresses($transport->getReplyToAddresses()));

        foreach ($transport->getAttachments() as $item) {
            if ($item['6'] === 'attachment') {
                $this->assertArrayHasKey($item[0], $message->getAttachments());
            } elseif ($item['6'] === 'inline') {
                $this->assertContains($item[0], $embedded_images);
            }
        }

        $transport->initByMessage(new Message());

        $this->assertEmpty($transport->getAttachments());
        $this->assertEmpty($transport->getReplyToAddresses());
        $this->assertEmpty($transport->getBccAddresses());
        $this->assertEmpty($transport->getCcAddresses());
        $this->assertEmpty($transport->Subject);
        $this->assertEmpty($transport->Body);
        $this->assertEmpty($transport->CharSet);
    }

    public function dpInitByMessage()
    {
        $message = new Message();
        $message->setSubject('subject');
        $message->setBody('body');
        $message->setCharset('cp-1251');
        $message->setIsHtml(true);
        $message->setFrom('email@example.com', 'name');
        $message->addAttachment(__FILE__, 'attachment');
        $message->addCC('email1@example.com', 'name1');
        $message->addCC('email2@example.com', 'name2');
        $message->addBCC('email3@example.com', 'name3');
        $message->addBCC('email4@example.com', 'name4');
        $message->addReplyTo('email5@example.com', 'name5');
        $message->addReplyTo('email6@example.com', 'name6');
        $message->addEmbeddedImages(__FILE__, 'cid1', 'image/jpeg');
        $message->addEmbeddedImages(__FILE__, 'cid2', 'image/jpeg');

        return array(
            array($message)
        );
    }
}