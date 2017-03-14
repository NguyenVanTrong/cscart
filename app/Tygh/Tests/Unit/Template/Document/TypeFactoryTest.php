<?php


namespace Tygh\Tests\Unit\Template\Document;


use Pimple\Container;
use Tygh\Template\Document\IType;
use Tygh\Template\Document\TypeFactory;
use Tygh\Tests\Unit\ATestCase;

class TypeFactoryTest extends ATestCase
{
    /** @var TypeFactory */
    protected $factory;

    public function setUp()
    {
        $container = new Container();
        $container['template.document.order.type'] = new TypeFactoryTestType();
        $container['template.document.packing_slip.type'] = new TypeFactoryTestType();

        $this->factory = new TypeFactory(array('order', 'packing_slip'), $container);
        parent::setUp();
    }

    public function testCreate()
    {
        $type = $this->factory->create('order');
        $this->assertInstanceOf('\Tygh\Tests\Unit\Template\Document\TypeFactoryTestType', $type);

        $type = $this->factory->create('packing_slip');
        $this->assertInstanceOf('\Tygh\Tests\Unit\Template\Document\TypeFactoryTestType', $type);
    }

    /**
     * @expectedException \Tygh\Exceptions\InputException
     */
    public function testCreateUndefined()
    {
        $this->factory->create('undefined');
    }
}

class TypeFactoryTestType implements IType
{
    public function getCode()
    {
        return 'test';
    }
}