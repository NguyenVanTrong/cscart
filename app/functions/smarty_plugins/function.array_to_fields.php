<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
use Tygh\Tools\SecurityHelper;

/**
 * Converts array to html hidden fields
 *
 * Type:     function<br>
 * Name:     array_to_fields<br>
 * @param array $param params list
 * @return object $template template object
 */
function smarty_function_array_to_fields($params, &$template)
{
    $result = '';
    $pattern = '<input type="hidden" name="%s" value="%s" />' . "\n";
    foreach ($params['data'] as $name => $value) {
        if (empty($value)) {
            continue;
        }

        if (!empty($params['skip']) && in_array($name, $params['skip'])) {
            continue;
        }

        if (!empty($params['escape']) && in_array($name, $params['escape'])) {
            if (is_array($value)) {
                foreach ($value as $index => &$data) {
                    $data = SecurityHelper::escapeHtml($data);
                }
            } else {
                $value = SecurityHelper::escapeHtml($value);
            }
        }

        if (is_array($value)) {
            foreach ($value as $index => $data) {
                $result .= sprintf($pattern, $name . '[' . $index . ']', $data);
            }
        } else {
            $result .= sprintf($pattern, $name, $value);
        }
    }

    return $result;
}

/* vim: set expandtab: */
