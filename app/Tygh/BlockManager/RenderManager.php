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

namespace Tygh\BlockManager;

use Tygh\Debugger;
use Tygh\Development;
use Tygh\Embedded;
use Tygh\Registry;
use Tygh\SmartyEngine\Core;

class RenderManager
{
    const ADMIN = 'admin';
    const CUSTOMER = 'customer';

    /**
     * Current rendered location data
     * @var array Location data
     */
    private $_location;

    /**
     * Containers from current rendered location
     * @var array List of containers data
     */
    private $_containers;

    /**
     * Grids from current rendered location
     * @var array List of grids data
     */
    private $_grids;

    /**
     * Blocks from current rendered location
     * @var array List of blocks data
     */
    private $_blocks;

    /**
     * Current rendered area
     * @var string Current rendered area
     */
    private $_area;

    /**
     * Link to global Smarty object
     * @var Core Link to global Smarty object
     */
    private $_view;

    /**
     * Current theme name
     * @var string Current theme name
     */
    private $_theme;

    /**
     * @var array|bool
     */
    private $_dynamic_object_scheme;

    /**
     * @var array
     */
    private $_parent_grid;

    /**
     * Rendered block content
     * @var array Container
     */
    private $_rendered_blocks;

    /**
     * Loads location data, containers, grids and blocks
     *
     * @param string $dispatch       URL dispatch (controller.mode.action)
     * @param string $area           Area ('A' for admin or 'C' for custom
     * @param array  $dynamic_object
     * @param int    $location_id
     * @param string $lang_code      2 letters language code
     */
    public function __construct($dispatch, $area, $dynamic_object = array(), $location_id = 0, $lang_code = DESCR_SL)
    {
        Debugger::checkpoint('Start render location');
        // Try to get location for this dispatch
        if ($location_id > 0) {
            $this->_location = Location::instance()->getById($location_id, $lang_code);
        } else {
            $this->_location = Location::instance()->get($dispatch, $dynamic_object, $lang_code);
        }

        $this->_area = $area;

        if (!empty($this->_location)) {
            if (isset($dynamic_object['object_id']) && $dynamic_object['object_id'] > 0) {
                $this->_containers = Container::getListByArea($this->_location['location_id'], 'C');
            } else {
                $this->_containers = Container::getListByArea($this->_location['location_id'], $this->_area);
            }

            $this->_grids = Grid::getList(array(
                'container_ids' => Container::getIds($this->_containers)
            ));

            $blocks = Block::instance()->getList(
                array('?:bm_snapping.*','?:bm_blocks.*', '?:bm_blocks_descriptions.*'),
                Grid::getIds($this->_grids),
                $dynamic_object,
                null,
                null,
                $lang_code
            );

            $this->_blocks = $blocks;

            $this->_view = \Tygh::$app['view'];
            $this->_theme = self::_getThemePath($this->_area);
            $this->_dynamic_object_scheme = SchemesManager::getDynamicObject($this->_location['dispatch'], 'C');
        }
    }

    /**
     * Renders current location
     * @return string HTML code of rendered location
     */
    public function render()
    {
        if (!empty($this->_location)) {

            $this->_view->assign('containers', array(
                'top_panel' => $this->_renderContainer($this->_containers['TOP_PANEL']),
                'header' => $this->_renderContainer($this->_containers['HEADER']),
                'content' => $this->_renderContainer($this->_containers['CONTENT']),
                'footer' => $this->_renderContainer($this->_containers['FOOTER']),
            ));

            Debugger::checkpoint('End render location');

            return $this->_view->fetch($this->_theme . 'location.tpl');
        } else {
            return '';
        }
    }

    /**
     * Renders container
     * @param  array  $container Container data to be rendered
     * @return string HTML code of rendered container
     */
    private function _renderContainer($container)
    {
        static $layout_width = 0;
        if (empty($layout_width)) {
            $layout_width = Registry::get('runtime.layout.width');
        }

        $content = '';
        $container['width'] = $layout_width;

        $this->_view->assign('container', $container);

        if (isset($this->_grids[$container['container_id']]) && ($this->_area == 'A' || $container['status'] != 'D')) {
            $grids = $this->_grids[$container['container_id']];
            $grids = fn_build_hierarchic_tree($grids, 'grid_id');
            $grids = $this->sortGrids($grids);

            $this->_parent_grid = array();
            $content = $this->renderGrids($grids);

            $this->_view->assign('content', $content);

            // Cleanup old blocks content to avoid extra memory using
            $this->_rendered_blocks = array();

            return $this->_view->fetch($this->_theme . 'container.tpl');

        } else {
            $this->_view->assign('content', '');

            if ($this->_area == 'A') {
                return $this->_view->fetch($this->_theme . 'container.tpl');
            }
        }

    }

