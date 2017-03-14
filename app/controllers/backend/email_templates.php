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

use Tygh\Registry;
use Tygh\Mailer\Message;
use Tygh\Mailer\MessageStyleFormatter;
use Tygh\Template\Mail\Template;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * @var string $mode
 */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    fn_trusted_vars('email_template', 'snippet_data');

    if ($mode == 'update') {
        if (empty($_REQUEST['template_id'])) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        /** @var \Tygh\Template\Mail\Service $service */
        $service = Tygh::$app['template.mail.service'];
        /** @var \Tygh\Template\Mail\Repository $repository */
        $repository = Tygh::$app['template.mail.repository'];

        $template = $repository->findById($_REQUEST['template_id']);

        if (!$template) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        $result = $service->updateTemplate($template, $_REQUEST['email_template']);

        if (!$result->isSuccess()) {
            fn_save_post_data('email_template');
            $result->showNotifications();
        }

        return array(CONTROLLER_STATUS_OK, 'email_templates.update?template_id=' . $template->getId());
    }

    if ($mode == 'preview') {
        /** @var \Tygh\Template\Renderer $renderer */
        $renderer = Tygh::$app['template.renderer'];
        /** @var \Tygh\Template\Mail\Repository $repository */
        $repository = Tygh::$app['template.mail.repository'];
        /** @var \Tygh\SmartyEngine\Core $view */
        $view = Tygh::$app['view'];

        $email_template = $repository->findById($_REQUEST['template_id']);

        if (!$email_template) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        if (isset($_REQUEST['email_template'])) {
            if (isset($_REQUEST['email_template']['template'])) {
                $result = $renderer->validate($_REQUEST['email_template']['template']);

                if (!$result->isSuccess()) {
                    $result->showNotifications();
                    exit;
                }
            }

            $email_template->loadFromArray($_REQUEST['email_template']);
        }

        $variables = $renderer->retrieveVariables($email_template->getTemplate() . "\n" . $email_template->getSubject());
        $variables = array_combine($variables, $variables);

        $context = new \Tygh\Template\Mail\Context($variables, $email_template->getArea(), DESCR_SL);
        $collection = new \Tygh\Template\Collection($context->data);

        $message = new Message();
        $message->setBody($renderer->renderTemplate($email_template, $context, $collection));
        $message->setSubject($renderer->render($email_template->getSubject(), $collection->getAll()));

        $style_formatter = new MessageStyleFormatter();
        $style_formatter->convert($message);

        $view->assign('preview', $message);
        $view->display('views/email_templates/preview.tpl');
        exit;
    }

    if ($mode == 'restore') {
        /** @var \Tygh\Template\Mail\Service $service */
        $service = Tygh::$app['template.mail.service'];
        /** @var \Tygh\Template\Mail\Repository $repository */
        $repository = Tygh::$app['template.mail.repository'];

        $template_id = isset($_REQUEST['template_id']) ? (int) $_REQUEST['template_id'] : 0;
        $template = $repository->findById($template_id);

        if (!$template) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        if ($service->restoreTemplate($template)) {
            fn_set_notification('N', __('notice'), __('text_changes_saved'));
        }

        if (!empty($_REQUEST['return_url'])) {
            return array(CONTROLLER_STATUS_REDIRECT, $_REQUEST['return_url']);
        }
    }

    if ($mode == 'send') {
        /** @var \Tygh\Mailer\Mailer $mailer */
        $mailer = Tygh::$app['mailer'];
        /** @var \Tygh\Template\Renderer $renderer */
        $renderer = Tygh::$app['template.renderer'];
        /** @var \Tygh\Template\Mail\Repository $repository */
        $repository = Tygh::$app['template.mail.repository'];

        $email_template = $repository->findById($_REQUEST['template_id']);
        $email_template->loadFromArray($_REQUEST['email_template']);

        $user_data = fn_get_user_info(Tygh::$app['session']['auth']['user_id']);

        $variables = $renderer->retrieveVariables($email_template->getSubject() . "\n" . $email_template->getTemplate());
        $variables = array_combine($variables, $variables);

        $result = $mailer->send(
            array(
                'template' => $email_template,
                'template_code' => $email_template->getCode(),
                'to' => $user_data['email'],
                'from' => 'company_users_department',
                'reply_to' => 'company_users_department',
                'data' => $variables,
            ),
            $email_template->getArea(),
            Registry::get('settings.Appearance.backend_default_language')
        );

        if ($result) {
            fn_set_notification('N', __('notice'), __('text_test_email_sent', array(
                '[email]' => $user_data['email']
            )));
        }

        exit;
    }

    if ($mode == 'export') {
        /** @var \Tygh\Template\Mail\Exim $exim */
        $exim = \Tygh::$app['template.mail.exim'];

        try {
            $xml = $exim->exportAllToXml();

            $filename = 'email_templates_' . date("m_d_Y") . '.xml';
            $file_path = Registry::get('config.dir.files') . $filename;

            fn_mkdir(dirname($file_path));
            fn_put_contents($file_path, $xml);
            fn_get_file($file_path);

        } catch (Exception $e) {
            fn_set_notification('E', __('error'), $e->getMessage());
        }
    }

    if ($mode == 'import') {
        /** @var \Tygh\Template\Mail\Exim $exim */
        $exim = \Tygh::$app['template.mail.exim'];

        $data = fn_filter_uploaded_data('filename', array('xml'));
        $file = reset($data);

        if (!empty($file['path'])) {
            try {
                $result = $exim->importFromXmlFile($file['path']);
                $counter = $result->getData();

                /** @var \Smarty $smarty */
                $smarty = Tygh::$app['view'];

                $smarty->assign('import_result', array(
                    'count_success_templates' => $counter['success_templates'],
                    'count_success_snippets' => $counter['success_snippets'],
                    'count_fail_templates' => $counter['fail_templates'],
                    'count_fail_snippets' => $counter['fail_snippets'],
                    'errors' => $result->getErrors(),
                ));

                fn_set_notification(
                    'I',
                    __('import_results'),
                    $smarty->fetch('views/email_templates/components/import_summary.tpl')
                );
            } catch (Exception $e) {
                fn_set_notification('E', __('error'), $e->getMessage());
            }
        }
    }

    return array(CONTROLLER_STATUS_OK, 'email_templates.manage');
}

