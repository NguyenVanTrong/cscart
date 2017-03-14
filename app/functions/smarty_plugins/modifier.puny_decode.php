<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier<br>
 * Name:     puny_decode<br>
 * Purpose:  Decode the domain from Punycode and return the URL.
 * Example:  {$url|puny_decode}
 * -------------------------------------------------------------
 */

function smarty_modifier_puny_decode($url)
{
    return Tygh\Tools\Url::decode($url, true);
}

/* vim: set expandtab: */
