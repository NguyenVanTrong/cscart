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

namespace Tygh;
use Tygh\Themes\Styles;
use Tygh\Registry;
use Tygh\Themes\Themes;

class Less extends \lessc
{
    /**
     * Compile LESS to CSS, appending data from styles and parsing urls
     * @param  string $less_output    LESS code
     * @param  string $dirname        absolute path where compiled file will be saved (to parse URLs correctly)
     * @param  array  $data           style data
     * @param  string $prepend_prefix prefix to prepend all selectors (for widget mode)
     * @param  string $area           current working area
     * @return string CSS code
     */
    public function customCompile($less_output, $dirname, $data = array(), $prepend_prefix = '', $area = AREA)
    {
        $theme = Themes::areaFactory($area);
        // Apply all Custom styles styles
        if ($area == 'C') {
            $less_output .= "\n" . Styles::factory($theme->getThemeName())->getLess($data);

            // Inject Bootstrap fluid variables
            $less_output .= self::getLayoutStyleVariables();
        }

        if (!empty($prepend_prefix)) {
            $less_output = $prepend_prefix . " {\n" . $less_output . "\n}";
        }

        if (false) { // is not implemented completely
            $output = self::parseWithNodeJs($less_output, $area);
        } else {
            $output = !empty($less_output) ? $this->parse($less_output) : '';
        }

        // Remove "body" definition
        if (!empty($prepend_prefix)) {
            $output = str_replace($prepend_prefix . ' body', $prepend_prefix, $output);
        }

        // Quote font family names
        $output = self::normalizeFontFamilies($output);

        return Less::parseUrls($output, $dirname, fn_get_theme_path('[themes]/[theme]/media', $area));

    }

    /**
     * Gets data from layout to pass it to LESS
     * @param  string $layout_data layout data
     * @return string LESS markup
     */
    public static function getLayoutStyleVariables($layout_data = array())
    {
        if (empty($layout_data)) {
            $layout_data = Registry::get('runtime.layout');
        }

        // default values
        $variables = array(
            'gridColumns' => '16',
            'fluidContainerMaxWidth' => '960px',
            'fluidContainerMinWidth' => '760px'
        );

        if ($layout_data['layout_width'] == 'fluid') {
            $variables['fluidContainerMinWidth'] = $layout_data['min_width'] . 'px';
            $variables['fluidContainerMaxWidth'] = $layout_data['max_width'] . 'px';

        } elseif ($layout_data['layout_width'] == 'full_width') {
            $variables['fluidContainerMinWidth'] = 'auto';
            $variables['fluidContainerMaxWidth'] = 'auto';
        }

        if (!empty($layout_data['width'])) {
            $variables['gridColumns'] = $layout_data['width'];
        }

        return self::arrayToLessVars($variables);
    }

    /**
     * Converts array with LESS vars to LESS code
     * @param  array  $vars LESS vars
     * @return string LESS code
     */
    public static function arrayToLessVars($vars)
    {
        $less = '';

        foreach ($vars as $var_name => $value) {
            $less .= '@' . $var_name . ': ' . $value . ";\n";
        }

        return $less;
    }

    /**
     * Parses CSS code to make correct relative URLs in case of CSS/LESS files compiled and placed to another directory
     * @param  string $content   CSS/LESS code
     * @param  string $from_path path, where original CSS/LESS file is placed
     * @param  string $to_path   path, where compiled CSS/LESS file is placed
     * @return string parsed content
     */
    public static function parseUrls($content, $from_path, $to_path)
    {
        $theme = Themes::areaFactory();
        $theme_dirs = $theme->getThemeDirs();

        if (preg_match_all("/url\((?![\"']?data\:).*?\)/", $content, $m)) {
            $relative_path = self::relativePath($from_path, $to_path);

            foreach ($m[0] as $match) {
                $url = trim(str_replace('url(', '', $match), "'()\"");

                // Workaround for parse_url bug fixed in PHP 5.4.7
                if (strpos($url, '//') === 0) {
                    continue;
                }

                $parsed_url = parse_url($url);

                if ($parsed_url === false
                    || !isset($parsed_url['path']) // Incorrect URL
                    || isset($parsed_url['host']) // Absolute URL
                    || isset($parsed_url['query']) // URL contains query params
                ) {
                    continue;
                }

                $file_url = trim(preg_replace("/^(\.\.\/)+media\//", '', $parsed_url['path']), '/');

                // trick to handle parent theme files
                $content_path = $relative_path;
                if (count($theme_dirs) > 1 && preg_match('@design/themes/(?<name>.+?)/@', $to_path, $src_theme)) {
                    if ($src_theme['name'] == $theme->getThemeName()) {
                        $file_info = $theme->getContentPath($to_path . '/' . $file_url);
                        if ($file_info && $src_theme['name'] != $file_info['theme']) {
                            $content_path = str_replace("design/themes/{$src_theme['name']}/", "design/themes/{$file_info['theme']}/", $content_path);
                        }
                    }
                }

                $url = trim($content_path, '/')
                    . '/'
                    . $file_url
                    . '?' . TIME;

                if (isset($parsed_url['fragment'])) {
                    $url .= '#' . $parsed_url['fragment'];
                }

                $content = str_replace($match, "url('{$url}')", $content);
            }
        }

        return $content;
    }

