<?php


namespace Tygh\Tests\Unit\Template;


use Tygh\Template\Collection;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    public function testCollection()
    {
        $data = array(
            'key0' => 'value0',
            'key1' => 'value1',
            'key2' => 'value2'
        );
        $collection = new Collection($data);

        $this->assertCount(3, $collection->getAll());
        $this->assertEquals($data, $collection->getAll());
        $this->assertEquals('value2', $collection->get('key2'));

        $collection->add('key3', 'value3');

        $this->assertArrayHasKey('key3', $collection->getAll());
        $this->assertEquals('value3', $collection->get('key3'));
        $this->assertTrue($collection->contains('key3'));

        $collection->remove('key0');

        $this->assertCount(3, $collection->getAll());
        $this->assertFalse($collection->contains('key0'));
        $this->assertNull($collection->get('key0'));
    }
}