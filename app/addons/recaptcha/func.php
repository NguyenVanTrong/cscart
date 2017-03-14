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
use Tygh\Settings;


/**
 * @return string Notification text displayed at the add-on settings.
 */
function fn_recaptcha_settings_notice_handler()
{
    return __('recaptcha_settings_notice');
}

/**
 * @return string|null HTML code of Image verification settings inputs
 */
function fn_recaptcha_image_verification_settings_proxy()
{
    // For example, during the installation
    if (!isset(Tygh::$app['view'])) {
        return null;
    }

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $settings = Settings::instance();
    $proxied_section = $settings->getSectionByName('Image_verification');
    $proxied_setting_objects = $settings->getList($proxied_section['section_id'], 0);

    $output = '';
    foreach ($proxied_setting_objects as $subsection_name => $setting_objects) {
        foreach ($setting_objects as $setting_object) {
            $view->assign('item', $setting_object);
            $view->assign('section', $proxied_section['section_id']);
            $view->assign('html_name', "addon_data[options][{$setting_object['object_id']}]");
            $view->assign('class', 'setting-wide');
            $view->assign('html_id', "addon_option_recaptcha_{$setting_object['name']}");

            $output .= $view->fetch('common/settings_fields.tpl');
        }
    }

    return $output;
}