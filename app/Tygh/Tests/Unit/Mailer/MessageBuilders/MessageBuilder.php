<?php


namespace Tygh\Tests\Unit\Mailer\MessageBuilders;


use Tygh\Mailer\AMessageBuilder;
use Tygh\Mailer\Message;

class MessageBuilder extends AMessageBuilder
{
    protected function initMessage(Message $message, $params, $area, $lang_code)
    {
    }

    public function getCompany($company_id, $lang_code)
    {
        return self::getCompanyTest($company_id, $lang_code);
    }

    public function getDefaultCompanyId()
    {
        return self::getDefaultCompanyIdTest();
    }

    public function getImageSize($real_path)
    {
        return self::getImageSizeTest($real_path);
    }

    public function getImageExtension($mime_type)
    {
        return self::getImageExtensionTest($mime_type);
    }

    public function allowedFor($edition)
    {
        return self::allowedForTest($edition);
    }

    public function validateAddress($email)
    {
        return self::validateAddressTest($email);
    }

    public static function getCompanyTest($company_id, $lang_code)
    {
        $companies = array(
            0 => array(
                'company_name' => 'Simtech',
                'default_company_name' => 'Default Simtech',
                'company_users_department' => 'company_users_department@example.com',
                'default_company_users_department' => 'default_company_users_department@example.com',
                'company_site_administrator' => 'company_site_administrator@example.com',
                'default_company_site_administrator' => 'default_company_site_administrator@example.com',
                'company_orders_department' => 'company_orders_department@example.com',
                'default_company_orders_department' => 'default_company_orders_department@example.com',
                'company_support_department' => 'company_support_department@example.com',
                'default_company_support_department' => 'default_company_support_department@example.com',
                'company_newsletter_email' => 'company_newsletter_email@example.com',
                'default_company_newsletter_email' => 'default_company_newsletter_email@example.com',
                'test_empty_email' => ''
            ),
            1 => array(
                'company_name' => 'Simtech1',
                'default_company_name' => 'Default Simtech1',
                'company_users_department' => 'company_users_department1@example.com',
                'default_company_users_department' => 'default_company_users_department1@example.com',
                'company_site_administrator' => 'company_site_administrator1@example.com',
                'default_company_site_administrator' => 'default_company_site_administrator1@example.com',
                'company_orders_department' => 'company_orders_department1@example.com',
                'default_company_orders_department' => 'default_company_orders_department1@example.com',
                'company_support_department' => 'company_support_department1@example.com',
                'default_company_support_department' => 'default_company_support_department1@example.com',
                'company_newsletter_email' => 'company_newsletter_email1@example.com',
                'default_company_newsletter_email' => 'default_company_newsletter_email1@example.com',
                'test_empty_email' => 'test_empty_email@example.com'
            )
        );

        return $companies[$company_id];
    }

    public static function getDefaultCompanyIdTest()
    {
        return 1;
    }

    public static function getImageSizeTest($real_path)
    {
        return array(100, 100, '');
    }

    public static function getImageExtensionTest($mime_type)
    {
        return 'jpeg';
    }

    public static function allowedForTest($edition)
    {
        return true;
    }

    public static function validateAddressTest($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}