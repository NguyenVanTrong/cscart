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

namespace Tygh\Mailer;

/**
 * The class responsible for sending email messages.
 *
 * @package Tygh\Mailer
 */
class Mailer
{
    /**
     * @var ITransportFactory Factory of transport
     */
    private $transport_factory;

    /**
     * @var ITransport The default transport used to send messages
     */
    private $default_transport;

    /**
     * @var IMessageBuilderFactory Factory of message builder
     */
    private $message_builder_factory;

    /**
     * @var bool Allow use new email templates from database
     */
    private $allow_db_templates =  false;

    /**
     * @var IMessageBuilder[] List of created message builders
     */
    private $message_builders = array();

    /**
     * @var string Default language code, it will be used to send emails if $lang_code do not explicitly set.
     */
    private $default_language_code;

    /**
     * Mailer constructor.
     *
     * @param IMessageBuilderFactory    $message_builder_factory    Factory of transport
     * @param ITransportFactory         $transport_factory          Factory of transport
     * @param array                     $default_transport_settings Default transport mailer settings.
     *                                                              For create default transport.
     * @param bool                      $allow_db_templates         Allow use new email templates from database
     * @param string                    $default_language_code      Default language code
     */
    public function __construct(
        IMessageBuilderFactory $message_builder_factory,
        ITransportFactory $transport_factory,
        array $default_transport_settings,
        $allow_db_templates = false,
        $default_language_code
    )
    {
        $this->message_builder_factory = $message_builder_factory;
        $this->transport_factory = $transport_factory;
        $this->default_transport = $this->getTransport($default_transport_settings);
        $this->allow_db_templates = (bool) $allow_db_templates;
        $this->default_language_code = $default_language_code;
    }

    /**
     * Gets message builder instance
     *
     * @param string $type Builder type (file_template|db_template|default)
     *
     * @return IMessageBuilder
     */
    public function getMessageBuilder($type)
    {
        if (!isset($this->message_builders[$type])) {
            $this->message_builders[$type] = $this->message_builder_factory->createBuilder($type);
        }

        return $this->message_builders[$type];
    }

    /**
     * Gets mailer transport instance
     *
     * @param array $settings Array of mailer transport settings
     *
     * @return ITransport
     */
    public function getTransport(array $settings)
    {
        return $this->transport_factory->createTransport(
            isset($settings['mailer_send_method']) ? $settings['mailer_send_method'] : null,
            $settings
        );
    }

    /**
     * Send message
     *
     * @param array|Message $message            Array of E-mail message params or Message object.
     * @param null|string   $area               Current working area (A-admin|C-customer)
     * @param null|string   $lang_code          Language code
     * @param array|null    $transport_settings Mailer transport settings, if it is null will be used default transport
     *
     * @return bool
     */
    public function send($message, $area = null, $lang_code = null, array $transport_settings = null)
    {
        $lang_code = empty($lang_code) ? $this->default_language_code : $lang_code;
        $transport = empty($transport_settings) ? $this->default_transport : $this->getTransport($transport_settings);

        if (!$message instanceof Message) {

            /**
             * Changes message params before message created
             *
             * @param ITransport    $transport  Instance of transport for send mail
             * @param array         $message    Message params
             * @param string        $area       Current working area (A-admin|C-customer)
             * @param string        $lang_code  Language code
             */
            fn_set_hook('send_mail_pre', $transport, $message, $area, $lang_code);

            if ($this->allow_db_templates && !empty($message['template_code'])) {
                $builder = $this->getMessageBuilder('db_template');
            } elseif (!empty($message['tpl'])) {
                $builder = $this->getMessageBuilder('file_template');
            } elseif (!empty($message['message_builder'])) {
                $builder = $this->getMessageBuilder($message['message_builder']);
            } else {
                $builder = $this->getMessageBuilder('default');
            }

            $message = $builder->createMessage($message, $area, $lang_code);
        }

        $body = $message->getBody();
        $from = $message->getFrom();
        $to = $message->getTo();

        if (empty($body) || empty($from) || empty($to)) {
            return false;
        }

        /**
         * Allows to change the message before sending it.
         *
         * @param Mailer        $this       Instance of mailer
         * @param ITransport    $transport  Instance of transport for send mail
         * @param Message       $message    Instance of message
         * @param string        $area       Current working area (A-admin|C-customer)
         * @param string        $lang_code  Language code
         */
        fn_set_hook('mailer_send_pre', $this, $transport, $message, $area, $lang_code);

        $result = $transport->sendMessage($message);

        foreach ($result->getErrors() as $error) {
            fn_set_notification('E', __('error'), __('error_message_not_sent') . ' ' . $error);
        }

        /**
         * Allows to check the result of sending the message.
         *
         * @param Mailer        $this       Instance of mailer
         * @param ITransport    $transport  Instance of transport for send mail
         * @param Message       $message    Instance of message
         * @param SendResult    $result     Instance of send result
         * @param string        $area       Current working area (A-admin|C-customer)
         * @param string        $lang_code  Language code
         */
        fn_set_hook('mailer_send_post', $this, $transport, $message, $result, $area, $lang_code);

        return $result->isSuccess();
    }
}