    /**
     * Renders given list of grids.
     *
     * @param array $grids List of grids to render indexed by grid ID.
     *
     * @return string Rendered content of the grids.
     */
    protected function renderGrids($grids)
    {
        $grids_content = array();
        foreach ($grids as $index => $grid) {
            $grids_content[$index] = trim($this->renderGrid($grid));
        }

        if ($this->_area != 'A') {
            $grids = $this->recalculateGridsBoundingBox($grids, $grids_content);
        }

        foreach ($grids as $index => $grid) {
            $this->_view->assign('content', $grids_content[$index]);
            $this->_view->assign('parent_grid', $this->_parent_grid);
            $this->_view->assign('grid', $grid);

            $grids_content[$index] = $this->_view->fetch($this->_theme . 'grid.tpl');
        }

        return implode('', $grids_content);
    }

    /**
     * This method recalculates width and alpha/omega status for each grid from given list after they have been rendered.
     *
     * This is required in order to empty grids space was filled by neighbour grid contents. Neighbour grid will be enlarged.
     *
     * Alpha status stands for the grid is the first in row. Omega status stands for the grid is the last in row.
     * If grid have neither alpha nor omega status, it means it is located between the first in row and the last in row.
     *
     * @param array $grids         List of grids indexed by grid ID.
     * @param array $grids_content List of rendered grid contents indexed by grid ID.
     *
     * @return array Modified grid list
     */
    protected function recalculateGridsBoundingBox($grids, $grids_content)
    {
        $next_grid_overrides = array(
            'width' => 0,
            'alpha' => 0,
        );
        foreach ($grids as $index => &$grid) {
            if (!empty($grid['fluid_width'])) {
                $grid['fluid_width'] += $next_grid_overrides['width'];
            }
            if (!empty($grid['width'])) {
                $grid['width'] += $next_grid_overrides['width'];
            }
            if (!empty($next_grid_overrides['alpha'])) {
                $grid['alpha'] = $next_grid_overrides['alpha'];
            }

            $next_grid_overrides = array(
                'width' => 0,
                'alpha' => 0,
            );

            // Found empty first-in-row (alpha only) or in-the-middle (non-alpha and non-omega) grid.
            // Its width will be added to the next grid and its alpha status will be assigned to the next grid.
            if (empty($grids_content[$index])
                && (($grid['alpha'] && !$grid['omega']) || (!$grid['alpha'] && !$grid['omega']))
            ) {
                $next_grid_overrides['width'] = empty($grid['fluid_width']) ? $grid['width'] : $grid['fluid_width'];
                $next_grid_overrides['alpha'] = $grid['alpha'];
            }
        }

        // Reverse grids list and do the same for omega-only grids.
        $grids = array_reverse($grids, true);

        $prev_grid_overrides = array(
            'width' => 0,
            'omega' => 0
        );
        foreach ($grids as $index => &$grid) {
            if (!empty($grid['fluid_width'])) {
                $grid['fluid_width'] += $prev_grid_overrides['width'];
            }
            if (!empty($grid['width'])) {
                $grid['width'] += $prev_grid_overrides['width'];
            }
            if (!empty($prev_grid_overrides['omega'])) {
                $grid['omega'] = $prev_grid_overrides['omega'];
            }

            $prev_grid_overrides = array(
                'width' => 0,
                'omega' => 0
            );

            // Found empty last-in-row (omega only) grid.
            // Its width will be added to previous grid and its omega status will be assigned to previous grid.
            if (empty($grids_content[$index]) && ($grid['omega'] && !$grid['alpha'])) {
                $prev_grid_overrides['width'] = empty($grid['fluid_width']) ? $grid['width'] : $grid['fluid_width'];
                $prev_grid_overrides['omega'] = $grid['omega'];
            }
        }

        // Return the normal order of grids list
        $grids = array_reverse($grids, true);

        return $grids;
    }

