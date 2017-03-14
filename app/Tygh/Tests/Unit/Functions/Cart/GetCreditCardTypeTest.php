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

namespace Tygh\Tests\Unit\Functions\Cart;

class GetCreditCartTypeTest extends \Tygh\Tests\Unit\ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    protected $app;

    /**
     * @dataProvider getCreditCards
     */
    public function testGetCreditCardType($card_number, $expected_type, $is_valid = true)
    {
        $actual_type = fn_get_credit_card_type($card_number);

        if ($is_valid) {
            $this->assertEquals($expected_type, $actual_type);
        } else {
            $this->assertNotEquals($expected_type, $actual_type);
        }

    }

    public function getCreditCards()
    {
        return array(
            array('340000000000000', 'amex'),
            array('370000000000000', 'amex'),
            array('380000000000000', 'amex', false), // prefix
            array('3100000000000000', 'amex', false), // length

            array('30000000000000', 'diners_club_carte_blanche'),
            array('30600000000000', 'diners_club_carte_blanche', false), // prefix
            array('300000000000000', 'diners_club_carte_blanche', false), // length

            array('36000000000008', 'diners_club_international'),
            array('37000000000000', 'diners_club_international', false), // prefix
            array('309000000000000', 'diners_club_international', false), // length

            array('3528000000000000', 'jcb'),
            array('3529000000000000', 'jcb'),
            array('3530000000000000', 'jcb'),
            array('3589000000000000', 'jcb'),
            array('3590000000000000', 'jcb', false), // prefix
            array('3527000000000000', 'jcb', false), // prefix
            array('35280000000000000', 'jcb', false), // length

            array('6304000000000000', 'laser'),
            array('6706000000000000', 'laser'),
            array('6771000000000000', 'laser'),
            array('6709000000000000', 'laser'),
            array('63040000000000000', 'laser'),
            array('67060000000000000', 'laser'),
            array('67710000000000000', 'laser'),
            array('67090000000000000', 'laser'),
            array('630400000000000000', 'laser'),
            array('670600000000000000', 'laser'),
            array('677100000000000000', 'laser'),
            array('670900000000000000', 'laser'),
            array('6304000000000000000', 'laser'),
            array('6706000000000000000', 'laser'),
            array('6771000000000000000', 'laser'),
            array('6709000000000000000', 'laser'),
            array('6305000000000000000', 'laser', false), // prefix
            array('67060000000000000000', 'laser', false), // length

            array('4567350000000000', 'visa_debit'),
            array('4006260000000000', 'visa_debit'),
            array('4085474900000000', 'visa_debit'),
            array('4094000200000000', 'visa_debit'),
            array('4122858600000000', 'visa_debit'),
            array('4137333700000000', 'visa_debit'),
            array('4137878800000000', 'visa_debit'),
            array('4187600000000000', 'visa_debit'),
            array('4191767900000000', 'visa_debit'),
            array('4197720000000000', 'visa_debit'),
            array('4206720000000000', 'visa_debit'),
            array('4215929400000000', 'visa_debit'),
            array('4227930000000000', 'visa_debit'),
            array('4237690000000000', 'visa_debit'),
            array('4310720000000000', 'visa_debit'),
            array('4440010000000000', 'visa_debit'),
            array('4440050800000000', 'visa_debit'),
            array('4462001100000000', 'visa_debit'),
            array('4462135400000000', 'visa_debit'),
            array('4462577200000000', 'visa_debit'),
            array('4462748300000000', 'visa_debit'),
            array('4462860000000000', 'visa_debit'),
            array('4462940000000000', 'visa_debit'),
            array('4462000000000000', 'visa_debit'),
            array('4508750000000000', 'visa_debit'),
            array('4539787900000000', 'visa_debit'),
            array('4543130000000000', 'visa_debit'),
            array('4544323500000000', 'visa_debit'),
            array('4547420000000000', 'visa_debit'),
            array('4567254500000000', 'visa_debit'),
            array('4658307900000000', 'visa_debit'),
            array('4659015000000000', 'visa_debit'),
            array('4751105900000000', 'visa_debit'),
            array('4757105900000000', 'visa_debit'),
            array('4762206900000000', 'visa_debit'),
            array('4763408900000000', 'visa_debit'),
            array('4844091000000000', 'visa_debit'),
            array('4844270000000000', 'visa_debit'),
            array('4909607900000000', 'visa_debit'),
            array('4921818200000000', 'visa_debit'),
            array('4001150000000000', 'visa_debit'),
            array('4008373900000000', 'visa_debit'),
            array('4129212300000000', 'visa_debit'),
            array('4179350000000000', 'visa_debit'),
            array('4197400000000000', 'visa_debit'),
            array('4197410000000000', 'visa_debit'),
            array('4197737600000000', 'visa_debit'),
            array('4245190000000000', 'visa_debit'),
            array('4249623000000000', 'visa_debit'),
            array('4440000000000000', 'visa_debit'),
            array('4844060800000000', 'visa_debit'),
            array('4844112600000000', 'visa_debit'),
            array('4844285500000000', 'visa_debit'),
            array('4918800000000000', 'visa_debit'),
            array('4918900000000000', 'visa_debit', false), // prefix
            array('49208000000000000', 'visa_debit', false), // length

            array('4026000000000000', 'visa_electron'),
            array('4175000000000000', 'visa_electron'),
            array('4508000000000000', 'visa_electron'),
            array('4844000000000000', 'visa_electron'),
            array('4913000000000000', 'visa_electron'),
            array('4917000000000000', 'visa_electron'),
            array('4845000000000000', 'visa_electron', false), // prefix
            array('40260000000000000', 'visa_electron', false), // length

            array('4000000000000000', 'visa'),
            array('4000000000000', 'visa'),
            array('50000000000000', 'visa', false), // prefix
            array('40000000000000', 'visa', false), // length

            array('5167300000000000', 'mastercard_debit'),
            array('5169790000000000', 'mastercard_debit'),
            array('5170000000000000', 'mastercard_debit'),
            array('5170490000000000', 'mastercard_debit'),
            array('5351100000000000', 'mastercard_debit'),
            array('5353090000000000', 'mastercard_debit'),
            array('5354200000000000', 'mastercard_debit'),
            array('5358190000000000', 'mastercard_debit'),
            array('5372100000000000', 'mastercard_debit'),
            array('5376090000000000', 'mastercard_debit'),
            array('5573470000000000', 'mastercard_debit'),
            array('5574960000000000', 'mastercard_debit'),
            array('5574980000000000', 'mastercard_debit'),
            array('5575470000000000', 'mastercard_debit'),
            array('5575080000000000', 'mastercard_debit', false), // prefix
            array('55754700000000000', 'mastercard_debit', false), // length

            array('5100000000000000', 'mastercard'),
            array('5200000000000000', 'mastercard'),
            array('5300000000000000', 'mastercard'),
            array('5400000000000000', 'mastercard'),
            array('5500000000000000', 'mastercard'),
            array('5600000000000000', 'mastercard', false), // prefix
            array('56000000000000000', 'mastercard', false), // length

            array('500000000000', 'maestro'),
            array('5000000000000', 'maestro'),
            array('50000000000000', 'maestro'),
            array('500000000000000', 'maestro'),
            array('5000000000000000', 'maestro'),
            array('50000000000000000', 'maestro'),
            array('500000000000000000', 'maestro'),
            array('5000000000000000000', 'maestro'),
            array('5000000000000000000', 'maestro'),
            array('560000000000', 'maestro'),
            array('590000000000', 'maestro'),
            array('600000000000', 'maestro'),
            array('690000000000', 'maestro'),
            array('550000000000', 'maestro', false), // prefix
            array('700000000000', 'maestro', false), // prefix
            array('50000000000000000000', 'maestro', false), // length

            array('6011075140481728', 'discover'),
            array('6011196725447270', 'discover'),
            array('6011710313791764', 'discover'),
            array('6012710313791764', 'discover', false), // prefix
            array('60117103137917643', 'discover', false), // length

            array(0, false)
        );
    }

    protected function setUp()
    {
        define('BOOTSTRAP', true);
        define('AREA', 'A');
        define('CART_LANGUAGE', 'en');

        $this->requireCore('functions/fn.cart.php');
    }
}
