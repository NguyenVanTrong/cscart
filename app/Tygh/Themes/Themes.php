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
namespace Tygh\Themes;

use Tygh\Languages\Languages;
use Tygh\Less;
use Tygh\Registry;
use Tygh\BlockManager\Layout;
use Tygh\Settings;
use Tygh\Storage;
use Tygh\Themes\Styles;
use Tygh\Languages\Po;

class Themes
{
    /**
     * If directory is not empty in the theme, the parent theme will not be searched
     */
    const STR_SINGLE = 'single';
    /**
     * Merge together content of the theme and the parent theme
     */
    const STR_MERGE = 'merge';
    /**
     * Use the theme content only
     */
    const STR_EXCLUSIVE = 'exclusive';
    /**
     * Path type: absolute
     */
    const PATH_ABSOLUTE = 'absolute';
    /**
     * Path type: relative
     */
    const PATH_RELATIVE = 'relative';
    /**
     * Path type: repository
     */
    const PATH_REPO = 'repo';
    /**
     * Content type: file
     */
    const CONTENT_FILE = 'file';
    /**
     * Content type: directory
     */
    const CONTENT_DIR = 'dir';
    /**
     * Include no additional dirs
     */
    const USE_DEFAULT = 0;
    /**
     * Include content of base theme
     */
    const USE_BASE = 1;
    /**
     * Include content of legacy theme (basic)
     */
    const USE_LEGACY = 2;

    protected static $file_cache;

    public static $compiled_less_filename = 'styles.pcl.css';
    public static $less_backup_dirname = '__less_backup';
    public static $css_backup_dirname = '__css_backup';

    private static $instances = array();
    private static $area_instances = array();
    private static $settings_overrides = array();

    protected $less = null;
    protected $less_reflection = null;
    protected $theme_name = '';
    protected $theme_path = '';
    protected $relative_path = '';
    protected $repo_path = '';
    protected $manifest = array();
    protected $content_paths = array();

    /**
     * @var string Theme's default language
     */
    protected $default_language;

    public $area;

    public function __construct($theme_name)
    {
        $this->theme_name = $theme_name;
        if (fn_string_not_empty($theme_name)) {
            $this->area = 'C';
        } else {
            $this->area = 'A';
            $theme_name = '[theme]';
        }
        $this->theme_path = fn_get_theme_path('[themes]/' . $theme_name, $this->area);
        $this->relative_path = fn_get_theme_path('[relative]/' . $theme_name, $this->area);
        $this->repo_path = fn_get_theme_path('[repo]/' . $theme_name, $this->area);
    }

    /**
     * Convert theme LESS to CSS files
     *
     * @return boalean Result
     */
    public function convertToCss()
    {
        if (!file_exists($this->theme_path . '/' . THEME_MANIFEST)) {
            fn_put_contents($this->theme_path . '/' . THEME_MANIFEST, '');
        }

        if (!is_writable($this->theme_path . '/' . THEME_MANIFEST)) {
            return false;
        }

        $theme_css_path = $this->theme_path . '/css';

        $less_reflection = $this->getLessReflection();

        if (!empty($less_reflection['output']['main'])) {

            $exclude = array(
                'addons', self::$less_backup_dirname, self::$css_backup_dirname
            );

            if (!(
                $this->convertChunkToCss($less_reflection['output']['main'], $theme_css_path)
                && $this->removeLessFiles($theme_css_path, $theme_css_path . '/' . self::$less_backup_dirname, $exclude)
            )) {
                return false;
            }

        }

        if (!empty($less_reflection['output']['addons'])) {
            foreach ($less_reflection['output']['addons'] as $addon_name => $addon_less_output) {
                if (!empty($addon_less_output)) {
                    if (!$this->convertAddonToCss($addon_name, $addon_less_output)) {
                        return false;
                    }
                }
            }
        }

        $manifest = &$this->getManifest();
        $manifest['converted_to_css'] = true;

        return $this->saveManifest();
    }