    /**
     * Renders grid
     *
     * @param array $grid Grid data to be rendered
     *
     * @return string HTML code of rendered grid
     */
    protected function renderGrid($grid)
    {
        $content = '';

        if ($this->_area == 'A' || $grid['status'] != 'D') {
            if (isset($grid['children']) && !empty($grid['children'])) {
                $grid['children'] = fn_sort_array_by_key($grid['children'], 'grid_id');
                $grid['children'] = self::sortGrids($grid['children']);

                $parent_grid = $this->_parent_grid;
                $this->_parent_grid = $grid;

                $content = $this->renderGrids($grid['children']);

                $this->_parent_grid = $parent_grid;
            } else {
                $content = $this->renderBlocks($grid);
            }
        }

        return $content;
    }

    /**
     * Renders blocks in grid
     * @param  array  $grid Grid data
     * @return string HTML code of rendered blocks
     */
    public function renderBlocks($grid)
    {
        $content = '';

        if (isset($this->_blocks[$grid['grid_id']])) {
            foreach ($this->_blocks[$grid['grid_id']] as $block) {
                if (isset($this->_rendered_blocks[$block['snapping_id']])) {
                    $content = $this->_rendered_blocks[$block['snapping_id']];

                } else {
                    $block['status'] = self::correctStatusForDynamicObject($block, $this->_dynamic_object_scheme);

                    /**
                     * Actions before render block
                     * @param array $grid Grid data
                     * @param array $block Block data
                     * @param object $this Current RenderManager object
                     * @param string $content Rendered content of blocks
                     */
                    fn_set_hook('render_blocks', $grid, $block, $this, $content);

                    if ($this->_area == 'C' && $block['status'] == 'D') {
                        // Do not render block in frontend if it disabled
                        continue;
                    }
                    Debugger::blockRenderingStarted($block);
                    $content .= self::renderBlock($block, $grid, $this->_area);
                    Debugger::blockRenderingEnded($block['block_id']);

                    $this->_rendered_blocks[$block['snapping_id']] = $content;
                }
            }
        }

        return $content;
    }

    /**
     * Corrects status if this block has different status for some dynamic object
     * @param array $block Block data
     * @param $dynamic_object_scheme
     * @return string Status A or D
     */
    public static function correctStatusForDynamicObject($block, $dynamic_object_scheme)
    {
        $status = $block['status'];
        // If dynamic object defined correct status
        if (!empty($dynamic_object_scheme['key'])) {
            $status = 'A';
            $object_key = $dynamic_object_scheme['key'];

            if ($block['status'] == 'A' && in_array($_REQUEST[$object_key], $block['items_array'])) {
                // If block enabled globally and disabled for some dynamic object
                $status = 'D';
            } elseif ($block['status'] == 'D' && !in_array($_REQUEST[$object_key], $block['items_array'])) {
                // If block disabled globally and not enabled for some dynamic object
                $status = 'D';
            }
        }

        return $status;
    }

    /**
     * Renders block
     * @static
     * @param  array  $block             Block data to be rendered
     * @param  string $content_alignment Alignment of block (float left, float, right, width 100%)
     * @param  string $area              Area ('A' for admin or 'C' for custom
     * @return string HTML code of rendered block
     */
    public static function renderBlock($block, $parent_grid = array(), $area = 'C', $params = array())
    {
        if (SchemesManager::isBlockExist($block['type'])) {
            $view = \Tygh::$app['view'];

            $view->assign('parent_grid', $parent_grid);

            $content_alignment = !empty($parent_grid['content_align']) ? $parent_grid['content_align'] : 'FULL_WIDTH';
            $view->assign('content_alignment', $content_alignment);

            if ($area == 'C') {
                return self::renderBlockContent($block, $params);
            } elseif ($area == 'A') {
                $scheme = SchemesManager::getBlockScheme($block['type'], array());
                if (!empty($scheme['single_for_location'])) {
                    $block['single_for_location'] = true;
                }
                $view->assign('block_data', $block);

                return $view->fetch(self::_getThemePath($area) . 'block.tpl');
            }
        }

        return '';
    }

