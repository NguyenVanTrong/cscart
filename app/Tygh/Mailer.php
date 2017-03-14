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

namespace Tygh;

/**
 * Class Mailer
 * @package Tygh
 * @deprecated since 4.4.1 use Tygh::$app['mailer'] 
 */
class Mailer extends \PHPMailer
{
    /**
     * @param $params
     * @param string $area
     * @param mixed|string $lang_code
     * @return bool
     * @deprecated since 4.4.1
     */
    public static function sendMail($params, $area = AREA, $lang_code = CART_LANGUAGE)
    {
        if (empty($params['to']) || empty($params['from']) || (empty($params['tpl']) && empty($params['body']))) {
            return false;
        }

        /** @var \Tygh\Mailer\Mailer $mailer */
        $mailer = Tygh::$app['mailer'];

        $mailer_settings = !empty($params['mailer_settings']) ? $params['mailer_settings'] : null;

        return $mailer->send($params, $area, $lang_code, $mailer_settings);
    }

    /**
     * @param $body
     * @return mixed
     * @throws Mailer\MailerException
     * @deprecated since 4.4.1
     */
    public function attachImages($body)
    {
        /** @var \Tygh\Mailer\MessageBuilderFactory $builder_factory */
        $builder_factory = Tygh::$app['mailer.message_builder_factory'];
        $builder = $builder_factory->createBuilder('default');
        $message = $builder->createMessage(array('body' => $body), AREA, CART_LANGUAGE);

        foreach ($message->getEmbeddedImages() as $item) {
            $content = @file_get_contents($item['file']);
            $this->addStringEmbeddedImage($content, $item['cid'], $item['cid'], 'base64', $item['mime_type']);
        }

        return $body;
    }

    /**
     * @param $emails
     * @return array
     * @throws Mailer\MailerException
     * @deprecated since 4.4.1
     */
    public function formatEmails($emails)
    {
        /** @var \Tygh\Mailer\MessageBuilderFactory $builder_factory */
        $builder_factory = Tygh::$app['mailer.message_builder_factory'];
        $builder = $builder_factory->createBuilder('default');

        return $builder->normalizeEmails($emails);
    }

    /**
     * @param string $email
     * @param string $method
     * @return mixed
     * @deprecated since 4.4.1
     */
    public static function ValidateAddress($email, $method = 'auto')
    {
        return fn_validate_email($email, false);
    }

    /**
     * Get email from from company
     *
     * @param string|array $from
     * @param int $company_id
     * @param string $lang_code
     * @deprecated since 4.4.1
     * @return array
     */
    public function getEmailFrom($from, $company_id, $lang_code = CART_LANGUAGE)
    {
        /** @var \Tygh\Mailer\MessageBuilderFactory $builder_factory */
        $builder_factory = Tygh::$app['mailer.message_builder_factory'];
        $builder = $builder_factory->createBuilder('default');

        return $builder->getMessageFrom($from, $company_id, $lang_code);
    }

    /**
     * Get company data
     *
     * @param int $company_id
     * @param string $lang_code
     * @deprecated since 4.4.1
     * @return array
     */
    public function getCompanyData($company_id, $lang_code = CART_LANGUAGE)
    {
        return fn_get_company_placement_info($company_id, $lang_code);
    }
}
