<?php

use Tygh\Embedded;
use Tygh\Registry;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
function smarty_block_styles($params, $content, &$smarty, &$repeat)
{
    if ($repeat == true) {
        return;
    }

    $prepend_prefix = Embedded::isEnabled() ? 'html#tygh_html body#tygh_body .tygh' : '';
    $current_location = Registry::get('config.current_location');

    $styles = array();
    $inline_styles = '';
    $external_styles = array();

    //if (preg_match_all('/\<link(.*?href ?= ?"([^"]+)")?[^\>]*\>/is', $content, $m)) {
    if (preg_match_all('/\<link(.*?href\s?=\s?(?:"|\')([^"]+)(?:"|\'))?[^\>]*\>/is', $content, $m)) {
        foreach ($m[2] as $k => $v) {
            $v = preg_replace('/\?.*?$/', '', $v);
            $media = '';
            if (strpos($m[1][$k], 'media=') !== false && preg_match('/media="(.*?)"/', $m[1][$k], $_m)) {
                $media = $_m[1];
            }

            if (strpos($v, $current_location) === false || strpos($m[1][$k], 'data-ca-external') !== false) {
                // Location is different OR style is skipped for minification
                $external_styles[] = str_replace(' data-ca-external="Y"', '', $m[0][$k]);
            } else {
                $styles[] = array(
                    'file' => str_replace($current_location, Registry::get('config.dir.root'), $v),
                    'relative' => str_replace($current_location . '/', '', $v),
                    'media' => $media
                );
            }
        }
    }

    if (preg_match_all('/\<style.*\>(.*)\<\/style\>/isU', $content, $m)) {
        $inline_styles = implode("\n\n", $m[1]);
    }

    if (!empty($styles) || !empty($inline_styles)) {
        fn_set_hook('styles_block_files', $styles);

        list($_area) = Tygh::$app['view']->getArea();
        $params['compressed'] = true;
        $filename = fn_merge_styles($styles, $inline_styles, $prepend_prefix, $params, $_area);

        $content = '<link type="text/css" rel="stylesheet" href="' . $filename . '" />';

        if (!empty($external_styles)) {
            $content .= PHP_EOL . implode(PHP_EOL, $external_styles);
        }
    }

    return $content;
}
