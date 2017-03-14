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


namespace Tygh\Mailer\Transports;


use Tygh\Mailer\ITransport;
use Tygh\Mailer\Message;
use Tygh\Mailer\SendResult;

/**
 * The class responsible for sending the message.
 *
 * @package Tygh\Mailer\Transports
 */
class PhpMailerTransport extends \PHPMailer implements ITransport
{
    /**
     * PhpMailerTransport constructor.
     * @param array $settings
     */
    public function __construct($settings)
    {
        $this->LE = (defined('IS_WINDOWS')) ? "\r\n" : "\n";
        $method = isset($settings['mailer_send_method']) ? $settings['mailer_send_method'] : '';

        if ($method == 'smtp') {
            $this->isSMTP();
            $this->SMTPAuth = $settings['mailer_smtp_auth'] == 'Y' ? true : false;
            $this->Host = $settings['mailer_smtp_host'];
            $this->Username = $settings['mailer_smtp_username'];
            $this->Password = $settings['mailer_smtp_password'];
            $this->SMTPSecure = $settings['mailer_smtp_ecrypted_connection'];

        } elseif ($method == 'sendmail') {
            $this->isSendmail();
            $this->Sendmail = $settings['mailer_sendmail_path'];

        } else {
            $this->isMail();
        }

        parent::__construct();
    }

    /**
     * Initialize object by message
     *
     * @param Message $message
     * @throws \phpmailerException
     */
    public function initByMessage(Message $message)
    {
        $this->clearReplyTos();
        $this->clearCCs();
        $this->clearBCCs();
        $this->clearAttachments();
        $this->isHTML($message->isIsHtml());
        $this->Sender = '';
        $this->CharSet = $message->getCharset();
        $this->Body = $message->getBody();
        $this->Subject = $message->getSubject();

        $from = $message->getFrom();

        if ($from) {
            $name = reset($from);
            $address = key($from);

            $this->setFrom($address, $name);
        }

        foreach ($message->getReplyTo() as $address => $name) {
            $this->addReplyTo($address, $name);
        }

        foreach ($message->getCC() as $address => $name) {
            $this->addCC($address, $name);
        }

        foreach ($message->getBCC() as $address => $name) {
            $this->addBCC($address, $name);
        }

        foreach ($message->getAttachments() as $file => $name) {
            $this->addAttachment($file, $name);
        }

        foreach ($message->getEmbeddedImages() as $item) {
            $content = @file_get_contents($item['file']);
            $this->addStringEmbeddedImage($content, $item['cid'], $item['cid'], 'base64', $item['mime_type']);
        }
    }

    /** @inheritdoc */
    public function sendMessage(Message $message)
    {
        $result = new SendResult();
        $this->initByMessage($message);
        
        foreach ($message->getTo() as $address => $name) {
            $this->clearAddresses();
            $this->addAddress($address, $name);

            if ($this->send()) {
                $result->setIsSuccess(true);
            } else {
                $result->setError($this->ErrorInfo);
            }

            fn_set_hook('send_mail', $this);
        }

        return $result;
    }
}