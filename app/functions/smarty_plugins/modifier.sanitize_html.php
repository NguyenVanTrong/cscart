<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     sanitize_html
 * Purpose:  sanitizes HTML from any XSS code.
 * -------------------------------------------------------------
 */
function smarty_modifier_sanitize_html($text)
{
    return \Tygh\Tools\SecurityHelper::sanitizeHtml($text);
}
