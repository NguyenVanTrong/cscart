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
use Tygh\Template\Snippet\Snippet;
use Tygh\Template\Document\IPreviewableType;

/** @var string $mode */

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'update') {
        fn_trusted_vars('document');

        /** @var \Tygh\Template\Document\TypeFactory $type_factory */
        $type_factory = Tygh::$app['template.document.type_factory'];
        /** @var \Tygh\Template\Document\Repository $repository */
        $repository = Tygh::$app['template.document.repository'];
        /** @var \Tygh\Template\Document\Service $service */
        $service = Tygh::$app['template.document.service'];
        /** @var \Tygh\Template\Snippet\Table\ColumnRepository $table_column_repository */
        $table_column_repository = Tygh::$app['template.snippet.table.column_repository'];

        $document_id = isset($_REQUEST['document_id']) ? (int) $_REQUEST['document_id'] : 1;

        $document = $repository->findById($document_id);

        if (!$document) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        fn_save_post_data('document');
        $data = $_REQUEST['document'];
        $data = $service->filterData($data, array('template'));
        $result = $service->updateDocument($document, $data);

        if ($result->isSuccess()) {
            if (!empty($_REQUEST['return_url'])) {
                return array(CONTROLLER_STATUS_OK, $_REQUEST['return_url']);
            } else {
                return array(CONTROLLER_STATUS_REDIRECT, 'documents.update?document_id=' . $document->getId());
            }
        } else {
            $result->showNotifications();
            $document->loadFromArray($data);

            return array(CONTROLLER_STATUS_REDIRECT, 'documents.update?document_id=' . $document->getId());
        }
    }

    if ($mode == 'preview') {
        fn_trusted_vars('document');
        /** @var \Tygh\Template\Document\TypeFactory $type_factory */
        $type_factory = Tygh::$app['template.document.type_factory'];
        /** @var \Tygh\Template\Document\Repository $repository */
        $repository = Tygh::$app['template.document.repository'];
        /** @var \Tygh\Template\Renderer $renderer */
        $renderer = Tygh::$app['template.renderer'];
        /** @var \Tygh\SmartyEngine\Core $view */
        $view = Tygh::$app['view'];

        $document_id = isset($_REQUEST['document_id']) ? (int) $_REQUEST['document_id'] : 0;

        $document = $repository->findById($document_id);

        if (!$document) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        $document_type = $type_factory->create($document->getType());

        if (!$document_type instanceof IPreviewableType) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        if (isset($_REQUEST['document'])) {
            if (isset($_REQUEST['document']['template'])) {
                $result = $renderer->validate($_REQUEST['document']['template']);

                if (!$result->isSuccess()) {
                    $result->showNotifications();
                    exit;
                }
            }
            $document->loadFromArray($_REQUEST['document']);
        }

        try {
            $preview = $document_type->preview($document);

            $view->assign('preview', $preview);
            $view->display('views/documents/preview.tpl');
        } catch (Exception $e) {
            fn_set_notification('E', __('error'), $e->getMessage());
        }
        exit;
    }

    if ($mode == 'restore') {
        /** @var \Tygh\Template\Document\Repository $repository */
        $repository = Tygh::$app['template.document.repository'];
        /** @var \Tygh\Template\Document\Service $service */
        $service = Tygh::$app['template.document.service'];
        $document_id = isset($_REQUEST['document_id']) ? (int) $_REQUEST['document_id'] : 0;

        $document = $repository->findById($document_id);

        if (!$document) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        if ($service->restoreTemplate($document)) {
            fn_set_notification('N', __('notice'), __('text_changes_saved'));
        }
    }

    if ($mode == 'delete') {
        /** @var \Tygh\Template\Document\Repository $repository */
        $repository = Tygh::$app['template.document.repository'];
        /** @var \Tygh\Template\Document\Service $service */
        $service = Tygh::$app['template.document.service'];
        $document_id = isset($_REQUEST['document_id']) ? (int) $_REQUEST['document_id'] : 0;

        $document = $repository->findById($document_id);

        if (!$document) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        $service->removeDocument($document);
    }

    if ($mode == 'export') {
        /** @var \Tygh\Template\Document\Exim $exim */
        $exim = \Tygh::$app['template.document.exim'];

        try {
            if (!empty($_REQUEST['document_id'])) {
                $document_ids = (array) $_REQUEST['document_id'];

                $xml = $exim->exportToXmlByIds($document_ids);
            } else {
                $xml = $exim->exportAllToXml();
            }

            $filename = 'documents_' . date("m_d_Y") . '.xml';
            $file_path = Registry::get('config.dir.files') . $filename;

            fn_mkdir(dirname($file_path));
            fn_put_contents($file_path, $xml);
            fn_get_file($file_path);

        } catch (Exception $e) {
            fn_set_notification('E', __('error'), $e->getMessage());
        }
    }

    if ($mode == 'import') {
        /** @var \Tygh\Template\Document\Exim $exim */
        $exim = \Tygh::$app['template.document.exim'];

        $data = fn_filter_uploaded_data('filename', array('xml'));
        $file = reset($data);

        if (!empty($file['path'])) {
            try {
                $result = $exim->importFromXmlFile($file['path']);
                $counter = $result->getData();

                /** @var \Smarty $smarty */
                $smarty = Tygh::$app['view'];

                $smarty->assign('import_result',array(
                    'count_success_documents' => $counter['success_documents'],
                    'count_fail_templates' => $counter['fail_documents'],
                    'errors' => $result->getErrors(),
                ));

                fn_set_notification(
                    'I',
                    __('import_results'),
                    $smarty->fetch('views/documents/components/import_summary.tpl')
                );
            } catch (Exception $e) {
                fn_set_notification('E', __('error'), $e->getMessage());
            }
        }
    }

    if (!empty($_REQUEST['return_url'])) {
        return array(CONTROLLER_STATUS_REDIRECT, $_REQUEST['return_url']);
    }

    return array(CONTROLLER_STATUS_OK, 'documents.manage');
}