    /**
     * Precompile addon LESS
     *
     * @param string $addon             Addon name
     * @param string $addon_less_output Addon less output
     *
     * @return boolean Result
     */
    public function convertAddonToCss($addon, $addon_less_output = '')
    {
        $manifest = &$this->getManifest();

        $_temporary_restore_less = false;

        if (!empty($manifest['converted_to_css'])) {
            $_temporary_restore_less = true;
            $this->restoreLess(false);
        }

        if (empty($addon_less_output)) {
            $less_reflection = $this->getLessReflection();
            $addon_less_output = '';
            if (!empty($less_reflection['output']['addons'][$addon])) {
                $addon_less_output = $less_reflection['output']['addons'][$addon];
            }
        }

        if ($_temporary_restore_less) {
            $exclude = array(
                'addons', self::$less_backup_dirname, self::$css_backup_dirname
            );
            $this->removeLessFiles($this->theme_path . '/css', null, $exclude);
            $manifest['converted_to_css'] = true;
            $this->saveManifest();
        }

        $addon_css_path = $this->theme_path . '/css/addons/' . $addon;
        $addon_less_backup_path = $this->theme_path . '/css/' . self::$less_backup_dirname . '/addons/' . $addon;

        if (!(
            $this->convertChunkToCss($addon_less_output, $addon_css_path)
            && $this->removeLessFiles($addon_css_path, $addon_less_backup_path)
        )) {
            return false;
        }

        return true;
    }

    /**
     * Get CSS content from a file
     *
     * @param mixed $filename CSS file name or relative path
     *
     * @return mixed CSS content or false on failure
     */
    public function getCssContents($filename = null)
    {
        if (is_null($filename)) {
            $filename = Themes::$compiled_less_filename;
        }

        return fn_get_contents($this->theme_path . '/css/' . $filename);
    }

    /**
     * Update CSS file
     *
     * @param string $css_file    CSS file name or relative path
     * @param string $css_content CSS content
     *
     * @return boolean Result
     */
    public function updateCssFile($css_file, $css_content)
    {
        return fn_put_contents($this->theme_path . '/css/' . $css_file, $css_content);
    }

    /**
     * Restore LESS files and remove precompiled LESS files
     *
     * @return bolean Result
     */
    public function restoreLess($remove_precompiled_less = true)
    {
        if (!file_exists($this->theme_path . '/' . THEME_MANIFEST)) {
            fn_put_contents($this->theme_path . '/' . THEME_MANIFEST, '');
        }

        if (!is_writable($this->theme_path . '/' . THEME_MANIFEST)) {
            return false;
        }

        $theme_css_path = $this->theme_path . '/css';

        $less_backup_path = $theme_css_path . '/' . self::$less_backup_dirname;

        // dependent themes may not have __less_backup folder at all
        if (is_dir($less_backup_path) && !fn_copy($less_backup_path, $theme_css_path)) {
            return false;
        }

        if ($remove_precompiled_less) {
            $this->removePrecompiledLess();
        }

        $manifest = &$this->getManifest();
        $manifest['converted_to_css'] = false;

        return $this->saveManifest();
    }

    /**
     * Remove precompiled LESS files
     *
     * @return boolean Result
     */
    public function removePrecompiledLess()
    {
        $theme_css_path = $this->theme_path . '/css';

        $exclude = array(
            self::$less_backup_dirname, self::$css_backup_dirname
        );

        $precompiled_files = fn_get_dir_contents(
            $theme_css_path, false, true, self::$compiled_less_filename, '', true, $exclude
        );

        foreach ($precompiled_files as $pcl_file) {

            $pcl_filepath = $theme_css_path . '/' . $pcl_file;
            $css_backup_filepath = $theme_css_path . '/' . self::$css_backup_dirname . '/' . $pcl_file;

            if (!fn_mkdir(dirname($css_backup_filepath)) || !fn_copy($pcl_filepath, $css_backup_filepath)) {
                return false;
            }

            fn_rm($pcl_filepath);
        }

        return true;
    }

    /**
     * Get theme CSS files list
     *
     * @return array CSS files list
     */
    public function getCssFilesList()
    {
        $from = $this->theme_path . '/css';
        $exclude = array('addons', self::$less_backup_dirname, self::$css_backup_dirname);

        $css_files = fn_get_dir_contents($from, false, true, '.css', '', true, $exclude);

        list($active_addons) = fn_get_addons(array('type' => 'active'));

        foreach ($active_addons as $addon_name => $addon) {
            $css_files = array_merge(
                $css_files,
                fn_get_dir_contents($from . "/addons/$addon_name", false, true, '.css', "addons/$addon_name/", true)
            );
        }

        return $css_files;
    }

