<?php

namespace Tygh\Tests\Unit\Tools;

class MathTest extends \Tygh\Tests\Unit\ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    /**
     * @param float $x        Floored value
     * @param int   $expected Expected value
     * @dataProvider getFloorValues
     */
    public function testFloor($x, $expected)
    {
        $actual = \Tygh\Tools\Math::floor($x);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param float $x         Floored value
     * @param float $precision Precision
     * @param int   $expected  Expected value
     * @dataProvider getFloorToPrecisionValues
     */
    public function testFloorToPrecision($x, $precision, $expected)
    {
        $actual = \Tygh\Tools\Math::floorToPrecision($x, $precision);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param float $x         Ceiled value
     * @param float $precision Precision
     * @param int   $expected  Expected value
     * @dataProvider getCeilToPrecisionValues
     */
    public function testCeilToPrecision($x, $precision, $expected)
    {
        $actual = \Tygh\Tools\Math::ceilToPrecision($x, $precision);
        $this->assertEquals($expected, $actual);
    }

    public function getFloorValues()
    {
        return array(
            array(-3.1, -4),
            array(-3.0, -3),
            array(-2.4, -3),
            array(-2, -2),
            array(-1.5, -2),
            array(-1, -1),
            array(-0.5, -1),
            array(0, 0),
            array(0.5, 0),
            array(1, 1),
            array(1.5, 1),
            array(2, 2),
            array(2.4, 2),
            array(3.0, 3),
            array(3.1, 3)
        );
    }

    public function getFloorToPrecisionValues()
    {
        return array(
            array(-3.12345, 100, -100),
            array(-3.12345, 10, -10),
            array(-3.12345, 1, -4),
            array(-3.12345, 0.1, -3.2),
            array(-3.12345, 0.01, -3.13),
            array(-3.12345, 0.001, -3.124),
            array(1, 1, 1),
            array(0, 1, 0),
            array(1.1, 0.1, 1.1),
            array(1.01, 0.01, 1.01),
            array(1.01, 0.1, 1.0),
            array(1.05, 0.1, 1.0),
            array(1.09, 0.1, 1.0),
            array(11.28, 0.1, 11.2),
            array(11.25, 0.1, 11.2),
            array(11.21, 0.1, 11.2),
            array(11.28, 0.01, 11.28),
            array(11.25, 0.01, 11.25),
            array(11.21, 0.01, 11.21),
            array(11.228, 0.01, 11.22),
            array(11.225, 0.01, 11.22),
            array(11.221, 0.01, 11.22),
            array(111, 10, 110),
            array(115, 10, 110),
            array(119, 10, 110),
            array(111, 100, 100),
            array(150, 100, 100),
            array(190, 100, 100),
            array(2.01, 0.01, 2.01),
            array(222, 0.01, 222.0),
            array(222, 0.1, 222.0),
            array(222, 1, 222.0),
        );
    }

    public function getCeilToPrecisionValues()
    {
        return array(
            array(-3.12345, 100, 0),
            array(-3.12345, 10, 0),
            array(-3.12345, 1, -3),
            array(-3.12345, 0.1, -3.1),
            array(-3.12345, 0.01, -3.12),
            array(-3.12345, 0.001, -3.123),
            array(1, 1, 1),
            array(0, 1, 0),
            array(1.1, 0.1, 1.1),
            array(1.01, 0.01, 1.01),
            array(1.01, 0.1, 1.1),
            array(1.05, 0.1, 1.1),
            array(1.09, 0.1, 1.1),
            array(11.28, 0.1, 11.3),
            array(11.25, 0.1, 11.3),
            array(11.21, 0.1, 11.3),
            array(11.28, 0.01, 11.28),
            array(11.25, 0.01, 11.25),
            array(11.21, 0.01, 11.21),
            array(11.228, 0.01, 11.23),
            array(11.225, 0.01, 11.23),
            array(11.221, 0.01, 11.23),
            array(111, 10, 120),
            array(115, 10, 120),
            array(119, 10, 120),
            array(111, 100, 200),
            array(150, 100, 200),
            array(190, 100, 200),
            array(2.01, 0.01, 2.01),
            array(222, 0.01, 222.0),
            array(222, 0.1, 222.0),
            array(222, 1, 222.0),
            array(198.92, 0.1, 199)
        );
    }
}