    /**
     * Creates relative path from one directory to another
     * @param  string $from from directory
     * @param  string $to   to directory
     * @return string relative path
     */
    private static function relativePath($from, $to)
    {
        $from = fn_normalize_path($from);
        $to = fn_normalize_path($to);

        $_from = explode('/', rtrim($from, '/'));
        $_to = explode('/', rtrim($to, '/'));

        while (count($_from) && count($_to) && ($_from[0] == $_to[0])) {
            array_shift($_from);
            array_shift($_to);
        }

        return str_pad('', count($_from) * 3, '../') . implode('/', $_to);
    }

    /**
     * Parse LESS markup and generate CSS with native nodejs parser
     * @param  string $less_output LESS markup
     * @param  string $area        current working area
     * @return string generated CSS
     */
    private static function parseWithNodeJs($less_output, $area = AREA)
    {
        $output = '';
        $cmd = 'lessc -';
        $descriptorspec = array(
           0 => array("pipe", "r"), // stdin is a pipe that the child will read from
           1 => array("pipe", "w"), // stdout is a pipe that the child will write to
           2 => array('pipe', 'w'), // stderr
        );
        chdir(fn_get_theme_path('[themes]/[theme]/css', $area));
        $process = proc_open($cmd, $descriptorspec, $pipes);
        if (is_resource($process)) {
            fwrite($pipes[0], $less_output); // file_get_contents('php://stdin')
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);

            if (!empty($errors)) {
                fn_print_die($errors);
            }

            fclose($pipes[1]);
            $return_value = proc_close($process);
        }