    /**
     * Get URL to the file with joint theme CSS
     *
     * @return mixed Url or false on failure
     */
    public function getCssUrl()
    {
        $res = $this->fetchFrontendStyles();

        if (!preg_match('/href="([^"]+)"/is', $res, $m)) {
            return false;
        }

        return $m[1];
    }

    /**
     * Get theme manifest information
     *
     * @return array Manifest information
     */
    public function &getManifest()
    {
        if (empty($this->manifest)) {
            if (file_exists($this->theme_path . '/' . THEME_MANIFEST)) {
                $manifest_path = $this->theme_path . '/' . THEME_MANIFEST;

                $ret = json_decode(fn_get_contents($manifest_path), true);
            } elseif (file_exists($this->theme_path . '/' . THEME_MANIFEST_INI)) {
                $ret = parse_ini_file($this->theme_path . '/' . THEME_MANIFEST_INI);
            } else {
                $ret = array();
            }

            if ($ret) {
                $this->manifest = $ret;
            }
        }

        if (!empty($this->manifest)) {
            // Backward compatibility
            if (isset($this->manifest['logo'])) {
                $this->manifest['theme'] = $this->manifest['logo'];
            }
            if (empty($this->manifest['mail'])) {
                $this->manifest['mail'] = $this->manifest['theme'];
            }
        }

        return $this->manifest;
    }

    /**
     * @param array $manifest_data Manifest data to set
     */
    public function setManifest($manifest_data)
    {
        $this->manifest = $manifest_data;
    }

    /**
     * Get theme manifest information from Themes repository
     *
     * @return array Manifest information
     */
    public function getRepoManifest()
    {
        $ret = '';

        if (file_exists($this->repo_path . '/' . THEME_MANIFEST)) {
            $manifest_path = $this->repo_path . '/' . THEME_MANIFEST;

            $ret = json_decode(fn_get_contents($manifest_path), true);
        } elseif (file_exists($this->repo_path . '/' . THEME_MANIFEST_INI)) {
            $ret = parse_ini_file($this->repo_path . '/' . THEME_MANIFEST_INI);
        }

        return $ret;
    }

    /**
     * Save theme manifest information
     *
     * @return boolean Result
     */
    public function saveManifest()
    {
        if (empty($this->manifest)) {
            return false;
        }

        return fn_put_contents($this->theme_path . '/' . THEME_MANIFEST, json_encode($this->manifest));
    }

    /**
     * Get theme name
     *
     * @return string Theme name
     */
    public function getThemeName()
    {
        return $this->theme_name;
    }

    /**
     * Get absolute theme path
     *
     * @return string Absolute theme path
     */
    public function getThemePath()
    {
        return $this->theme_path;
    }

    /**
     * Gets relative theme path
     *
     * @return string Theme path
     */
    public function getThemeRelativePath()
    {
        return $this->relative_path;
    }

    /**
     * Gets repository theme path
     *
     * @return string Theme path
     */
    public function getThemeRepoPath()
    {
        return $this->repo_path;
    }

    /**
     * Gets theme of specified area and company
     *
     * @param string   $area       Area (C/A) to get theme for
     * @param int|null $company_id Company identifier
     *
     * @return Themes Theme instance
     */
    public static function areaFactory($area = AREA, $company_id = null)
    {
        if (!isset(self::$area_instances[$area . $company_id])) {
            self::$area_instances[$area . $company_id] = fn_get_theme_path('[theme]', $area, $company_id);
        }

        return self::factory(self::$area_instances[$area . $company_id]);
    }

    /**
     * @param string $theme_name
     *
     * @return self
     */
    public static function factory($theme_name)
    {
        if (empty(self::$instances[$theme_name])) {
            self::$instances[$theme_name] = new self($theme_name);
        }

        return self::$instances[$theme_name];
    }

    /**
     * Get LESS reflection (information necessary to precompile LESS): LESS import dirs and structured output
     *
     * @return array LESS reflection
     */
    protected function getLessReflection()
    {
        if (empty($this->less_reflection)) {

            $this->fetchFrontendStyles(array('reflect_less' => true));

            $this->less_reflection = json_decode(
                fn_get_contents(fn_get_cache_path(false) . 'less_reflection.json'), true
            );
        }

        return $this->less_reflection;
    }

