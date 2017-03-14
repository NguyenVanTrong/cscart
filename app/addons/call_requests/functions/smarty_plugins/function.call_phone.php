<?php

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_function_call_phone($params, &$smarty)
{
    $phone = fn_call_requests_get_splited_phone();

    return '<span><span class="ty-cr-phone-prefix">' . $phone['prefix'] . '</span>' . $phone['postfix'] . '</span>';
}
