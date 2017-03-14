<?php


namespace Tygh\Tests\Unit\Template;


use Pimple\Container;
use Tygh\Template\ObjectFactory;

class ObjectFactoryTest extends \PHPUnit_Framework_TestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;
    
    protected function getContainer()
    {
        $container = new Container(array(
            'service1' => 'service1',
            'service2' => 'service2',
            'service3' => 'service3',
        ));

        return $container;
    }

    /**
     * @param $arguments
     * @param $params
     * @param $expected_args
     * @throws \Tygh\Exceptions\ClassNotFoundException
     * @throws \Tygh\Exceptions\InputException
     * @dataProvider dpCreate
     */
    public function testCreate($arguments, $params, $expected_args)
    {
        $factory = new ObjectFactory($this->getContainer());

        /** @var \Tygh\Tests\Unit\Template\ObjectFactoryTestTestClass $object */
        $object = $factory->create('\Tygh\Tests\Unit\Template\ObjectFactoryTestTestClass', $arguments, $params);

        $this->assertEquals($expected_args, $object->args);
    }

    public function dpCreate()
    {
        return array(
            array(
                array('@service1', '#param1', '#param2'),
                array('param1' => 'param1', 'param2' => 'param2', 'param3' => 'param3'),
                array('service1', 'param1', 'param2')
            ),
            array(
                array('@service1', '#param1', '@service3'),
                array('param1' => 'param1', 'param2' => 'param2', 'param3' => 'param3'),
                array('service1', 'param1', 'service3')
            )
        );
    }
}

class ObjectFactoryTestTestClass
{
    public $args;

    public function __construct()
    {
        $this->args = func_get_args();
    }
}