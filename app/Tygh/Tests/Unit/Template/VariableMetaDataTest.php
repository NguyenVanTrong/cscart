<?php


namespace Tygh\Tests\Unit\Template;


use Tygh\Template\VariableMetaData;

class VariableMetaDataTest extends \PHPUnit_Framework_TestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;
    
    /**
     * @param $config
     * @param $expected
     * @dataProvider dpInit
     */
    public function testInit($config, $expected)
    {
        $meta_data = new VariableMetaData($config);

        if (isset($expected['name'])) {
            $this->assertEquals($expected['name'], $meta_data->getName());
        }

        if (isset($expected['alias'])) {
            $this->assertEquals($expected['alias'], $meta_data->getAlias());
        }

        if (isset($expected['attributes'])) {
            $this->assertEquals($expected['attributes'], $meta_data->getAttributes());
        }
    }

    public function dpInit()
    {
        return array(
            array(
                array('class' => '\Tygh\Tests\Unit\Template\VariableMetaDataTest2TestVariable', 'name' => 'fake_variable', 'alias' => 'fake_v', 'attributes' => array('attr1', 'attr2', 'attr3' => array('attr31'))),
                array('name' => 'fake_variable', 'alias' => 'fake_v', 'attributes' => array(
                    'attr1' => 'attr1',
                    'attr2' => 'attr2',
                    'attr3' => array('attr31' => 'attr31'),
                ))
            ),
            array(
                array('class' => '\Tygh\Tests\Unit\Template\VariableMetaDataTestTestVariable'),
                array('attributes' => array(
                    'attribute1' => 'attribute1', 'attribute2' => 'attribute2', 'attribute3' => 'attribute3',
                    'attribute4' => 'attribute4', 'long_attribute' => 'long_attribute'
                ))
            ),
            array(
                array('class' => '\Tygh\Tests\Unit\Template\VariableMetaDataTestTestVariable', 'attributes' => array('attribute0')),
                array('attributes' => array(
                    'attribute0' => 'attribute0',
                    'attribute1' => 'attribute1', 'attribute2' => 'attribute2', 'attribute3' => 'attribute3',
                    'attribute4' => 'attribute4', 'long_attribute' => 'long_attribute'
                ))
            )
        );
    }
}

class VariableMetaDataTestTestVariable
{
    public $attribute1;
    public $attribute2;
    protected $hidden_attribute1;
    private $hidden_attribute2;

    public function getAttribute3()
    {

    }

    public function getAttribute4()
    {

    }

    public function getLongAttribute()
    {

    }

    protected function getHiddenAttribute3()
    {

    }

    private function getHiddenAttribute4()
    {

    }
}

class VariableMetaDataTest2TestVariable
{

}