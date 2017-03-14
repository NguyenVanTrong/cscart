<?php
namespace Tygh\Tests\Unit\Mailer\MessageBuilders;

use Tygh\Tests\Unit\ATestCase;

class DefaultMessageBuilderTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;
    
    public function testCreateMessage()
    {
        $builder = new DefaultMessageBuilder(array());
        $data = array(
            'to' => 'example@example.com',
            'from' => 'example@example.com',
            'body' => 'body',
            'subject' => 'subject'
        );

        $message = $builder->createMessage($data, 'C', 'en');

        $this->assertEquals($data['body'], $message->getBody());
        $this->assertEquals($data['subject'], $message->getSubject());

        // Check backward capability for subj
        $data = array(
            'to' => 'example@example.com',
            'from' => 'example@example.com',
            'body' => 'body',
            'subj' => 'subject'
        );

        $message = $builder->createMessage($data, 'C', 'en');

        $this->assertEquals($data['body'], $message->getBody());
        $this->assertEquals($data['subj'], $message->getSubject());
    }
}