    /**
     * Renders block content
     *
     * @static
     *
     * @param array $block  Block data for rendering content
     * @param array $params Parameters of rendering:
     *                       * use_cache - Whether to use cache
     *                       * parse_js - Whether to move inline JS of the block to the bottom of the page
     *
     * @return string HTML code of rendered block content
     */
    public static function renderBlockContent($block, $params = array())
    {
        $default_params = array(
            'use_cache' => true,
            'parse_js' => true
        );

        $params = array_merge($default_params, $params);

        $block_schema = SchemesManager::getBlockScheme($block['type'], array());

        $block_content = null;

        /**
         * Allows to perform any actions before rendering block.
         *
         * @param array       $block         Block data
         * @param array       $block_schema  Block schema
         * @param array       $params        Rendering paramenters
         * @param string|null $block_content Block content. If your hook handler will fill this variable, it will be used as block content.
         */
        fn_set_hook('render_block_pre', $block, $block_schema, $params, $block_content);

        if ($block_content !== null) {
            return $block_content;
        }

        // Do not render block if it disabled in the frontend
        if (isset($block['is_disabled']) && $block['is_disabled']) {
            return '';
        }
        $smarty = \Tygh::$app['view'];
        $smarty_original_vars = $smarty->getTemplateVars();

        $display_this_block = true;

        self::_assignBlockSettingsToTemplate($block);

        // Assign block data from DB
        $smarty->assign('block', $block);

        $cache_prefix = "block_content_{$block['type']}";

        $cache_this_block = $params['use_cache'] && self::allowCache();
        if ($cache_this_block
            && isset($block['content']['items']['filling'])
            && isset($block_schema['content']['items']['fillings'][$block['content']['items']['filling']]['disable_cache'])
        ) {
            $cache_this_block = !$block_schema['content']['items']['fillings'][$block['content']['items']['filling']]['disable_cache'];
        }

        /**
         * Determines flags for Cache
         *
         * @param array  $block              Block data
         * @param string $cache_prefix       Prefix to cache block
         * @param array  $block_schema       Block schema
         * @param bool   $cache_this_block   Flag to register cache
         * @param bool   $display_this_block Flag to display block
         */
        fn_set_hook('render_block_register_cache', $block, $cache_prefix, $block_schema, $cache_this_block,
            $display_this_block);

        if ($cache_this_block) {
            // Check whether cache was registered successfully
            $cache_this_block = $cache_key = self::registerBlockCacheIfNeeded($cache_prefix, $block_schema, $block);

            // We need an extra data to cache Inline JavaScript
            $smarty->assign('block_cache_name', $cache_key);
        } else {
            $smarty->clearAssign('block_cache_name');
        }

        $smarty->assign('block_rendering', true);
        $smarty->assign('block_parse_js', $params['parse_js']);

        // We should load only when cache record exists
        $load_block_from_cache = $cache_this_block && Registry::isExist($cache_key);

        $block_content = '';

        // Block content is found at cache and should be loaded out of there
        if ($load_block_from_cache) {
            $cached_content = Registry::get($cache_key);
            $block_content = $cached_content['content'];

            if (!empty($cached_content['javascript'])) {
                $repeat = false;
                $smarty->loadPlugin('smarty_block_inline_script');
                smarty_block_inline_script(array(), $cached_content['javascript'], $smarty, $repeat);
            }
            Debugger::blockFoundAtCache($block['block_id']);
        }
        // Otherwise we should render the content
        else {
            if ($block['type'] == Block::TYPE_MAIN) {
                $block_content = self::_renderMainContent();
            } else {
                $title = $block['name'];
                if (Registry::get('runtime.customization_mode.live_editor')) {
                    $le_block_types = fn_get_schema('customization', 'live_editor_block_types');
                    if (!empty($le_block_types[$block['type']]) && !empty($le_block_types[$block['type']]['name'])) {
                        $title = sprintf('<span data-ca-live-editor-obj="block:name:%s">%s</span>',
                            $block['block_id'], $title
                        );
                    }
                }
                $smarty->assign('title', $title);

                if (!empty($block_schema['content'])) {
                    $all_values_are_empty = true;
                    foreach ($block_schema['content'] as $template_variable => $field) {
                        /**
                         * Actions before render any variable of block content
                         *
                         *
                         * @deprecated Use "assign_block_content_variable" instead.
                         * @param string $template_variable name of current block content variable
                         * @param array  $field             Scheme of this content variable from block scheme content section
                         * @param array  $block_schema      block scheme
                         * @param array  $block             Block data
                         */
                        fn_set_hook('render_block_content_pre', $template_variable, $field, $block_schema, $block);
                        $value = self::getValue($template_variable, $field, $block_schema, $block);

                        if ($all_values_are_empty && !empty($value)) {
                            $all_values_are_empty = false;
                        }

                        $smarty->assign($template_variable, $value);
                    }
                    // We shouldn't display block which content variables are all empty
                    $display_this_block = $display_this_block && !$all_values_are_empty;
                }

                // Assign block data from scheme
                $smarty->assign('block_scheme', $block_schema);
                if ($display_this_block && $smarty->templateExists($block['properties']['template'])) {
                    $block_content = $smarty->fetch($block['properties']['template']);
                }
            }

            if (!empty($block['wrapper']) && $smarty->templateExists($block['wrapper']) && $display_this_block) {
                $smarty->assign('content', $block_content);

                if ($block['type'] == Block::TYPE_MAIN) {
                    $smarty->assign(
                        'title',
                        !empty(\Smarty::$_smarty_vars['capture']['mainbox_title'])
                            ? \Smarty::$_smarty_vars['capture']['mainbox_title']
                            : '',
                        false
                    );
                }
                $block_content = $smarty->fetch($block['wrapper']);
            } else {
                $smarty->assign('content', $block_content);
                $block_content = $smarty->fetch('views/block_manager/render/block.tpl');
            }

            /**
             * Allows to perform any actions after block content was rendered.
             *
             * @param array       $block_schema          Block schema
             * @param array       $block                 Block data
             * @param string|null $block_content         Block content. You may modify already rendered content by changing this variable contents.
             * @param array       $params                Rendering paramenters
             * @param bool        $load_block_from_cache Whether block content was found at cache and was loaded out of there
             */
            fn_set_hook('render_block_content_after', $block_schema, $block, $block_content, $params, $load_block_from_cache);

            // Save block contents to cache
            if ($cache_this_block) {
                $cached_content = Registry::get($cache_key);
                $cached_content['content'] = $block_content;

                Registry::set($cache_key, $cached_content);
            }
        }

        $wrap_id = $smarty->getTemplateVars('block_wrap');

        $smarty->clearAllAssign();
        $smarty->assign($smarty_original_vars); // restore original vars
        \Smarty::$_smarty_vars['capture']['title'] = null;

        if (!empty($wrap_id)) {
            $block_content = '<div id="' . $wrap_id . '">' . $block_content . '<!--' . $wrap_id . '--></div>';
        }
        $block_content = trim($block_content);

        fn_set_hook('render_block_post', $block, $block_schema, $block_content, $load_block_from_cache, $display_this_block,  $params);

        if ($display_this_block == true) {
            return $block_content;
        } else {
            return '';
        }
    }

