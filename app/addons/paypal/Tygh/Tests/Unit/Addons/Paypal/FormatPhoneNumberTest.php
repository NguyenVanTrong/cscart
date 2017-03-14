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

namespace Tygh\Tests\Unit\Addons\Paypal;

use Tygh\Tests\Unit\ATestCase;

class FormatPhoneNumberTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    protected function setUp()
    {
        define('BOOTSTRAP', true);
        define('AREA', 'A');
        define('ACCOUNT_TYPE', 'admin');

        $this->requireCore('addons/paypal/func.php');
    }

    public function getSchema()
    {
        return require(__DIR__ . '/../../../../../schemas/paypal/phone_validation_rules.php');
    }

    /**
     * @dataProvider dpTestGeneral
     */
    public function testGeneral($number, $country, $expected)
    {
        $rules = $this->getSchema();
        $actual = fn_pp_format_phone_number($number, $country, $rules);
        $this->assertEquals($expected, $actual);
    }

    public function dpTestGeneral()
    {
        return array(
            array('+7(495)123-45-67', 'RU', array('7',   '4951234567', '')),
            array('(495)123-45-67',   'RU', array('7',   '4951234567', '')),
            array('+61 1300 975 707', 'AU', array('61',  '1300975707', '')),
            array('1300 975 707',     'AU', array('61',  '1300975707', '')),
            array('+61 491 570 156',  'AU', array('61',  '491570156',  '')),
            array('0491 570 156',     'AU', array('61',  '491570156',  '')),
            array('+44 1632 960900',  'GB', array('44',  '1632960900', '')),
            array('01632 960900',     'GB', array('44',  '1632960900', '')),
            array('+44 7700 900221',  'GB', array('44',  '7700900221', '')),
            array('07700 900221',     'GB', array('44',  '7700900221', '')),
            array('+1-613-555-0186',  'CA', array('1',   '6135550186', '')),
            array('613-555-0186',     'CA', array('1',   '6135550186', '')),
            array('+1-202-555-0124',  'US', array('202', '555',        '0124')),
            array('202-555-0124',     'US', array('202', '555',        '0124')),
        );
    }

}
