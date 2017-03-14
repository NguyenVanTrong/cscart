<?php


namespace Tygh\Tests\Unit\Template;


use Tygh\Template\IContext;
use Tygh\Template\VariableCollectionFactory;

class VariableCollectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Tygh\Template\ObjectFactory
     */
    protected function getObjectFactory()
    {
        return $this->getMockBuilder('\Tygh\Template\ObjectFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testCreateCollection()
    {
        $context = new VariableCollectionFactoryTestContext();
        $factory = new VariableCollectionFactoryTestFactory($this->getObjectFactory());
        $collection = $factory->createCollection('documents', 'order', $context);

        $this->assertCount(2, $collection->getAll());
        $this->assertTrue($collection->contains('variable0'));
        $this->assertTrue($collection->contains('variable1'));
        $this->assertInstanceOf('\Tygh\Template\VariableProxy', $collection->get('variable0'));
        $this->assertInstanceOf('\Tygh\Template\VariableProxy', $collection->get('variable1'));
    }

    public function testCreateMetaDataCollection()
    {
        $context = new VariableCollectionFactoryTestContext();
        $factory = new VariableCollectionFactoryTestFactory($this->getObjectFactory());
        $collection = $factory->createMetaDataCollection('documents', 'order');

        $this->assertCount(2, $collection->getAll());
        $this->assertTrue($collection->contains('variable0'));
        $this->assertTrue($collection->contains('variable1'));
        $this->assertInstanceOf('\Tygh\Template\VariableMetaData', $collection->get('variable0'));
        $this->assertInstanceOf('\Tygh\Template\VariableMetaData', $collection->get('variable1'));
    }
}


class VariableCollectionFactoryTestFactory extends VariableCollectionFactory
{
    /**
     * @inheritDoc
     */
    protected function getVariablesSchema($schema_dir, $schema_name)
    {
        return array(
            'variable0' => array(
                'class' => '\Tygh\Tests\Unit\Template\VariableCollectionFactoryTestVariable',
            ),
            'variable1' => array(
                'class' => '\Tygh\Tests\Unit\Template\VariableCollectionFactoryTestVariable',
            )
        );
    }
}

class VariableCollectionFactoryTestVariable
{
    public $name;
}

class VariableCollectionFactoryTestContext implements IContext
{
    /**
     * @inheritDoc
     */
    public function getLangCode()
    {
        return 'en';
    }
}