    /**
     * Returns true if cache used for blocks
     *
     * @static
     * @return bool true if we may use cahce, false otherwise
     */
    public static function allowCache()
    {
        $use_cache = true;
        if (Registry::ifGet('config.tweaks.disable_block_cache', false)
            || Registry::get('runtime.customizaton_mode.design')
            || Registry::get('runtime.customizaton_mode.translation')
            || Development::isEnabled('compile_check')
        ) {
            $use_cache = false;
        }

        return $use_cache;
    }

    /**
     * Renders content of main content block
     * @return string HTML code of rendered block content
     */
    private static function _renderMainContent()
    {
        $smarty = \Tygh::$app['view'];
        $content_tpl = $smarty->getTemplateVars('content_tpl');

        return !empty($content_tpl) ? $smarty->fetch($content_tpl) : '';
    }

    /**
     * Renders or gets value of some variable of block content
     * @param  string $template_variable name of current block content variable
     * @param  array  $field             Scheme of this content variable from block scheme content section
     * @param  array  $block_scheme      block scheme
     * @param  array  $block             Block data
     * @return string Rendered block content variable value
     */
    public static function getValue($template_variable, $field, $block_scheme, $block)
    {
        $value = '';
        // Init value by default
        if (isset($field['default_value'])) {
            $value = $field['default_value'];
        }

        if (isset($block['content'][$template_variable])) {
            $value = $block['content'][$template_variable];
        }

        if ($field['type'] == 'enum') {
            $value = Block::instance()->getItems($template_variable, $block, $block_scheme);
        }

        if ($field['type'] == 'function' && !empty($field['function'][0]) && is_callable($field['function'][0])) {
            $callable = array_shift($field['function']);
            array_unshift($field['function'], $value, $block, $block_scheme);
            $value = call_user_func_array($callable, $field['function']);
        }

        return $value;
    }

