<?php


namespace Tygh\Tests\Unit\Template\Mail;


use Tygh\Template\Mail\Repository;
use Tygh\Template\Mail\Template;
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
            ->with("SELECT * FROM ?:template_emails WHERE ?w ORDER BY code", $conditions)
            ->willReturnCallback(array($this, 'findResult'));

        $result = $repository->find($conditions);

        $this->assertCount(2, $result);

        foreach ($result as $item) {
            $this->assertInstanceOf('\Tygh\Template\Mail\Template', $item);
        }
    }

    public function testInsert()
    {
        $connection = $this->getConnection();
        $repository = new Repository($connection);

        $template = Template::fromArray(array(
            'code' => 'code',
            'area' => 'A',
            'status' => 'A',
            'subject' => 'subject',
            'default_subject' => 'default_subject',
            'template' => 'template',
            'default_template' => 'default_template',
            'created' => 1234567,
            'updated' => 1234568,
            'addon' => 'addon',
            'params_schema' => array('test' => array('type' => 'input')),
            'params' => array('test' => '10')
        ));

        $expected_data = $template->toArray(array('template_id'));
        $expected_data['params_schema'] = json_encode($template->getParamsSchema());
        $expected_data['params'] = json_encode($template->getParams());

        $connection->expects($this->once())
            ->method('query')
            ->with("INSERT INTO ?:template_emails ?e", $expected_data);

        $repository->save($template);
    }

    public function testUpdate()
    {
        $connection = $this->getConnection();
        $repository = new Repository($connection);

        $template = Template::fromArray(array(
            'template_id' => 100,
            'code' => 'code',
            'area' => 'A',
            'status' => 'A',
            'subject' => 'subject',
            'default_subject' => 'default_subject',
            'template' => 'template',
            'default_template' => 'default_template',
            'created' => 1234567,
            'updated' => 1234568,
            'params_schema' => array('test' => array('type' => 'input')),
            'params' => array('test' => '10')
        ));

        $expected_data = $template->toArray(array('template_id'));
        $expected_data['params_schema'] = json_encode($template->getParamsSchema());
        $expected_data['params'] = json_encode($template->getParams());

        $connection->expects($this->once())
            ->method('query')
            ->with("UPDATE ?:template_emails SET ?u WHERE template_id = ?i", $expected_data, $template->getId());

        $repository->save($template);
    }

    public function findResult()
    {
        return array(
            array(
                'template_id' => 100,
                'area' => 'A',
                'status' => 'A',
                'subject' => 'subject',
                'default_subject' => 'default_subject',
                'code' => 'code',
                'template' => 'template',
                'default_template' => 'default_template',
                'created' => 1234567,
                'updated' => 1234568,
            ),
            array(
                'template_id' => 120,
                'area' => 'A',
                'status' => 'A',
                'subject' => 'subject',
                'default_subject' => 'default_subject',
                'code' => 'code2',
                'template' => 'template',
                'default_template' => 'default_template',
                'created' => 1234567,
                'updated' => 1234568,
            ),
        );
    }
}