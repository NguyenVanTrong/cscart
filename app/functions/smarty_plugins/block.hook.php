<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

use Tygh\Registry;

function smarty_block_hook($params, $content, &$smarty)
{
    static $overrides = array();
    $hook_content = '';
    $hook_name = 'thooks_' . $smarty->template_area;

    Registry::registerCache($hook_name, array('addons'), Registry::cacheLevel('static'));
    $hooks = Registry::ifGet($hook_name, array());

    if (!isset($hooks[$params['name']])) {
        list($dir, $name) = explode(':', $params['name']);

        $hooks_list = array(
            'pre' => array(),
            'post' => array(),
            'override' => array()
        );

        foreach (Registry::get('addons') as $addon => $data) {

            if ($data['status'] == 'D') {
                continue;
            }

            $files = array();

            foreach (Registry::get('addons') as $_addon => $_data) {
                if ($_data['status'] == 'D' || $_addon == $addon) {
                    continue;
                }

                $files[] = 'addons/' . $addon . '/addons/' . $_addon . '/hooks/' . $dir . '/' . $name;
            }

            $files[] = 'addons/' . $addon . '/hooks/' . $dir . '/' . $name;

            foreach ($files as $file) {

                if ($smarty->templateExists($file . '.pre.tpl')) {
                    $hooks_list['pre'][] = $file . '.pre.tpl';
                }
                if ($smarty->templateExists($file . '.post.tpl')) {
                    $hooks_list['post'][] = $file . '.post.tpl';
                }
                if ($smarty->templateExists($file . '.override.tpl')) {
                    $hooks_list['override'][] = $file . '.override.tpl';
                }
            }
        }

        if (fn_is_empty($hooks_list)) {
            $hooks[$params['name']] = array();
        } else {
            $hooks[$params['name']] = $hooks_list;
        }

        Registry::set($hook_name, $hooks);
    }

    if (is_null($content)) {
        // reset override for current hook
        $overrides[$params['name']] = false;

        // override hook should be call for opened tag to prevent pre/post hook execution
        if (!empty($hooks[$params['name']]['override'])) {
            $override_content = '';
            foreach ($hooks[$params['name']]['override'] as $tpl) {
                if ($tpl == $smarty->template_resource) {
                    continue;
                }

                $_hook_content = $smarty->fetch($tpl);
                if (trim($_hook_content)) {
                    $overrides[$params['name']] = true;

                    $hook_content = $_hook_content;
                }
            }
        }

        // prehook should be called for the opening {hook} tag to allow variables passed from hook to body
        if (empty($overrides[$params['name']])) {
            if (!empty($hooks[$params['name']]['pre'])) {
                foreach ($hooks[$params['name']]['pre'] as $tpl) {
                    $hook_content .= $smarty->fetch($tpl);
                }
            }
        }

    } else {
        // post hook should be called only if override hook was no executed
        if (empty($overrides[$params['name']])) {
            if (!empty($hooks[$params['name']]['post'])) {
                foreach ($hooks[$params['name']]['post'] as $tpl) {
                    $hook_content .= $smarty->fetch($tpl);
                }
            }

            $hook_content =  $content . "\n" . $hook_content;
        }
    }

    fn_set_hook('smarty_block_hook_post', $params, $content, $overrides, $smarty, $hook_content);

    return $hook_content;
}