    /**
     * Fetch frontend styles
     *
     * @param array Params
     *
     * @return string Frontend styles
     */
    protected function fetchFrontendStyles($params = array())
    {
        fn_clear_cache('assets', 'design/');

        $style_id = Registry::get('runtime.layout.style_id');
        if (empty($style_id)) {
            Registry::set('runtime.layout.style_id', Styles::factory($this->theme_name)->getDefault());
        }

        $view = \Tygh::$app['view'];

        $view->setArea('C');

        $view->assign('use_scheme', true);
        $view->assign('include_dropdown', true);

        foreach ($params as $key => $val) {
            $view->assign($key, $val);
        }

        $ret = $view->fetch('common/styles.tpl');

        $view->setArea(AREA);

        return $ret;
    }

    /**
     * Compile chunk of LESS output and save the result in the file
     *
     * @param string $less_output Chunk of LESS output
     * @param string $css_path    The path where the precompiled LESS will be saved
     *
     * @return boolean Result
     */
    protected function convertChunkToCss($less_output, $css_path)
    {
        $less = $this->getLess();

        $less_reflection = $this->getLessReflection();

        $less->setImportDir($less_reflection['import_dirs']);

        Registry::set('runtime.layout', Layout::instance()->getDefault($this->theme_name));

        $from_path = Storage::instance('assets')->getAbsolutePath($this->relative_path . '/css');

        $compiled_less = $less->customCompile($less_output, $from_path, array(), '', 'C');

        $res = fn_put_contents($css_path . '/' . self::$compiled_less_filename, $compiled_less);

        if ($res === false) {
            return false;
        }

        return true;
    }

    /**
     * Remove LESS files
     *
     * @param string $from       The directory the LESS files are removed from
     * @param string $backup_dir Backup directory
     * @param array  $exclude    The list of directories to skip while removing
     *
     * @return boolean Result
     */
    protected function removeLessFiles($from, $backup_dir, $exclude = array())
    {
        $less_files = fn_get_dir_contents($from, false, true, '.less', '', true, $exclude);

        foreach ($less_files as $less_file) {

            if (!empty($backup_dir)) {

                if (!(
                    fn_mkdir(dirname($backup_dir . '/' . $less_file))
                    && fn_copy($from . '/' . $less_file, $backup_dir . '/' . $less_file)
                )) {
                    return false;
                }

            }

            fn_rm($from . '/' . $less_file);
        }

        return true;
    }

    /**
     * Get LESS compiler instance
     *
     * @return object LESS compiler instance
     */
    protected function getLess()
    {
        if ($this->less === null) {
            $this->less = new Less;
        }

        return $this->less;
    }

    /**
     * Gets theme setting overrides
     *
     * @param  string $lang_code 2-letter language code
     * @return array  Theme setting overrides
     */
    public function getSettingsOverrides($lang_code = CART_LANGUAGE)
    {
        if (!self::$settings_overrides) {

            $manifest = $this->getManifest();

            if (!empty($manifest['settings_overrides'])) {
                $settings = array();

                $settings_overrides = $manifest['settings_overrides'];
                foreach ($settings_overrides as $section_name => $setting_group) {
                    $section = Settings::instance()->getSectionByName($section_name);
                    if ($section) {
                        $settings[$section_name] = array(
                            'name' => Settings::instance()->getSectionName($section['section_id']),
                            'settings' => array()
                        );

                        foreach ($setting_group as $setting_name => $setting_value) {
                            $setting = Settings::instance()->getSettingDataByName($setting_name, null, $lang_code);
                            if ($setting) {
                                if (is_bool($setting_value)) {
                                    $setting_value = $setting_value ? 'Y' : 'N';
                                }
                                if (is_array($setting_value)) {
                                    array_walk_recursive($setting_value, function(&$value, $key) {
                                        if (is_bool($value)) {
                                            $value = $value ? 'Y' : 'N';
                                        }
                                    });
                                }
                                $settings[$section_name]['settings'][$setting_name] = array(
                                    'object_id' => $setting['object_id'],
                                    'name' => $setting['description'],
                                    'value' => $setting_value,
                                    'current_value_readable' => Settings::getValueReadable($setting),
                                    'new_value_readable' => Settings::getValueReadable($setting, $setting_value)
                                );
                            }
                        }
                    }
                }

                self::$settings_overrides = $settings;
            }
        }

        return self::$settings_overrides;
    }