if ($mode == 'update') {
    /** @var \Tygh\Template\Document\TypeFactory $type_factory */
    $type_factory = Tygh::$app['template.document.type_factory'];
    /** @var \Tygh\Template\Document\Repository $repository */
    $repository = Tygh::$app['template.document.repository'];
    /** @var \Tygh\Template\Snippet\Repository $snippet_repository */
    $snippet_repository = Tygh::$app['template.snippet.repository'];
    /** @var \Tygh\Template\VariableCollectionFactory $variable_collection_factory */
    $variable_collection_factory = Tygh::$app['template.variable_collection_factory'];
    /** @var \Tygh\Template\Document\Service $service */
    $service = Tygh::$app['template.document.service'];
    /** @var \Tygh\Template\Snippet\Table\ColumnRepository $table_column_repository */
    $table_column_repository = Tygh::$app['template.snippet.table.column_repository'];
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $document_id = isset($_REQUEST['document_id']) ? (int) $_REQUEST['document_id'] : 0;
    $document = $repository->findById($document_id);

    if (!$document) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $post_data = fn_restore_post_data('document');
    $default_template = $document->getDefaultTemplate() ? $document->getDefaultTemplate() : $document->getTemplate();

    if ($post_data) {
        $document->loadFromArray($post_data);
    }

    $document_type = $type_factory->create($document->getType());
    $snippets = $snippet_repository->findByType($document->getSnippetType());
    $variables = $variable_collection_factory->createMetaDataCollection($document_type::SCHEMA_DIR, $document_type->getCode());

    $view->assign('has_preview', $document_type instanceof IPreviewableType);
    $view->assign('document', $document);
    $view->assign('default_template', $default_template);
    $view->assign('snippets', $snippets);
    $view->assign('variables', $variables->getAll());
    $view->assign('snippet_type', $document->getSnippetType());

    $tabs = array(
        'general' => array(
            'title' => __('general'),
            'js' => true,
        ),
        'snippets' => array(
            'title' => __('code_snippets'),
            'js' => true,
        )
    );

    $tables = array();

    foreach ($snippets as $snippet) {
        $params = $snippet->getParams();

        if (!empty($params['used_table'])) {
            $tables[] = array(
                'snippet' => $snippet,
                'columns' => $table_column_repository->findBySnippet($snippet->getType(), $snippet->getCode())
            );

            $tab = array(
                'title' => $snippet->getName(),
                'js' => true
            );

            if ($snippet->getStatus() == Snippet::STATUS_DISABLE) {
                $tab['hidden'] = 'Y';
            }

            $tabs['snippet_content_' . $snippet->getId() . '_table_columns'] = $tab;
        }
    }

    $view->assign('snippets_tables', $tables);

    Registry::set('navigation.tabs', $tabs);

} elseif ($mode == 'manage') {
    /** @var \Tygh\Template\Document\Service $service */
    $service = Tygh::$app['template.document.service'];
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $documents = $service->getDocuments();

    $view->assign('documents', $documents);
}