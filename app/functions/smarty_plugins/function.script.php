<?php

use Tygh\Registry;
use Tygh\Themes\Themes;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_function_script($params, &$template)
{
    static $scripts = array();

    if (!isset($scripts[$params['src']])) {
        if (strpos($params['src'], '//') === false) {
            // load missing js from the parent theme
            if ($file = $template->smarty->theme->getContentPath(DIR_ROOT . '/' . $params['src'])) {
                $params['src'] = $file[Themes::PATH_RELATIVE];
            }
            $src = Registry::get('config.current_location') . '/' . fn_link_attach($params['src'], 'ver=' . PRODUCT_VERSION);
        } else {
            $src = $params['src'];
        }

        $scripts[$params['src']] = '<script type="text/javascript"'
                                    . (!empty($params['class']) ? ' class="' . $params['class'] . '" ' : '')
                                    . ' src="' . $src . '" ' . (isset($params['charset']) ? ('charset="' . $params['charset'] . '"') : '') . (isset($params['escape']) ? '><\/script>' : '></script>');

        if (defined('AJAX_REQUEST') || Registry::get('runtime.inside_scripts')) {
            return $scripts[$params['src']];
        } else {

            if (isset($params['no-defer']) && $params['no-defer']) {
                return $scripts[$params['src']];
            } else {
                $cache_name = $template->getTemplateVars('block_cache_name');
                if (!empty($cache_name)) {
                    $cached_content = Registry::get($cache_name);
                    if (!isset($cached_content['javascript'])) {
                        $cached_content['javascript'] = '';
                    }
                    $cached_content['javascript'] .= $scripts[$params['src']];

                    Registry::set($cache_name, $cached_content, true);
                }
                $repeat = false;
                $template->loadPlugin('smarty_block_inline_script');
                smarty_block_inline_script(array(), $scripts[$params['src']], $template, $repeat);

                return '<!-- Inline script moved to the bottom of the page -->';
            }

        }
    }
}
