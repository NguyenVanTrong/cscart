<?php

class ConnectionTest extends \Tygh\Tests\Unit\ATestCase
{
    /**
     * @dataProvider buildConditionProvider
     */
    public function testBuildCondition($data, $expected)
    {
        $driver = $this->getMockBuilder('\Tygh\Backend\Database\Pdo')
            ->setMethods(array('escape'))
            ->getMock();
        $driver->method('escape')->will($this->returnCallback('addslashes'));

        $connection = new \Tygh\Database\Connection($driver);
        $this->assertEquals($expected, $connection->buildConditions($data));
    }

    public function buildConditionProvider()
    {
        return array(
            array(
                array(
                    'field1' => 100,
                    'field2' => '200',
                    'field3' => null,
                    'field4' => array(100, 'value')
                ),
                "`field1` = 100 AND `field2` = 200 AND `field3` IS NULL AND `field4` IN ('100', 'value')"
            ),
            array(
                array(
                    array("field1", ">=", 100),
                    array("field2", "<=", 200),
                    array("field3", "NULL", true),
                    array("field4", "NULL", false),
                ),
                "`field1` >= 100 AND `field2` <= 200 AND `field3` IS NULL AND `field4` IS NOT NULL"
            ),
            array(
                array(
                    array("field1", "IN", array(100, 'value')),
                    array("field2", "NOT IN", array(100, 'value')),
                    array("field3", "LIKE", 'test'),
                    array("field4", "NOT LIKE", '%test%'),
                ),
                "`field1` IN ('100', 'value') AND `field2` NOT IN ('100', 'value') AND `field3` LIKE 'test' AND `field4` NOT LIKE '%test%'"
            ),
            array(
                array(
                    'field1' => 100,
                    array('field2', '=', 200),
                    array('field3', '!=', 300),
                ),
                "`field1` = 100 AND `field2` = 200 AND `field3` != 300"
            ),
            array(
                array(
                    'table.field1' => 100,
                    array('table.field2', '=', 200),
                    array('field3', '!=', 300),
                ),
                "`table`.`field1` = 100 AND `table`.`field2` = 200 AND `field3` != 300"
            )
        );
    }

    /**
     * @dataProvider processProvider
     */
    public function testProcess($query, $data, $replace, $expected)
    {
        $driver = $this->getMockBuilder('\Tygh\Backend\Database\Pdo')
            ->setMethods(array('escape'))
            ->getMock();
        $driver->method('escape')->will($this->returnCallback('addslashes'));

        $connection = new \Tygh\Database\Connection($driver);
        $this->assertEquals($expected, $connection->process($query, $data, $replace));
    }

    public function processProvider()
    {
        return array(
            array(
                "SELECT * FROM products WHERE ?w",
                array(
                    array(
                        'product_id' => array(10, 20, 30),
                        array('product', 'LIKE', '%product%'),
                        array('price', '>=', 1000)
                    )
                ),
                true,
                "SELECT * FROM products WHERE `product_id` IN (10, 20, 30) AND `product` LIKE '%product%' AND `price` >= 1000"
            )
        );
    }
}
