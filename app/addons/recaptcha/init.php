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

use Tygh\Addons\Recaptcha\RecaptchaDriver;
use Tygh\Application;
use Tygh\Registry;
use Tygh\Web\Antibot;

Tygh::$app->extend('antibot', function(Antibot $antibot, Application $app) {
    $recaptcha_antibot_driver = new RecaptchaDriver(Registry::get('addons.recaptcha'));

    if ($recaptcha_antibot_driver->isSetUp()) {
        $antibot->setDriver($recaptcha_antibot_driver);
    }

    return $antibot;
});