    /**
     * Registers block cache
     *
     * @param string $cache_prefix Prefix to cache block
     * @param array  $block_schema Block schema data
     * @param array  $block_data   Block data from DB
     *
     * @return string|bool Cache key when block has been cached, false otherwise
     */
    public static function registerBlockCacheIfNeeded($cache_prefix, $block_schema, $block_data)
    {
        // @TODO: remove Registry calls and use RenderManager::$_location instead. This method should be non-static.
        $dispatch = Registry::get('runtime.controller') . '.' . Registry::get('runtime.mode');

        // Use parameters for current dispatch with fallback to common params
        if (!empty($block_schema['cache_overrides_by_dispatch'][$dispatch])) {
            $cache_params = $block_schema['cache_overrides_by_dispatch'][$dispatch];
            $cache_prefix .= '_' . $dispatch;
        } elseif (!empty($block_schema['cache'])) {
            if ($block_schema['cache'] === true) {
                // Caching according to the global default rules is expected
                $cache_params = array();
            } elseif (is_array($block_schema['cache'])) {
                // Cache configuration array is passed
                $cache_params = $block_schema['cache'];
            } else {
                // Weird value is passed, just do not cache this kind of block
                return false;
            }
        } else {
            return false;
        }

        $cookie_data = fn_get_session_data();
        $cookie_data['all'] = $cookie_data;

        $callable_handlers_variables = compact('block_schema', 'block_data');

        $disable_cache = false;
        // Check conditions that disable block caching
        if (!empty($cache_params['disable_cache_when'])) {
            $disable_cache |= self::findHandlerParamsAtData($cache_params['disable_cache_when'], 'request_handlers', $_REQUEST);
            $disable_cache |= self::findHandlerParamsAtData($cache_params['disable_cache_when'], 'session_handlers', \Tygh::$app['session']);
            $disable_cache |= self::findHandlerParamsAtData($cache_params['disable_cache_when'], 'cookie_handlers', $cookie_data);
            $disable_cache |= self::findHandlerParamsAtData($cache_params['disable_cache_when'], 'auth_handlers', \Tygh::$app['session']['auth']);

            // Disable cache if any of callable handlers returns true
            if (!empty($cache_params['disable_cache_when']['callable_handlers'])) {
                self::execCallableHandlers(
                    function ($handler_name, $handler_result) use (&$disable_cache) {
                        $disable_cache |= $handler_result;
                    },
                    (array) $cache_params['disable_cache_when']['callable_handlers'],
                    $callable_handlers_variables
                );
            }
        }
        if ($disable_cache) {
            return false;
        }

        // Generate cache key using dependencies specified at schema
        $cache_key = implode('_', array(
            $block_data['block_id'],
            $block_data['snapping_id'],
            (empty($block_data['grid_id'])      ? 0 : $block_data['grid_id']),
            (empty($block_data['object_id'])    ? 0 : $block_data['object_id']),
            (empty($block_data['object_type'])  ? 0 : $block_data['object_type'])
        ));

        $generate_additional_level = function($param_name, $param_value) use (&$cache_key) {
            $cache_key .= '|' . $param_name . '=' . md5(serialize($param_value));
        };

        $default_cache_params = fn_get_schema('block_manager', 'block_cache_properties');

        // Merge block's cache parameters with global default cache parameters
        $cache_param_types = array(
            'request_handlers',
            'session_handlers',
            'cookie_handlers',
            'auth_handlers',
            'update_handlers',
            'callable_handlers'
        );
        foreach ($cache_param_types as $cache_param_type) {
            $cache_params[$cache_param_type] = isset($cache_params[$cache_param_type])
                ? $cache_params[$cache_param_type]
                : array();

            $cache_params[$cache_param_type] = isset($default_cache_params[$cache_param_type])
                ? array_merge($cache_params[$cache_param_type], $default_cache_params[$cache_param_type])
                : $cache_params[$cache_param_type];
        }

        self::findHandlerParamsAtData($cache_params, 'request_handlers', $_REQUEST, $generate_additional_level);
        self::findHandlerParamsAtData($cache_params, 'session_handlers', \Tygh::$app['session'], $generate_additional_level);
        self::findHandlerParamsAtData($cache_params, 'cookie_handlers', $cookie_data, $generate_additional_level);
        self::findHandlerParamsAtData($cache_params, 'auth_handlers', \Tygh::$app['session']['auth'], $generate_additional_level);

        if (!empty($cache_params['callable_handlers'])) {
            self::execCallableHandlers(
                $generate_additional_level,
                (array) $cache_params['callable_handlers'],
                $callable_handlers_variables
            );
        }

        $cache_key .= '|path=' . Registry::get('config.current_path');
        $cache_key .= Embedded::isEnabled() ? '|embedded' : '';

        $cache_key = md5($cache_key);

        $cache_level = isset($cache_params['cache_level'])
            ? $cache_params['cache_level']
            : Registry::cacheLevel('html_blocks');

        Registry::registerCache(array($cache_prefix, $cache_key), $cache_params['update_handlers'], $cache_level);

        // Check conditions that trigger block cache regeneration
        $regenerate_cache = false;
        if (!empty($cache_params['regenerate_cache_when'])) {
            $regenerate_cache |= self::findHandlerParamsAtData($cache_params['regenerate_cache_when'], 'request_handlers', $_REQUEST);
            $regenerate_cache |= self::findHandlerParamsAtData($cache_params['regenerate_cache_when'], 'session_handlers', \Tygh::$app['session']);
            $regenerate_cache |= self::findHandlerParamsAtData($cache_params['regenerate_cache_when'], 'cookie_handlers', $cookie_data);
            $regenerate_cache |= self::findHandlerParamsAtData($cache_params['regenerate_cache_when'], 'auth_handlers', \Tygh::$app['session']['auth']);

            // Regenerate cache if any of callable handlers returns true
            if (!empty($cache_params['regenerate_cache_when']['callable_handlers'])) {
                self::execCallableHandlers(
                    function ($handler_name, $handler_result) use (&$regenerate_cache) {
                        $regenerate_cache |= $handler_result;
                    },
                    (array) $cache_params['regenerate_cache_when']['callable_handlers'],
                    $callable_handlers_variables
                );
            }
        }
        if ($regenerate_cache) {
            Registry::del($cache_key);
        }

        return $cache_key;
    }

