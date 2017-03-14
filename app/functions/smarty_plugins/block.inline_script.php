<?php

use Tygh\Registry;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
function smarty_block_inline_script($params, $content, &$smarty, &$repeat)
{
    static $data = array();
    if ($repeat == true) {
        return;
    }

    if (defined('AJAX_REQUEST') || Registry::get('runtime.inside_scripts')) {
        return $content;
    }

    $return = '';
    if (empty($params['output'])) {
        $data[] = $content;
    } else {
        foreach ($data as $script) {
            $return .= $script . "\n";
        }
    }

    return $return;
}
