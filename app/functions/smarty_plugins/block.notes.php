<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_block_notes($params, $content, &$smarty, $start)
{
    static $notes = array();
    if (empty($start)) {
        if (!empty($params['assign'])) {
            $smarty->assign($params['assign'], $notes, false);
        } elseif (!empty($params['clear'])) {
            $notes = array();
        } else {
            $key = empty($params['title']) ? '_note_': $params['title'];

            if (!empty($params['unique']) && !empty($notes[$key])) {
                return;
            }

            if (!isset($notes[$key])) {
                $notes[$key] = '';
            }
            $notes[$key] .= $content;
        }
    }
}
