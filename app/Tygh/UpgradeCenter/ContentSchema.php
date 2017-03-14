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

namespace Tygh\UpgradeCenter;
use Tygh\Themes\Themes;

/**
 * Class ContentSchema
 * @package Tygh\UpgradeCenter
 */
class ContentSchema implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /** @var array */
    private $data = array();

    /** @var array */
    private $config = array();

    /** @var null|array  */
    private $themes_files = null;

    /**
     * PackageSchema constructor.
     * @param $data
     */
    public function __construct($data, $config)
    {
        $this->data = (array) $data;
        $this->config = (array) $config;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function &offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Get files
     *
     * @param  bool  $include_themes Flag include themes files
     * @return array
     */
    public function getFiles($include_themes = false)
    {
        if (empty($this->data['files'])) {
            return array();
        }

        $result = $this->data['files'];

        if ($include_themes) {
            $result += $this->getThemesFiles();
        }

        return $result;
    }

    /**
     * Get Themes Files
     *
     * @return array
     */
    public function getThemesFiles()
    {
        if (empty($this->data['files'])) {
            return array();
        }

        if (!isset($this->themes_files)) {
            $this->themes_files = $repo_files = array();
            $repo_path = str_replace($this->config['dir']['root'] . '/', '', $this->config['dir']['themes_repository']);

            // Process themes_repository
            foreach ($this->data['files'] as $file_path => $file_data) {
                if (strpos($file_path, $repo_path) !== false) {
                    $path = str_replace($repo_path, '', $file_path);
                    $path = explode('/', $path);

                    $theme_name = array_shift($path);
                    $file_data['source'] = $file_path;

                    $repo_files[$theme_name][implode('/', $path)] = $file_data;
                }
            }

            $themes = fn_get_dir_contents($this->config['dir']['root'] . '/design/themes/');
            foreach ($themes as $theme_name) {
                if (!empty($repo_files[$theme_name])) {
                    foreach ($repo_files[$theme_name] as $file_path => $file_data) {
                        $this->themes_files['design/themes/' . $theme_name . '/' . $file_path] = $file_data;
                    }
                }
            }
        }

        return $this->themes_files;
    }
}
