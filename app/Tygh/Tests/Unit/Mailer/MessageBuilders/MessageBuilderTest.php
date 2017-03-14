<?php


namespace Tygh\Tests\Unit\Mailer\MessageBuilders;


use Tygh\Mailer\AMessageBuilder;
use Tygh\Mailer\Message;
use Tygh\Tests\Unit\ATestCase;

class MessageBuilderTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    /**
     * @param array $data
     * @return \PHPUnit_Framework_MockObject_MockObject|AMessageBuilder
     */
    public function getMessageBuilder($data = array())
    {
        return new MessageBuilder($data);
    }

    public function testGetMessageFrom()
    {
        $message_builder = $this->getMessageBuilder();

        $this->assertEquals(
            array('company_users_department@example.com' => 'Simtech'),
            $message_builder->getMessageFrom('company_users_department', 0, 'en')
        );

        $this->assertEquals(
            array('company_newsletter_email1@example.com' => 'Simtech1'),
            $message_builder->getMessageFrom('company_newsletter_email', 1, 'en')
        );

        $this->assertEquals(
            array('default_company_orders_department@example.com' => 'Default Simtech'),
            $message_builder->getMessageFrom('default_company_orders_department', 0, 'en')
        );

        $this->assertEquals(
            array('any@mail.com' => ''),
            $message_builder->getMessageFrom('any@mail.com', 0, 'en')
        );

        $this->assertEquals(
            false,
            $message_builder->getMessageFrom('mail.com', 0, 'en')
        );

        $this->assertEquals(
            array('default_company_orders_department@example.com' => 'Default Simtech'),
            $message_builder->getMessageFrom(array('email' => 'default_company_orders_department'), 0, 'en')
        );

        $this->assertEquals(
            array('default_company_orders_department@example.com' => 'Custom name'),
            $message_builder->getMessageFrom(array('email' => 'default_company_orders_department', 'name' => 'Custom name'), 0, 'en')
        );

        $this->assertEquals(
            array('example@email.com' => ''),
            $message_builder->getMessageFrom(array('email' => 'example@email.com'), 0, 'en')
        );

        $this->assertEquals(
            array('example@email.com' => 'Custom name'),
            $message_builder->getMessageFrom(array('email' => 'example@email.com', 'name' => 'Custom name'), 0, 'en')
        );

        $this->assertEquals(
            array('example@email.com' => 'Custom name'),
            $message_builder->getMessageFrom(array('email' => 'example@email.com;example2@email.com', 'name' => 'Custom name'), 0, 'en')
        );

        $this->assertEquals(
            array('default_company_orders_department@example.com' => 'Default Simtech'),
            $message_builder->getMessageFrom(array('email' => 'default_company_orders_department', 'name' => 'default_company_name'), 0, 'en')
        );
    }

    public function testNormalizeEmails()
    {
        $builder = $this->getMessageBuilder();

        $this->assertEquals(
            array('email1@domain1.com', 'email2@domain2.com'),
            $builder->normalizeEmails('email1@domain1.com,email2@domain2.com')
        );
        $this->assertEquals(
            array('email1@domain1.com', 'email2@domain2.com'),
            $builder->normalizeEmails('email1@domain1.com;email2@domain2.com')
        );
        $this->assertEquals(
            array('email1@domain1.com', 'email2@domain2.com'),
            $builder->normalizeEmails('email1@domain1.com;email2@domain2.com,email2@domain2.com')
        );
    }

    public function testGettingAddresses()
    {
        $message_builder = $this->getMessageBuilder();

        $this->assertEquals(
            array('company_users_department@example.com'),
            $message_builder->getMessageTo('company_users_department', 0, 'en')
        );
        $this->assertEquals(
            array('company_users_department@example.com', 'company_orders_department@example.com'),
            $message_builder->getMessageTo(array('company_users_department', 'company_orders_department'), 0, 'en')
        );
        $this->assertEquals(
            array('example@email.com'),
            $message_builder->getMessageTo('example@email.com', 0, 'en')
        );

        $this->assertEquals(
            array('company_users_department@example.com'),
            $message_builder->getMessageCC('company_users_department', 0, 'en')
        );
        $this->assertEquals(
            array('company_users_department@example.com', 'company_orders_department@example.com'),
            $message_builder->getMessageCC(array('company_users_department', 'company_orders_department'), 0, 'en')
        );
        $this->assertEquals(
            array('example@email.com'),
            $message_builder->getMessageCC('example@email.com', 0, 'en')
        );

        $this->assertEquals(
            array('company_users_department@example.com'),
            $message_builder->getMessageBCC('company_users_department', 0, 'en')
        );
        $this->assertEquals(
            array('company_users_department@example.com', 'company_orders_department@example.com'),
            $message_builder->getMessageBCC(array('company_users_department', 'company_orders_department'), 0, 'en')
        );
        $this->assertEquals(
            array('example@email.com'),
            $message_builder->getMessageBCC('example@email.com', 0, 'en')
        );

        $this->assertEquals(
            array('company_users_department@example.com'),
            $message_builder->getMessageReplyTo('company_users_department', 0, 'en')
        );
        $this->assertEquals(
            array('company_users_department@example.com', 'company_orders_department@example.com'),
            $message_builder->getMessageReplyTo(array('company_users_department', 'company_orders_department'), 0, 'en')
        );
        $this->assertEquals(
            array('example@email.com'),
            $message_builder->getMessageReplyTo('example@email.com', 0, 'en')
        );

        $this->assertEquals(
            array('company_users_department@example.com'),
            $message_builder->getMessageTo('company_users_department', 0, 'en')
        );
        $this->assertEquals(
            array('company_users_department@example.com', 'company_orders_department@example.com'),
            $message_builder->getMessageTo(array('company_users_department', 'company_orders_department'), 0, 'en')
        );
        $this->assertEquals(
            array('example@email.com'),
            $message_builder->getMessageTo('example@email.com', 0, 'en')
        );
    }

    /**
     * @param $body
     * @param $files
     * @dataProvider dpRetrieveEmbeddedImages
     */
    public function testRetrieveEmbeddedImages($body, $files)
    {
        $builder = $this->getMessageBuilder(array(
            'http_location' => 'http://http_location.loc',
            'https_location' => 'https://https_location.loc',
            'http_path' => '/http_path',
            'https_path' => '/https_path',
            'dir' => array(
                'root' => __DIR__
            )
        ));

        $message = new Message();
        $message->setBody($body);

        $builder->retrieveEmbeddedImages($message);

        if (!empty($files)) {
            $this->assertNotEmpty($message->getEmbeddedImages());
            foreach ($message->getEmbeddedImages() as $item) {
                $this->assertContains($item['file'], $files);
                $this->assertContains("cid:{$item['cid']}", $message->getBody());
            }
        } else {
            $this->assertEmpty($message->getEmbeddedImages());
        }
    }

    public function dpRetrieveEmbeddedImages()
    {
        return array(
            array(
                'simple body',
                array()
            ),
            array(
                '<div style="background: transparent url(\'images/image.jpeg\')">Background image</div>'
                . 'Tag image: <img src="images/path/detailed.jpeg">',
                array(
                    'http://http_location.loc/images/image.jpeg',
                    'http://http_location.loc/images/path/detailed.jpeg'
                )
            ),
            array(
                '<div style="background: transparent url(\'http://http_location.loc/http_path/images/image.jpeg\')">Background image</div>'
                . 'Tag image: <img src="https://https_location.loc/https_path/images/path/detailed.jpeg">',
                array(
                    __DIR__ . '/http_path/images/image.jpeg',
                    __DIR__ . '/https_path/images/path/detailed.jpeg'
                )
            ),
            array(
                '<div style="background: transparent url(\'/http_path/images/image.jpeg\')">Background image</div>'
                . 'Tag image: <img src="/https_path/images/path/detailed.jpeg">',
                array(
                    __DIR__ . '/images/image.jpeg',
                    __DIR__ . '/images/path/detailed.jpeg'
                )
            ),
        );
    }

    /**
     * @param $params
     * @param $expected
     * @dataProvider dpCreateMessage
     */
    public function testCreateMessage($params, $expected)
    {
        $builder = new MessageBuilder(array());
        $message = $builder->createMessage($params, 'C', 'en');

        foreach ($expected as $key => $value) {
            switch ($key) {
                case 'from':
                    $this->assertEquals($value, $message->getFrom());
                    break;
                case 'is_html':
                    $this->assertEquals($value, $message->isIsHtml());
                    break;
            }
        }
    }

    public function dpCreateMessage()
    {
        return array(
            array(
                array(
                    'to' => 'to@example.com',
                    'from' => 'test_empty_email',
                    'is_html' => true,
                    'company_id' => 0
                ),
                array(
                    'from' => array('test_empty_email@example.com' => 'Simtech1'), //If email from default company empty, should be get email from first company
                    'is_html' => true
                ),
            )
        );
    }
}