        return $output;
    }

    /**
     * Extracts variables from LESS markup
     * @param  string $less LESS markup
     * @return array  variables list
     */
    public function extractVars($less)
    {
        $vars = array();
        $this->compile($less);

        if (!empty($this->parser->env->props)) {
            foreach ($this->parser->env->props as $prop) {
                if ($prop[0] == 'assign') {
                    list(, $var_name, $value) = $prop;

                    $var_name = str_replace('@', '', $var_name);
                    $vars[$var_name] = $this->parseVarValue($value);
                }
            }
        }

        return $vars;
    }

    /**
     * Gets LESS variable value
     * @param  array  $value LESS variabe
     * @return string value
     */
    private function parseVarValue($value)
    {
        $result = '';

        switch ($value[0]) {
            case 'keyword': case 'raw_color':
                $result = $value[1];

                break;

            case 'list':
                $delimiter = $value[1];

                foreach ($value[2] as $iteration => $_val) {
                    $result .= $this->parseVarValue($_val);

                    if (++$iteration < count($value[2])) {
                        $result .= $delimiter;
                    }
                }

                $result = trim($result);

                break;

            case 'number':
                $number = $value[1];
                $metric = $value[2];

                $result = $number . $metric;

                break;

            case 'string':
                $delimiter = $value[1];
                $result = $delimiter . implode('', $value[2]) . $delimiter;

                break;

            case 'function':
                $function_name = $value[1];
                $result = $function_name . '(' . $this->parseVarValue($value[2]) . ')';

                break;
            case 'escape':
                $result = '';
                break;

            default:
                $result = $value;
                break;
        }

        $result = preg_replace('/,\s+/', ',', $result);

        return $result;
    }

    /*
     * Adds caching to default lessc method
     */
    protected function tryImport($importPath, $parentBlock, $out)
    {
        if ($importPath[0] == "function" && $importPath[1] == "url") {
            $importPath = $this->flattenList($importPath[2]);
        }

        $str = $this->coerceString($importPath);
        if ($str === null) return false;

        $url = $this->compileValue($this->lib_e($str));

        // don't import if it ends in css
        if (substr_compare($url, '.css', -4, 4) === 0) return false;

        $realPath = $this->findImport($url);

        if ($realPath === null) return false;

        if ($this->importDisabled) {
            return array(false, "/* import disabled */");
        }

        if (isset($this->allParsedFiles[realpath($realPath)])) {
            return array(false, null);
        }

        $this->addParsedFile($realPath);

        $hash = md5($realPath);
        $cache_file = Registry::get('config.dir.cache_static') . '/less/' . $hash;
        $parser = $this->makeParser($realPath);
        $root = null;
        if (file_exists($cache_file) && filemtime($realPath) < filemtime($cache_file)) {
            $root = unserialize(fn_get_contents($cache_file));
        }

        if (!is_object($root)) {
            $root = $parser->parse(fn_get_contents($realPath));
            fn_mkdir(dirname($cache_file));
            fn_put_contents($cache_file, serialize($root));
        }

        // set the parents of all the block props
        foreach ($root->props as $prop) {
            if ($prop[0] == "block") {
                $prop[1]->parent = $parentBlock;
            }
        }

        // copy mixins into scope, set their parents
        // bring blocks from import into current block
        // TODO: need to mark the source parser these came from this file
        foreach ($root->children as $childName => $child) {
            if (isset($parentBlock->children[$childName])) {
                $parentBlock->children[$childName] = array_merge(
                    $parentBlock->children[$childName],
                    $child);
            } else {
                $parentBlock->children[$childName] = $child;
            }
        }

        $pi = pathinfo($realPath);
        $dir = $pi["dirname"];

        list($top, $bottom) = $this->sortProps($root->props, true);
        $this->compileImportedProps($top, $parentBlock, $out, $parser, $dir);

        return array(true, $bottom, $parser, $dir);
    }

    /**
     * Quote font family names.
     * The functions processes compiled CSS content before saving the content on disk.
     *
     * @param  string $data Compiled CSS content
     * @return string Compiled CSS content with font family names quoted where necessary
     */
    public static function normalizeFontFamilies($data)
    {
        if (preg_match_all('/font-family: ?(.+?);/', $data, $matches)) {
            // get all unique definitions
            $matches = array_unique($matches[0]);
            foreach ($matches as $css_property) {
                list($property_name, $property_value) = explode(':', $css_property);
                $property_value = str_replace('!important', '', $property_value, $is_important);
                $property_value = explode(',', $property_value);
                // quote font families
                $property_value = array_map(function($family) {
                    $family = trim($family, ' ;\'"');
                    // do not wrap single-word families and 'inherit' value
                    return strpos($family, ' ') ? "'{$family}'" : $family;
                }, $property_value);
                $property_value = implode(',', $property_value);
                $is_important = $is_important ? ' !important' : '';
                $data = str_replace($css_property, "{$property_name}:{$property_value}{$is_important};", $data);
            }
        }

        return $data;
    }

    /**
     * Overrides the default \lessc method to fix LESS @import priorities.
     * This way, all styles from the child theme will have higher priority than the parent ones.
     *
     * @inherit
     */
    protected function compileImportedProps($props, $block, $out, $sourceParser, $importDir)
    {
        $oldSourceParser = $this->sourceParser;

        $oldImport = $this->importDir;

        /**
         * When any @import'ed file is found in the parent theme, the parent import dir priority is increased
         * by putting it to the top of the list (see \lessc::findImport()).
         * However, the child theme styles have higher priority, so instead of prepending import dir to the list,
         * we append it.
         */
        $this->addImportDir($importDir);

        foreach ($props as $prop) {
            $this->compileProp($prop, $block, $out);
        }

        $this->importDir = $oldImport;
        $this->sourceParser = $oldSourceParser;
    }
}
