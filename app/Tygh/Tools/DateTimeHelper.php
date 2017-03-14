<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Tools;

use DateTime;
use DateTimeZone;

class DateTimeHelper
{
    const PERIOD_TODAY = 'D';
    const PERIOD_YESTERDAY = 'LD';

    const PERIOD_THIS_WEEK = 'W';
    const PERIOD_LAST_WEEK = 'LW';

    const PERIOD_THIS_MONTH = 'M';
    const PERIOD_LAST_MONTH = 'LM';

    const PERIOD_THIS_YEAR = 'Y';
    const PERIOD_LAST_YEAR = 'LY';

    const PERIOD_DAY_AGO_TILL_NOW = 'HH';
    const PERIOD_WEEK_AGO_TILL_NOW = 'HW';
    const PERIOD_MONTH_AGO_TILL_NOW = 'HM';

    public static function getPeriod($period_name)
    {
        $definitions = self::getPeriodDefinitions();
        if (isset($definitions[$period_name])) {
            return self::createCustomPeriod(
                $definitions[$period_name]['from'],
                $definitions[$period_name]['to']
            );
        }

        return null;
    }

    public static function createCustomPeriod($start_date_definition, $end_date_definition)
    {
        return array(
            'from' => date_create($start_date_definition),
            'to' => date_create($end_date_definition)
        );
    }

    public static function getPeriodDefinitions()
    {
        $week_period_modifier = '';

        // Workaround for the php bug https://bugs.php.net/bug.php?id=63740
        if (date('w') === '0') {
            $week_period_modifier = date('Y-m-d', strtotime('-1 day'));
        }

        return array(
            self::PERIOD_TODAY => array(
                'from' => 'today',
                'to' => 'today 23:59:59',
            ),
            self::PERIOD_YESTERDAY => array(
                'from' => 'yesterday',
                'to' => 'yesterday 23:59:59',
            ),
            self::PERIOD_THIS_WEEK => array(
                'from' => $week_period_modifier . ' this week 00:00:00',
                'to' => $week_period_modifier . ' this week +6 days 23:59:59',
            ),
            self::PERIOD_LAST_WEEK => array(
                'from' => $week_period_modifier . ' previous week 00:00:00',
                'to' => $week_period_modifier . ' previous week +6 days 23:59:59',
            ),
            self::PERIOD_THIS_MONTH => array(
                'from' => 'first day of this month 00:00:00',
                'to' => 'last day of this month 23:59:59',
            ),
            self::PERIOD_LAST_MONTH => array(
                'from' => 'first day of previous month 00:00:00',
                'to' => 'last day of previous month 23:59:59',
            ),
            self::PERIOD_THIS_YEAR => array(
                'from' => 'first day of January this year 00:00:00',
                'to' => 'last day of December this year 23:59:59',
            ),
            self::PERIOD_LAST_YEAR => array(
                'from' => 'first day of January previous year 00:00:00',
                'to' => 'last day of December previous year 23:59:59',
            ),
            self::PERIOD_DAY_AGO_TILL_NOW => array(
                'from' => '1 day ago',
                'to' => 'now',
            ),
            self::PERIOD_WEEK_AGO_TILL_NOW => array(
                'from' => '1 week ago',
                'to' => 'now',
            ),
            self::PERIOD_MONTH_AGO_TILL_NOW => array(
                'from' => '1 month ago',
                'to' => 'now',
            ),
        );
    }

    /**
     * Calculates the offset of given time zone to UTC time zone in seconds.
     *
     * @param string $time_zone_name The name of a timezone like "Europe/London"
     *
     * @return int|false Returns time zone offset in seconds on success or false on failure.
     */
    public static function getTimeZoneOffset($time_zone_name)
    {
        $tz = new DateTimeZone($time_zone_name);

        return $tz->getOffset(new DateTime('now', new DateTimeZone('UTC')));
    }

    /**
     * Calculates the offset of given time zone to UTC time zone.
     *
     * @param string $time_zone_name The name of a timezone like "Europe/London"
     *
     * @return string|false Returns positive or negative offset string representation like "+08:00" or "-03:00" on success or false on failure.
     */
    public static function getTimeZoneOffsetString($time_zone_name)
    {
        $tz_offset = self::getTimeZoneOffset($time_zone_name);
        return $tz_offset !== false ? self::formatTimeZoneOffsetString($tz_offset) : false;
    }

    /**
     * Converts given timezone offset to human-readable format.
     *
     * @param int $offset Offset in seconds, can either be positive or negative
     *
     * @return string Positive or negative offset string representation like "+08:00" or "-03:00"
     */
    public static function formatTimeZoneOffsetString($offset)
    {
        $tz_offset_string = sprintf('%s%02d:%02d',
            ($offset >= 0) ? '+' : '-',
            abs($offset / 3600),
            abs($offset % 3600) / 60
        );

        return $tz_offset_string;
    }
}