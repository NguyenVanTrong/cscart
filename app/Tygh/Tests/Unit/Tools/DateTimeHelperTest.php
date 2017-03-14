<?php
namespace Tygh\Tests\Unit\Tools;

use Tygh\Tools\DateTimeHelper;

class DateTimeHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dpFormatTimeZoneOffsetString
     */
    public function testFormatTimeZoneOffsetString($expected_offset_string, $offset_seconds)
    {
        $this->assertEquals($expected_offset_string, DateTimeHelper::formatTimeZoneOffsetString($offset_seconds));
    }

    public function dpFormatTimeZoneOffsetString()
    {
        return array(
            'Asia/Kolkata' => array('+05:30', 19800),
            'Asia/Kathmandu' => array('+05:45', 20700),
            'Europe/Moscow' => array('-04:00', -14400),
        );
    }
}