    /**
     * Overrides settings values from theme manifest file
     *
     * @param array $settings   Settings to set
     * @param int   $company_id Company identifier
     */
    public function overrideSettings($settings = null, $company_id = null)
    {
        $theme_settings = $this->getSettingsOverrides();

        foreach ($theme_settings as $section_data) {
            foreach ($section_data['settings'] as $setting_name => $setting) {
                if (is_null($settings) || in_array($setting['object_id'], $settings)) {
                    Settings::instance($company_id)->updateValueById($setting['object_id'], $setting['value'], $company_id);
                }
            }
        }
    }

    /**
     * Creates a clone of the theme.
     *
     * @param string $clone_name Name of the new theme
     * @param array  $clone_data Array with "title" and "description" fields for the new theme
     * @param int    $company_id ID of the owner company for the new theme
     *
     * @return bool Whether cloning has succeed
     */
    public function cloneAs($clone_name, $clone_data = array(), $company_id = 0)
    {
        $cloned = new self($clone_name);

        if (file_exists($cloned->getThemePath())) {
            fn_set_notification('W', __('warning'), __('warning_theme_clone_dir_exists'));

            return false;
        }

        $source_manifest = $this->getManifest();
        $rewrite_parent = $this->getParent();

        if ($rewrite_parent) {
            // copy all parent theme files
            $file_status = fn_install_theme_files($this->getThemeName(), $cloned->getThemeName(), false);
        } else {
            // just create directory
            $file_status = fn_mkdir(fn_get_theme_path('[themes]/' . $clone_name, 'C'));
        }
        if (!$file_status) {
            return false;
        }

        $manifest = $cloned->getManifest();
        if (isset($clone_data['title'])) {
            $manifest['title'] = $clone_data['title'];
        }
        if (isset($clone_data['description'])) {
            $manifest['description'] = $clone_data['description'];
        }
        if ($rewrite_parent) {
            $manifest['parent_theme'] = $source_manifest['parent_theme'];
        } else {
            $manifest['parent_theme'] = $this->getThemeName();
        }

        // Put logos of current layout to manifest
        $logos = fn_get_logos(Registry::get('runtime.company_id'));
        foreach ($logos as $type => $logo) {
            if (!empty($logo['image'])) {
                $filename = fn_basename($logo['image']['relative_path']);
                Storage::instance('images')->export(
                    $logo['image']['relative_path'],
                    $cloned->getThemePath() . '/media/images/' . $filename
                );
                $manifest[$type] = 'media/images/' . $filename;
            }
        }

        if (isset($source_manifest['settings_overrides'])) {
            $manifest['settings_overrides'] = $source_manifest['settings_overrides'];
        }

        $cloned->setManifest($manifest);
        $cloned->saveManifest();

        // Clone selected styles
        if (!$rewrite_parent) {
            $styles_provider = Styles::factory($this->getThemeName());
            $styles_acceptor = Styles::factory($cloned->getThemeName());
            fn_mkdir($styles_acceptor->getStylesPath());

            $def_layout = Layout::instance()->getDefault($this->getThemeName());
            $extensions = array('css', 'less', 'png');
            foreach ($extensions as $extension) {
                // Style files
                if (is_file($styles_provider->getStyleFile($def_layout['style_id'], $extension))) {
                    fn_copy(
                        $styles_provider->getStyleFile($def_layout['style_id'], $extension),
                        $styles_acceptor->getStyleFile($def_layout['style_id'], $extension)
                    );
                }
            }

            // Patterns
            if (is_dir($this->getThemePath() . '/media/images/patterns/' . $def_layout['style_id'])) {
                fn_copy(
                    $this->getThemePath() . '/media/images/patterns/' . $def_layout['style_id'],
                    $cloned->getThemePath() . '/media/images/patterns/' . $def_layout['style_id']
                );
            }

            // Styles manifest
            $manifest = array(
                'default_style' => $def_layout['style_id'],
                'default' => array(
                    $def_layout['style_id'] => true
                )
            );
            $styles_acceptor->setManifest($manifest);
            $styles_acceptor->saveManifest();

            // Styles schema
            $styles_acceptor->setSchema($styles_provider->getSchema());
            $styles_acceptor->saveSchema();
        }

        return true;
    }

