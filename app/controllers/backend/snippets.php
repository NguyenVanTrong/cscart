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
use Tygh\Template\Snippet\Table\Column;
/**
 * @var string $mode
 */

if (!defined('BOOTSTRAP')) { die('Access denied'); }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode === 'update_status') {
        /** @var \Tygh\Template\Snippet\Service $service */
        $service = Tygh::$app['template.snippet.service'];
        /** @var \Tygh\Template\Snippet\Repository $repository */
        $repository = Tygh::$app['template.snippet.repository'];
        /** @var \Tygh\Ajax $ajax */
        $ajax = Tygh::$app['ajax'];

        $snippet_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
        $status = $_REQUEST['status'];

        if (empty($snippet_id)) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        $snippet = $repository->findById($snippet_id);

        if (empty($snippet)) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        $current_status = $snippet->getStatus();

        $ajax->assign('snippet_id', $snippet_id);
        $ajax->assign('snippet_code', $snippet->getCode());
        $ajax->assign('current_status', $current_status);
        $ajax->assign('new_status', $status);

        if ($service->updateSnippetStatus($snippet, $status)) {
            fn_set_notification('N', __('notice'), __('status_changed'));
            $ajax->assign('success', 1);
        } else {
            $ajax->assign('return_status', $current_status);
            $ajax->assign('success', 0);
        }
    }

    if ($mode === 'update') {
        fn_trusted_vars('snippet');

        /** @var \Tygh\Template\Snippet\Service $service */
        $service = Tygh::$app['template.snippet.service'];
        /** @var \Tygh\Template\Snippet\Repository $repository */
        $repository = Tygh::$app['template.snippet.repository'];
        /** @var \Tygh\Ajax $ajax */
        $ajax = Tygh::$app['ajax'];

        $snippet_id = isset($_REQUEST['snippet_id']) ? (int) $_REQUEST['snippet_id'] : 0;
        $data = (array) $_REQUEST['snippet'];

        if ($snippet_id) {
            $snippet = $repository->findById($snippet_id);

            if (empty($snippet)) {
                return array(CONTROLLER_STATUS_NO_PAGE);
            }

            $data = $service->filterData($data, array('name', 'template', 'status'));

            $result = $service->updateSnippet($snippet, $data);
        } else {
            $data = $service->filterData($data, array('code', 'name', 'template', 'status', 'type', 'addon'));

            $result = $service->createSnippet($data);
        }

        if ($result->isSuccess()) {
            if (!empty($_REQUEST['return_url'])) {
                return array(CONTROLLER_STATUS_OK, $_REQUEST['return_url']);
            }
        } else {
            $result->showNotifications();
            $ajax->assign('failed_request', true);
        }

        exit;
    }

    if ($mode === 'delete') {
        /** @var \Tygh\Template\Snippet\Service $service */
        $service = Tygh::$app['template.snippet.service'];
        /** @var \Tygh\Template\Snippet\Repository $repository */
        $repository = Tygh::$app['template.snippet.repository'];

        $snippet_ids = isset($_REQUEST['snippet_ids']) ? (array) $_REQUEST['snippet_ids'] : array();

        if ($snippet_ids) {
            $snippets = $repository->findByIds($snippet_ids);

            foreach ($snippets as $snippet) {
                $service->removeSnippet($snippet);
            }

            fn_set_notification('N', __('notice'), __('snippets_have_been_deleted'));
        }

        if (!empty($_REQUEST['return_url'])) {
            return array(CONTROLLER_STATUS_REDIRECT, $_REQUEST['return_url']);
        }
    }

    if ($mode === 'restore') {
        /** @var \Tygh\Template\Snippet\Service $service */
        $service = Tygh::$app['template.snippet.service'];
        /** @var \Tygh\Template\Snippet\Repository $repository */
        $repository = Tygh::$app['template.snippet.repository'];

        $snippet_id = isset($_REQUEST['snippet_id']) ? (int) $_REQUEST['snippet_id'] : 0;
        $snippet = $repository->findById($snippet_id);

        if (empty($snippet)) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        if ($service->restoreTemplate($snippet)) {
            fn_set_notification('N', __('notice'), __('text_changes_saved'));
        }
    }

    if ($mode === 'update_table_column') {
        fn_trusted_vars('column');

        /** @var \Tygh\Template\Snippet\Repository $snippet_repository */
        $snippet_repository = Tygh::$app['template.snippet.repository'];
        /** @var \Tygh\Template\Snippet\Table\ColumnRepository $column_repository */
        $column_repository = Tygh::$app['template.snippet.table.column_repository'];
        /** @var \Tygh\Template\Snippet\Table\ColumnService $column_service */
        $column_service = Tygh::$app['template.snippet.table.column_service'];

        /** @var \Tygh\Template\Renderer $renderer */
        $renderer = Tygh::$app['template.renderer'];
        /** @var \Tygh\Ajax $ajax */
        $ajax = Tygh::$app['ajax'];

        $data = (array) $_REQUEST['column'];
        $snippet_id = (int) $_REQUEST['snippet_id'];
        $column_id = isset($_REQUEST['column_id']) ? (int) $_REQUEST['column_id'] : 0;

        $snippet = $snippet_repository->findById($snippet_id);

        if (empty($snippet)) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        if ($column_id) {
            $column = $column_repository->findById($column_id);

            if (empty($column)) {
                return array(CONTROLLER_STATUS_NO_PAGE);
            }

            $result = $column_service->updateColumn($column, $data);
        } else {
            $data['snippet_type'] = $snippet->getType();
            $data['snippet_code'] = $snippet->getCode();
            $data['addon'] = $snippet->getAddon();

            $result = $column_service->createColumn($data);
        }

        if ($result->isSuccess()) {
            if (!empty($_REQUEST['return_url'])) {
                return array(CONTROLLER_STATUS_OK, $_REQUEST['return_url']);
            }
        } else {
            $result->showNotifications();
            $ajax->assign('failed_request', true);
        }

        exit;
    }

    if ($mode === 'delete_table_column') {
        /** @var \Tygh\Template\Snippet\Table\ColumnRepository $column_repository */
        $column_repository = Tygh::$app['template.snippet.table.column_repository'];
        /** @var \Tygh\Template\Snippet\Table\ColumnService $column_service */
        $column_service = Tygh::$app['template.snippet.table.column_service'];

        $column_id = isset($_REQUEST['column_id']) ? (int) $_REQUEST['column_id'] : 0;
        $column = $column_repository->findById($column_id);

        if (empty($column)) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        $column_service->removeColumn($column);

        fn_set_notification('N', __('notice'), __('table_column_has_been_deleted'));
    }

    if ($mode === 'restore_table_column') {
        $column_id = isset($_REQUEST['column_id']) ? (int) $_REQUEST['column_id'] : 0;
        /** @var \Tygh\Template\Snippet\Table\ColumnRepository $column_repository */
        $column_repository = Tygh::$app['template.snippet.table.column_repository'];
        /** @var \Tygh\Template\Snippet\Table\ColumnService $column_service */
        $column_service = Tygh::$app['template.snippet.table.column_service'];

        $column = $column_repository->findById($column_id);

        if (empty($column)) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        if ($column_service->restoreTemplate($column)) {
            fn_set_notification('N', __('notice'), __('text_changes_saved'));
        }
    }

    if (!empty($_REQUEST['return_url'])) {
        return array(CONTROLLER_STATUS_REDIRECT, $_REQUEST['return_url']);
    }

    return array(CONTROLLER_STATUS_OK);
}


