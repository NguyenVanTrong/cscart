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

namespace Tygh\Addons\Recaptcha;

use Tygh\Web\Antibot\IAntibotDriver;
use ReCaptcha\ReCaptcha;

/**
 * Class RecaptchaDriver implements integration with Google reCAPTCHA service.
 *
 * @package Tygh\Addons\Recaptcha
 */
class RecaptchaDriver implements IAntibotDriver
{
    const RECAPTCHA_TOKEN_PARAM_NAME = 'g-recaptcha-response';

    /**
     * @var array Recaptcha add-on settings
     */
    protected $settings;

    /**
     * RecaptchaDriver constructor.
     *
     * @param array $addon_settings Recaptcha add-on settings
     */
    public function __construct(array $addon_settings)
    {
        $this->settings = $addon_settings;
    }

    /**
     * @inheritdoc
     */
    public function isSetUp()
    {
        $required_settings = array(
            'recaptcha_site_key',
            'recaptcha_secret',
            'recaptcha_theme',
            'recaptcha_size',
            'recaptcha_type',
        );

        foreach ($required_settings as $required_setting) {
            if (empty($this->settings[$required_setting])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function validateHttpRequest(array $http_request_data)
    {
        if (isset($http_request_data[static::RECAPTCHA_TOKEN_PARAM_NAME])) {
            $recaptcha_token = $http_request_data[static::RECAPTCHA_TOKEN_PARAM_NAME];

            $user_ip_address = fn_get_ip();
            $user_ip_address = $user_ip_address['host'];

            $recaptcha = new ReCaptcha($this->settings['recaptcha_secret']);
            $response = $recaptcha->verify($recaptcha_token, $user_ip_address);

            return $response->isSuccess();
        }

        return false;
    }
}