    /**
     * Gets directories of theme
     *
     * @param int $search_options Directories search options
     *
     * @return array Array of theme names as keys and theme directories as values
     */
    public function getThemeDirs($search_options = self::USE_DEFAULT)
    {
        if (empty($this->content_paths[$search_options])) {
            $searched_themes = array($this);

            if ($this->area == 'C') {
                if ($parent = $this->getParent()) {
                    $searched_themes[] = $parent;
                }
                if ($search_options & self::USE_BASE) {
                    $searched_themes[] = Themes::factory(Registry::get('config.base_theme'));
                }
                if ($search_options & self::USE_LEGACY) {
                    $searched_themes[] = Themes::factory('basic');
                }
            }

            foreach ($searched_themes as $theme) {
                $this->content_paths[$search_options][$theme->getThemeName()] = array(
                    self::PATH_ABSOLUTE => rtrim($theme->getThemePath(),         '/') . '/',
                    self::PATH_RELATIVE => rtrim($theme->getThemeRelativePath(), '/') . '/',
                    self::PATH_REPO     => rtrim($theme->getThemeRepoPath(),     '/') . '/',
                );
            }
        }

        return $this->content_paths[$search_options];
    }

    /**
     * Converts absolute theme path to relative path
     *
     * @param string $path Path
     *
     * @return string Relative to theme directory path
     */
    public function convertToRelativePath($path)
    {
        foreach ($this->content_paths as $content_path) {
            $abs_paths = fn_array_column($content_path, self::PATH_ABSOLUTE);
            $path = trim(str_replace($abs_paths, '', $path), '/');
        }

        return $path;
    }

    /**
     * Gets contents of directory in theme optionally merged with parent theme
     *
     * @param array  $params         Parameters for ::fn_get_dir_contents
     * @param string $strategy       Merging content strategy
     * @param string $dir_type       Directory where to search content (absolute theme path or themes repository path)
     * @param int    $search_options Directories search options
     *
     * @return array Contents of directory
     */
    public function getDirContents($params = array(), $strategy = self::STR_SINGLE, $dir_type = self::PATH_ABSOLUTE, $search_options = self::USE_DEFAULT)
    {
        $default_params = array(
            'dir' => '',
            'get_dirs' => true,
            'get_files' => true,
            'extension' => '',
            'prefix' => '',
            'recursive' => false,
            'exclude' => array()
        );
        $params = array_merge($default_params, $params);
        $search_paths = $this->getThemeDirs($search_options);
        if ($strategy == self::STR_MERGE) {
            $search_paths = array_reverse($search_paths);
        }
        $params['dir'] = $this->convertToRelativePath($params['dir']);

        $contents = array();
        foreach ($search_paths as $theme_name => $path_info) {
            $func_params = $params;
            $func_params['dir'] = $path_info[$dir_type] . $params['dir'];
            $dir_contents = call_user_func_array('fn_get_dir_contents', $func_params);
            foreach ($dir_contents as $file) {
                $contents[$file] = array(
                    'theme' => $theme_name,
                    self::PATH_ABSOLUTE => $path_info[self::PATH_ABSOLUTE] . rtrim($params['dir'], '/') . '/' . $file,
                    self::PATH_RELATIVE => $path_info[self::PATH_RELATIVE] . rtrim($params['dir'], '/') . '/' . $file,
                    self::PATH_REPO     => $path_info[self::PATH_REPO]     . rtrim($params['dir'], '/') . '/' . $file,
                );
            }
            if ($strategy == self::STR_EXCLUSIVE || !empty($contents) && $strategy == self::STR_SINGLE) {
                break;
            }
        }

        return $contents;
    }

    /**
     * Gets path to content in theme or in parent theme
     *
     * @param string $path           Path to content
     * @param string $content_type   Content type
     * @param string $dir_type       Directory where to search content (absolute theme path or themes repository path)
     * @param int    $search_options Directories search options
     *
     * @return array|bool Theme name, absolute and relative path to file if one exists, false otherwise
     */
    public function getContentPath($path = '', $content_type = self::CONTENT_FILE, $dir_type = self::PATH_ABSOLUTE, $search_options = self::USE_DEFAULT)
    {
        $path = $this->convertToRelativePath($path);

        switch ($content_type) {
            case self::CONTENT_FILE:
                $check_function = 'is_file';
                break;
            case self::CONTENT_DIR:
                $check_function = 'is_dir';
                break;
            default:
                $check_function = 'file_exists';
        }

        foreach ($this->getThemeDirs($search_options) as $theme_name => $path_info) {
            if (call_user_func($check_function, $path_info[$dir_type] . $path)) {
                return array(
                    'theme' => $theme_name,
                    self::PATH_ABSOLUTE => $path_info[self::PATH_ABSOLUTE] . $path,
                    self::PATH_RELATIVE => $path_info[self::PATH_RELATIVE] . $path,
                    self::PATH_REPO     => $path_info[self::PATH_REPO]     . $path
                );
            }
        }

        return false;
    }