    /**
     * Executes callable cache handlers specified at block cache schema and passes call results to given function.
     *
     * @param Callable $wrapper_func Function that would be called after every handler call.
     *                                    Should accept handler name as the first argument and handler call result as the second.
     * @param array $callable_handlers List of callable handler definitions in format: [handler_name => [Callable, [arg0, arg1, ...]], ...]
     * @param array $variables_to_pass List of variable names and their values that may be passed to handler as an arguments
     *
     * @TODO: refactor to v5.0.1, see issue @1-14388
     */
    public static function execCallableHandlers($wrapper_func, array $callable_handlers, array $variables_to_pass = array())
    {
        if (!is_callable($wrapper_func)) {
            throw new \InvalidArgumentException('Wrapper function should be callable.');
        }

        foreach ($callable_handlers as $handler_name => $callable_definition) {

            if (isset($callable_definition[0]) && is_callable($callable_definition[0])) {
                $arguments = array();

                if (isset($callable_definition[1]) && is_array($callable_definition[1])) {
                    foreach ($callable_definition[1] as $argument) {

                        if (strpos($argument, '$') === 0) {
                            // Superglobal variables like $_REQUEST
                            if (isset(${$argument})) {
                                $arguments[] = ${$argument};
                            }
                            // Argument variable name listed at allowed variables to pass
                            elseif (
                                ($argument_variable_name = substr($argument, 1))
                                &&
                                array_key_exists($argument_variable_name, $variables_to_pass)
                            ) {
                                $arguments[] = $variables_to_pass[$argument_variable_name];
                            } else {
                                $arguments[] = $argument;
                            }
                        } else {
                            $arguments[] = $argument;
                        }
                    }
                }

                $wrapper_func($handler_name, call_user_func_array($callable_definition[0], $arguments));
            }
        }
    }

