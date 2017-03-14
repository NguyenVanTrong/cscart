<?php


namespace Tygh\Tests\Unit\Template;


use Tygh\Template\IContext;
use Tygh\Template\IVariable;
use Tygh\Template\Snippet\Snippet;
use Tygh\Template\VariableProxy;
use Tygh\Tests\Unit\ATestCase;

class VariableProxyTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    public function setUp()
    {
        define('DESCR_SL', 'en');
    }

    protected function getObjectFactory()
    {
        return $this->getMockBuilder('\Tygh\Template\ObjectFactory')
            ->setMethods(array('create'))
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testAccessAttribute()
    {
        $variable = new VariableProxyTestTestVariable();
        $factory = $this->getObjectFactory();
        $factory->method('create')->willReturn($variable);

        $variable_proxy = new VariableProxy(
            array('class' => '\Tygh\Tests\Unit\Template\VariableProxyTestTestVariable'),
            new VariableProxyTestContext(),
            $factory
        );

        $this->assertEquals($variable->price, $variable_proxy['price']);
        $this->assertEquals($variable->price, $variable_proxy->price);
        $this->assertEquals($variable->discount, $variable_proxy['discount']);
        $this->assertEquals($variable->getCost(), $variable_proxy['cost']);
        $this->assertEquals($variable->getAdditionalPrice(), $variable_proxy['additional_price']);
        $this->assertNull($variable_proxy['current_rate']);
        $this->assertNull($variable_proxy['current_rate2']);
        $this->assertNull($variable_proxy['cost_rate']);
        $this->assertNull($variable_proxy['internal_cost']);
    }
}

class VariableProxyTestTestVariable implements IVariable
{
    public $price = 100;
    public $discount = 150;
    private $current_rate = 0.5;

    public function getCost()
    {
        return 200;
    }

    public function getAdditionalPrice()
    {
        return 250;
    }

    protected function getInternalCost()
    {
        return 180;
    }

    private function getCostRate()
    {
        return 0.8;
    }
}

class VariableProxyTestContext implements IContext
{
    /**
     * @inheritDoc
     */
    public function getLangCode()
    {
        return 'en';
    }
}