    /**
     * Gets theme's parent
     *
     * @return Themes|null Parent theme name if exists, null otherwise
     */
    public function getParent()
    {
        $manifest = $this->getManifest();
        if (empty($manifest)) {
            $manifest = $this->getRepoManifest();
        }
        if (!empty($manifest['parent_theme']) && $manifest['parent_theme'] != $this->getThemeName()) {
            return self::factory($manifest['parent_theme']);
        }

        return null;
    }

    /**
     * Gets path to translations for the specified language.
     *
     * @param string $lang_code 2-letters language identifier
     *
     * @return string|bool Path to file if exists of false otherwise
     */
    public function getPoPath($lang_code)
    {
        if ($file_info = $this->getContentPath("langs/{$lang_code}.po")) {
            return $file_info[self::PATH_ABSOLUTE];
        }

        return false;
    }

    /**
     * Gets theme default language.
     *
     * @return string Two-letter language code
     */
    public function getDefaultLanguage()
    {
        if (!isset($this->default_language)) {

            $this->default_language = DEFAULT_LANGUAGE;

            $manifest = $this->getManifest();

            if (isset($manifest['default_language'])) {
                // use the language specified in the manifest
                $this->default_language = $manifest['default_language'];
            } else {
                // check if there is a langfile for default language
                if ($this->getContentPath('langs/' . DEFAULT_LANGUAGE . '.po')) {
                    $this->default_language = DEFAULT_LANGUAGE;
                } else {
                    $languages = $this->getDirContents(array(
                        'dir' => 'langs',
                        'get_dirs' => false,
                        'get_files' => true,
                        'extension' => '.po'
                    ));

                    // use the first of all langfiles
                    if (!empty($languages)) {
                        reset($languages);
                        $this->default_language = str_replace('.po', '', key($languages));
                    }
                }
            }
        }

        return $this->default_language;
    }

    /**
     * Gets translations from theme files.
     *
     * @param bool   $only_originals   Gets original values instead of language values
     * @param array  $languages        2-letters language identifiers to get translations for
     *                                 When empty specified, all available languages will be used.
     *
     * @return array List of translations
     */
    public function getLanguageValues($only_originals = false, $languages = array())
    {
        $default_language = $this->getDefaultLanguage();

        if (!$languages) {
            $languages = array_keys(Languages::getAll());
        }

        $language_variables = array();

        $default_lang_pack = $this->getPoPath($default_language);

        foreach ($languages as $lang_code) {
            $lang_data = array();

            $po_path = $this->getPoPath($lang_code);
            if (!empty($po_path)) {
                $lang_data = Po::getValues($po_path, 'Languages');
            }

            if (!empty($default_lang_pack)) {
                $lang_data = array_merge(Po::getValues($default_lang_pack, 'Languages'), $lang_data);
            }

            foreach ($lang_data as $var_name => $var_data) {
                $value = implode('', $var_data['msgstr']);
                $original_value = $var_data['msgid'];
                $value = empty($value) ? $original_value : $value;

                if ($only_originals) {
                    $language_variables[] = array(
                        'msgctxt' => $var_name,
                        'msgid' => $original_value,
                    );
                } else {
                    $language_variables[] = array(
                        'lang_code' => $lang_code,
                        'name' => $var_data['id'],
                        'value' => $value,
                    );
                }
            }
        }

        return $language_variables;
    }

    /**
     * Installs theme translations.
     *
     * @param array  $languages 2-letters language identifiers to install translations for.
     *                          When empty specified, all available languages will be used.
     *
     * @return bool Always true
     */
    public function installTranslations($languages = array())
    {
        $languages = (array) $languages;

        // Add optional language variables
        $language_variables = $this->getLanguageValues(false, $languages);
        if (!empty($language_variables)) {
            db_query('REPLACE INTO ?:language_values ?m', $language_variables);
        }

        // Get only original values
        $language_variables = $this->getLanguageValues(true, $languages);
        if (!empty($language_variables)) {
            db_query('REPLACE INTO ?:original_values ?m', $language_variables);
        }
        
        return true;
    }
}