if ($mode == 'manage') {
    /** @var \Tygh\Template\Mail\Repository $repository */
    $repository = Tygh::$app['template.mail.repository'];
    /** @var \Tygh\Template\Snippet\Repository $snippet_repository */
    $snippet_repository = Tygh::$app['template.snippet.repository'];
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $email_templates = $repository->find();

    Registry::set('navigation.tabs', array(
        'email_templates_C' => array(
            'title' => __('customer_notifications'),
            'js' => true,
        ),
        'email_templates_A' => array(
            'title' => __('admin_notifications'),
            'js' => true,
        ),
        'snippets' => array(
            'title' => __('code_snippets'),
            'js' => true,
        )
    ));

    // group by area
    $groups = array();
    foreach ($email_templates as $email_template) {
        $groups[$email_template->getArea()][] = $email_template;
    }

    foreach ($groups as $group_id => $templates) {
        usort($groups[$group_id], function (Template $template_a, Template $template_b) {
            return strcmp($template_a->getName(), $template_b->getName());
        });
    }

    $view->assign('snippets', $snippet_repository->findByType('mail'));
    $view->assign('groups', $groups);
} elseif ($mode == 'update') {
    /** @var \Tygh\Template\Mail\Repository $repository */
    $repository = Tygh::$app['template.mail.repository'];
    /** @var \Tygh\Template\Snippet\Repository $snippet_repository */
    $snippet_repository = Tygh::$app['template.snippet.repository'];
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    /** @var \Tygh\Template\Renderer $renderer */
    $renderer = Tygh::$app['template.renderer'];

    if (empty($_REQUEST['template_id'])) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $email_template = $repository->findById($_REQUEST['template_id']);

    if (!$email_template) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $default_subject = $email_template->getDefaultSubject() ? $email_template->getDefaultSubject() : $email_template->getSubject();
    $default_template = $email_template->getDefaultTemplate() ? $email_template->getDefaultTemplate() : $email_template->getTemplate();

    $snippets = $snippet_repository->findByType('mail');

    $variables = array_unique(array_merge(
        array('company_name', 'company_data', 'logos', 'styles', 'settings'),
        $renderer->retrieveVariables($email_template->getDefaultTemplate() . "\n" . $email_template->getDefaultSubject())
    ));

    if ($post_data = fn_restore_post_data('email_template')) {
        $email_template->loadFromArray($post_data);
    }

    $view->assign('snippets', $snippets);
    $view->assign('email_template', $email_template);
    $view->assign('params_schema', $email_template->getPreparedParamsSchema());
    $view->assign('default_subject', $default_subject);
    $view->assign('default_template', $default_template);
    $view->assign('variables', $variables);

    Registry::set('navigation.tabs', array(
        'general' => array(
            'title' => __('general'),
            'js' => true,
        )
    ));
}