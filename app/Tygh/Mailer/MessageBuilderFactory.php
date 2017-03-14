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


use Pimple\Container;
use Tygh\Mailer\MessageBuilders\DBTemplateMessageBuilder;
use Tygh\Mailer\MessageBuilders\DefaultMessageBuilder;
use Tygh\Mailer\MessageBuilders\FileTemplateMessageBuilder;
use Tygh\Registry;

/**
 * The class factory responsible for creating message builder objects.
 * 
 * @package Tygh\Mailer
 */
class MessageBuilderFactory implements IMessageBuilderFactory
{
    /** @var Container */
    protected $app;

    /**
     * MessageBuilderFactory constructor.
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /** @inheritdoc */
    public function createBuilder($type)
    {
        switch ($type) {
            case 'db_template':
                return new DBTemplateMessageBuilder(
                    $this->app['template.renderer'],
                    $this->app['template.mail.repository'],
                    $this->app['mailer.message_style_formatter'],
                    Registry::get('config')
                );
                break;
            case 'file_template':
                return new FileTemplateMessageBuilder($this->app['view'], Registry::get('config'));
                break;
            case 'default':
                return new DefaultMessageBuilder(Registry::get('config'));
                break;
            default:
                throw new MailerException("Undefined message builder: {$type}");
                break;
        }
    }
}