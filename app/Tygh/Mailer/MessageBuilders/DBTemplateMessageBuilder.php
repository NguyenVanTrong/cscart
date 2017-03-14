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

namespace Tygh\Mailer\MessageBuilders;

use Tygh\Mailer\AMessageBuilder;
use Tygh\Mailer\Message;
use Tygh\Mailer\MessageStyleFormatter;
use Tygh\Template\Collection;
use Tygh\Template\Mail\Context;
use Tygh\Template\Mail\Repository;
use Tygh\Template\Mail\Template;
use Tygh\Template\Renderer;

/**
 * The class responsible for building a message based on the Twig templates from the database.
 *
 * @package Tygh\Mailer\MessageFactories
 */
class DBTemplateMessageBuilder extends AMessageBuilder
{
    /**
     * @var Repository Email template repository
     */
    private $template_repository;

    /**
     * @var Renderer Instance of template renderer
     */
    private $renderer;

    /**
     * @var MessageStyleFormatter Css style message formatter
     */
    private $style_formatter;

    /**
     * DBTemplateMessageBuilder constructor.
     *
     * @param Renderer              $renderer               Instance of template renderer
     * @param Repository            $template_repository    Instance of email template repository
     * @param MessageStyleFormatter $style_formatter        Instance of css style message formatter
     * @param array                 $config                 List of base params (see AMessageBuilder::__construct)
     */
    public function __construct(
        Renderer $renderer,
        Repository $template_repository,
        MessageStyleFormatter $style_formatter,
        array $config
    )
    {
        $this->template_repository = $template_repository;
        $this->style_formatter = $style_formatter;
        $this->renderer = $renderer;

        parent::__construct($config);
    }

    /** @inheritdoc */
    protected function initMessage(Message $message, $params, $area, $lang_code)
    {
        if (empty($params['template_code'])) {
            return;
        }

        if (isset($params['template']) && $params['template'] instanceof Template) {
            $email_template = $params['template'];
        } else {
            $email_template = $this->getTemplate($params['template_code'], $area);
        }

        if ($email_template) {
            $context = $this->getContext($params['data'], $area, $lang_code);
            $collection = new Collection($context->data);
            
            $message->setId($email_template->getCode());
            $message->setParams($email_template->getParams());
            $message->setBody($this->renderer->renderTemplate($email_template, $context, $collection));
            $message->setSubject($this->renderer->render($email_template->getSubject(), $collection->getAll()));

            $this->style_formatter->convert($message);
        }
    }

    /**
     * Gets email template context.
     *
     * @param array     $data
     * @param string    $area
     * @param string    $lang_code
     *
     * @return Context
     */
    protected function getContext($data, $area, $lang_code)
    {
        return new Context($data, $area, $lang_code);
    }

    /**
     * Get active email template model by template code and area
     *
     * @param string $code      Code identifier of template
     * @param string $area      Current working area
     *
     * @return Template|null
     */
    public function getTemplate($code, $area)
    {
        return $this->template_repository->findActiveByCodeAndArea($code, $area);
    }
}