if ($mode === 'update') {
    /** @var \Tygh\Template\Snippet\Service $service */
    $service = Tygh::$app['template.snippet.service'];
    /** @var \Tygh\Template\Snippet\Repository $repository */
    $repository = Tygh::$app['template.snippet.repository'];
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $snippet_id = isset($_REQUEST['snippet_id']) ? (int) $_REQUEST['snippet_id'] : 0;

    if ($snippet_id) {
        $snippet = $repository->findById($snippet_id);

        if (!$snippet) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }
    } else {
        $snippet = new Snippet();

        if (isset($_REQUEST['type'])) {
            $snippet->setType($_REQUEST['type']);
            $view->assign('type', $_REQUEST['type']);
        }

        if (isset($_REQUEST['addon'])) {
            $snippet->setAddon($_REQUEST['addon']);
        }
    }

    $view->assign('snippet', $snippet);

    if (!empty($_REQUEST['return_url'])) {
        $view->assign('return_url', $_REQUEST['return_url']);
    }

    if (!empty($_REQUEST['result_ids'])) {
        $view->assign('result_ids', $_REQUEST['result_ids']);
    }

    if (!empty($_REQUEST['current_result_ids'])) {
        $view->assign('result_ids', $_REQUEST['current_result_ids']);
    }

    $tabs = array(
        'snippet_general' => array(
            'title' => __('general'),
            'js' => true,
        )
    );

    Registry::set('navigation.tabs', $tabs);
}

if ($mode === 'update_table_column') {
    /** @var \Tygh\Template\Snippet\Table\ColumnRepository $column_repository */
    $column_repository = Tygh::$app['template.snippet.table.column_repository'];
    /** @var \Tygh\Template\Snippet\Repository $snippet_repository */
    $snippet_repository = Tygh::$app['template.snippet.repository'];
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $column_id = isset($_REQUEST['column_id']) ? (int) $_REQUEST['column_id'] : 0;

    if ($column_id) {
        $column = $column_repository->findById($column_id);

        if ($column) {
            $snippet_type = $column->getSnippetType();
            $snippet_code = $column->getSnippetCode();

            $snippet = $snippet_repository->findByTypeAndCode($snippet_type, $snippet_code);
        } else {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }
    } else {
        $snippet_id = isset($_REQUEST['snippet_id']) ? (int) $_REQUEST['snippet_id'] : 0;

        $snippet = $snippet_repository->findById($snippet_id);

        if (!$snippet) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        $column = new Column();
        $column->setSnippetCode($snippet->getCode());
        $column->setSnippetType($snippet->getType());
        $column->setAddon($snippet->getAddon());
    }

    $snippet_id = $snippet->getId();
    $variable_schema = $snippet->getParam('variable_schema', $snippet->getType() . '_' . $snippet->getCode());

    /** @var \Tygh\Template\VariableCollectionFactory $collection_factory */
    $collection_factory = Tygh::$app['template.variable_collection_factory'];
    $collection = $collection_factory->createMetaDataCollection('snippets', $variable_schema);

    $view->assign('column', $column);
    $view->assign('snippet_id', $snippet_id);
    $view->assign('snippet', $snippet);
    $view->assign('variables', $collection->getAll());

    if (!empty($_REQUEST['return_url'])) {
        $view->assign('return_url', $_REQUEST['return_url']);
    }

    if (!empty($_REQUEST['result_ids'])) {
        $view->assign('result_ids', $_REQUEST['result_ids']);
    }

    if (!empty($_REQUEST['current_result_ids'])) {
        $view->assign('result_ids', $_REQUEST['current_result_ids']);
    }

    $tabs = array(
        'column_general' => array(
            'title' => __('general'),
            'js' => true,
        )
    );

    Registry::set('navigation.tabs', $tabs);
}