    /**
     * @param               $schema
     * @param               $handler_name
     * @param               $data
     * @param Callable|bool $when_found
     *
     * @return bool
     */
    protected static function findHandlerParamsAtData($schema, $handler_name, $data, $when_found = true)
    {
        if (!empty($schema[$handler_name]) && is_array($schema[$handler_name])) {

            if (in_array('*', $schema[$handler_name])) {
                if (is_callable($when_found)) {
                    call_user_func($when_found, '*', $data);

                    return;
                } else {
                    return $when_found;
                }
            }

            foreach ($schema[$handler_name] as $i => $param_name) {
                // Clear the value from previous iteration
                if (isset($value)) {
                    unset($value);
                }

                // An array with comparison condition is passed
                if (is_array($param_name)) {
                    if (!isset($param_name[0], $param_name[1])) {
                        throw new \InvalidArgumentException('Incorrect comparison condition format given');
                    }
                    list($param_name, list($comparison_operator, $right_operand)) = array($i, $param_name);
                }
                $param_name = fn_strtolower(str_replace('%', '', $param_name));

                if (isset($data[$param_name])) {
                    $value = $data[$param_name];
                } elseif (strpos($param_name, '.') !== false) {
                    $value = fn_dot_syntax_get($param_name, $data);
                    if ($value === null) {
                        unset($value);
                    }
                }
                if (isset($value)
                    && (!isset($comparison_operator, $right_operand)
                        || fn_compare_values_by_operator(
                            $value,
                            $comparison_operator,
                            $right_operand
                        )
                    )
                ) {
                    if (is_callable($when_found)) {
                        call_user_func($when_found, $param_name, $value);
                    } else {
                        return $when_found;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Generates additional cache levels by storage
     *
     * @param  array  $cache_scheme Block cache scheme
     * @param  string $handler_name Name of handlers frocm block scheme
     * @param  array  $storage      Storage to find params
     * @return string Additional chache level
     */
    private static function _generateAdditionalCacheLevel($cache_scheme, $handler_name, $storage)
    {
        $additional_level = '';

        if (!empty($cache_scheme[$handler_name]) && is_array($cache_scheme[$handler_name])) {
            foreach ($cache_scheme[$handler_name] as $param) {
                $param = fn_strtolower(str_replace('%', '', $param));
                if (isset($storage[$param])) {
                    $additional_level .= '|' . $param . '=' . md5(serialize($storage[$param]));
                }
            }
        }

        return $additional_level;
    }

    /**
     * Removes compiled block templates
     * @return bool
     */
    public static function deleteTemplatesCache()
    {
        static $is_deleted = false;

        if (!$is_deleted) {

            // mark cache as outdated
            Registry::setChangedTables('bm_blocks');
            // run cache routines
            Registry::save();

            $is_deleted = true;
        }

        return $is_deleted;
    }

    /**
     * Sorts grids by order parameter
     *
     * @param  array $grids Hierarchic builded tree
     * @return array Sorted grids
     */
    public static function sortGrids($grids)
    {
        $static_grids = array();
        foreach ($grids as $id => $grid) {
            if ($grid['order'] == 0) {
                $static_grids[] = $id;
            }

            if (!empty($grid['children'])) {
                $grid['children'] = self::sortGrids($grid['children']);
            }

            $grids[$id] = $grid;
        }

        $grids = fn_sort_array_by_key($grids, 'order', SORT_ASC);
        $sorted_grids = array();

        foreach ($static_grids as $grid_id) {
            $sorted_grids += array($grid_id => $grids[$grid_id]);
            unset($grids[$grid_id]);
        }

        $sorted_grids += $grids;

        return $sorted_grids;
    }

    /**
     * Assigns block properties data to template
     * @param array $block Block data
     */
    private static function _assignBlockSettingsToTemplate($block)
    {
        if (isset($block['properties']) && is_array($block['properties'])) {
            foreach ($block['properties'] as $name => $value) {
                \Tygh::$app['view']->assign($name, $value);
            }
        }

    }

    /**
     * Returns customer theme path
     * @static
     * @return string Path to customer theme folder
     */
    public static function getCustomerThemePath()
    {
        return fn_get_theme_path('[themes]/[theme]/templates/', 'C');
    }

    /**
     * Returns theme path for different areas
     * @static
     * @param  string $area Area ('A' for admin or 'C' for custom
     * @return string Path to theme folder
     */
    private static function _getThemePath($area = 'C')
    {
        if ($area == 'C') {
            $area = self::CUSTOMER;
        } elseif ($area == 'A') {
            $area = self::ADMIN;
        }

        return 'views/block_manager/render/';
    }
}
