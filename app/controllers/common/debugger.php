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
use Tygh\Debugger;

if (!defined('BOOTSTRAP') || !Debugger::isActive()) {
    die('Access denied');
}

$data = !empty($_REQUEST['debugger_hash']) ? Debugger::getData($_REQUEST['debugger_hash']) : array();

if ($mode == 'sql_parse') {

    fn_trusted_vars('query');

    if (!empty($data) && isset($_REQUEST['sql_id'])) {
        $query = stripslashes($data['sql']['queries'][$_REQUEST['sql_id']]['query']);
        $backtrace = !empty($data['backtraces']) ? $data['backtraces'][$_REQUEST['sql_id']] : array();
        $_REQUEST['sandbox'] = true;
    } else {
        $query = $_REQUEST['query'];
    }

    $result = $explain = array();
    $query_time = $start_time = 0;

    if (!empty($_REQUEST['sandbox'])) {
        db_query('SET AUTOCOMMIT=0');
        db_query('START TRANSACTION');
    }

    $stop_queries = array('DROP', 'CREATE', 'TRANSACTION', 'ROLLBACK');
    $stop_exec = false;
    foreach ($stop_queries as $stop_query) {
        if (stripos(trim($query), $stop_query) !== false) {
            $result = false;
            $stop_exec = true;
            break;
        }
    }

    if (!$stop_exec) {
        Tygh::$app['db']->raw = true;

        $time_start = microtime(true);

        if (stripos(trim($query), 'SELECT') !== false) {
            $result = db_get_array($query);
            $result_columns = !empty($result[0]) ? array_keys($result[0]) : array();
        } else {
            $result = db_query($query);
        }

        $query_time = microtime(true) - $time_start;
    }

    if (strpos($query, 'SELECT') === 0) {
        $json_explain_supported = (Tygh::$app['db']->getServerVersion() > 50600);
        if ($json_explain_supported) {
            $explain = db_get_field('EXPLAIN FORMAT=JSON ' . $query);
        } else {
            $explain = db_get_array('EXPLAIN ' . $query);
        }
    }

    if (!empty($_REQUEST['sandbox'])) {
        db_query('ROLLBACK');
    }

    if (!$stop_exec) {
        $query = SqlFormatter::format($query, true);
    }

    if ($stop_exec) {
        Tygh::$app['view']->assign('stop_exec', $stop_exec);
    }
    if (!empty($query_time)) {
        Tygh::$app['view']->assign('query_time', sprintf('%.5f', $query_time));
    }
    if (!empty($query)) {
        Tygh::$app['view']->assign('query', $query);
    }
    if (!empty($explain)) {
        Tygh::$app['view']->assign(
            $json_explain_supported ? 'json_explain' : 'explain',
            $explain
        );
    }
    if (isset($result)) {
        Tygh::$app['view']->assign('result', $result);
    }
    if (!empty($result_columns)) {
        Tygh::$app['view']->assign('result_columns', $result_columns);
    }
    if (!empty($backtrace)) {
        Tygh::$app['view']->assign('backtrace', $backtrace, false);
    }
    Tygh::$app['view']->display('backend:views/debugger/components/sql_parse.tpl');
    exit();

} elseif ($mode == 'server') {

    Tygh::$app['view']->display('backend:views/debugger/components/server_tab.tpl');
    exit();

} elseif ($mode == 'request') {

    if (!empty($data['request'])) {
        Tygh::$app['view']->assign('data', $data['request']);
        Tygh::$app['view']->assign('debugger_hash', $_REQUEST['debugger_hash']);
        Tygh::$app['view']->display('backend:views/debugger/components/request_tab.tpl');
    }
    exit();

} elseif ($mode == 'config') {

    if (!empty($data['config'])) {
        Tygh::$app['view']->assign('data', $data['config']);
        Tygh::$app['view']->display('backend:views/debugger/components/config_tab.tpl');
    }
    exit();

} elseif ($mode == 'sql') {
    if (empty($data['sql'])) {
        exit;
    }
    $sql_data = array(
        'totals' => $data['sql']['totals'],
        'list' => $data['sql']['queries'],
        'count' => array(),
    );

    $_REQUEST['order_by'] = isset($_REQUEST['order_by']) ? $_REQUEST['order_by'] : 'number,asc';
    list($order_by, $direction) = explode(',', $_REQUEST['order_by']);

    $sql_data['list'] = Debugger::sortQueries($sql_data['list'], $order_by, $direction);

    foreach ($sql_data['list'] as $query_id => $sql_query) {
        $query_hash = md5($sql_query['query']);

        if (empty($sql_data['count'][$query_hash])) {
            $sql_data['count'][$query_hash] = array(
                'query' => $sql_query['query'],
                'total_time' => 0,
                'count_time' => 0,
                'count' => 0,
            );
        }

        $sql_data['count'][$query_hash]['total_time'] += $sql_query['time'];
        $sql_data['count'][$query_hash]['count_time']++;
        $sql_data['count'][$query_hash]['count']++;
        if ($sql_data['count'][$query_hash]['count'] > $sql_data['totals']['rcount']) {
            $sql_data['totals']['rcount'] = $sql_data['count'][$query_hash]['count'];
        }
        if (!isset($sql_data['count'][$query_hash]['min_time']) || $sql_data['count'][$query_hash]['max_time'] < $sql_query['time']) {
            $sql_data['count'][$query_hash]['max_time'] = $sql_query['time'];
        }
        if (!isset($sql_data['count'][$query_hash]['min_time']) || $sql_data['count'][$query_hash]['min_time'] > $sql_query['time']) {
            $sql_data['count'][$query_hash]['min_time'] = $sql_query['time'];
        }
    }
    // descending sort
    usort($sql_data['count'], function($query1, $query2){
        if ($query1['count'] > $query2['count']) {
            return -1;
        } elseif ($query2['count'] > $query1['count']) {
            return 1;
        }

        return 0;
    });

    Tygh::$app['view']
        ->assign('direction', $direction)
        ->assign('order_by', $order_by)
        ->assign('medium_query_time', Debugger::MEDIUM_QUERY_TIME)
        ->assign('long_query_time', Debugger::LONG_QUERY_TIME)
        ->assign('data', $sql_data)
        ->assign('debugger_hash', $_REQUEST['debugger_hash'])
        ->display('backend:views/debugger/components/sql_tab.tpl');

} elseif ($mode == 'cache_queries') {

    $_REQUEST['order_by'] = isset($_REQUEST['order_by']) ? $_REQUEST['order_by'] : 'number,asc';
    list($order_by, $direction) = explode(',', $_REQUEST['order_by']);

    if (!empty($data['cache_queries'])) {
        $query_data = array(
            'totals' => $data['cache_queries']['totals'],
            'list' => $data['cache_queries']['queries'],
            'count' => array(),
        );

        $query_data['list'] = Debugger::sortQueries($query_data['list'], $order_by, $direction);

        foreach ($query_data['list'] as $query) {
            if (empty($query_data['count'][md5($query['query'])])) {
                $query_data['count'][md5($query['query'])] = array(
                    'query' => $query['query'],
                    'total_time' => 0,
                    'count_time' => 0,
                    'count' => 0,
                );
            }
            $query_data['count'][md5($query['query'])]['total_time'] += $query['time'];
            $query_data['count'][md5($query['query'])]['count_time']++;
            $query_data['count'][md5($query['query'])]['count']++;
            if ($query_data['count'][md5($query['query'])]['count'] > $query_data['totals']['rcount']) {
                $query_data['totals']['rcount'] = $query_data['count'][md5($query['query'])]['count'];
            }
            if (!isset($query_data['count'][md5($query['query'])]['min_time']) || $query_data['count'][md5($query['query'])]['max_time'] < $query['time']) {
                $query_data['count'][md5($query['query'])]['max_time'] = $query['time'];
            }
            if (!isset($query_data['count'][md5($query['query'])]['min_time']) || $query_data['count'][md5($query['query'])]['min_time'] > $query['time']) {
                $query_data['count'][md5($query['query'])]['min_time'] = $query['time'];
            }
        }

        Tygh::$app['view']
            ->assign('order_by', $order_by)
            ->assign('direction', $direction)
            ->assign('medium_query_time', Debugger::CACHE_MEDIUM_QUERY_TIME)
            ->assign('long_query_time', Debugger::CACHE_LONG_QUERY_TIME)
            ->assign('data', $query_data)
            ->assign('debugger_hash', $_REQUEST['debugger_hash'])
            ->display('backend:views/debugger/components/cache_queries_tab.tpl');
    }
    exit();

} elseif ($mode == 'logging') {

    if (!empty($data['logging'])) {
        Tygh::$app['view']->assign('data', $data['logging']);
        Tygh::$app['view']->assign('debugger_hash', $_REQUEST['debugger_hash']);
        Tygh::$app['view']->display('backend:views/debugger/components/logging_tab.tpl');
    }
    exit();

} elseif ($mode == 'templates') {

    if (!empty($data['templates'])) {
        $data['templates']['tpls'] = Debugger::parseTplsList($data['templates']['tpls'], 0);

        Tygh::$app['view']->assign('data', $data['templates']);
        Tygh::$app['view']->assign('debugger_hash', $_REQUEST['debugger_hash']);
        Tygh::$app['view']->display('backend:views/debugger/components/templates_tab.tpl');
    }
    exit();

} elseif ($mode == 'blocks') {
    if (!empty($data['blocks'])) {
        $blocks_rendered = array_filter($data['blocks'], function ($block) {
            return !$block['render_performance']['found_at_cache'];
        });
        $blocks_from_cache = array_filter($data['blocks'], function ($block) {
            return $block['render_performance']['found_at_cache'];
        });
        Tygh::$app['view']->assign('blocks_rendered', $blocks_rendered);
        Tygh::$app['view']->assign('blocks_from_cache', $blocks_from_cache);
        Tygh::$app['view']->assign('debugger_hash', $_REQUEST['debugger_hash']);
        Tygh::$app['view']->display('backend:views/debugger/components/blocks_tab.tpl');
    }
    exit;

} elseif ($mode == 'quit') {
    Debugger::quit();
    fn_clear_cache('registry');

    return array(CONTROLLER_STATUS_REDIRECT, fn_query_remove($_REQUEST['redirect_url'], Registry::get('config.debugger_token')));
}

exit;
