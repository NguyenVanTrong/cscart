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

namespace Tygh\Common;

use Tygh\Registry;

/**
 * Editing robots.txt file
 */
class Robots
{
    public $default = false;
    public $path;

    public function __construct($default = false)
    {
        $this->default = $default;

        $this->path = $this->getPath();
    }

    public function get()
    {
        $content = '';

        if (!empty($this->path) && file_exists($this->path)) {
            $content = file_get_contents($this->path);
        }

        fn_set_hook('robots_get', $this, $content);

        return $content;
    }

    public function save($content)
    {
        $processed = null;

        $this->saveBackup();

        fn_set_hook('robots_save', $this, $processed, $content);

        if (!is_null($processed)) {
            return $processed;
        }

        $res = false;
        if (!empty($this->path)) {
            $res = fn_put_contents($this->path, $content);
        }

        return $res;
    }

    public function restore()
    {
        $default = new self(true);
        $this->save($default->get());
    }

    public function check()
    {
        $result = false;

        if (!empty($this->path)) {
            $result = is_writeable($this->path);
        }

        fn_set_hook('robots_check', $this, $result);

        return $result;
    }

    public function updateViaFtp($content, $settings)
    {
        $this->saveBackup();

        $tmp_file = fn_create_temp_file();
        fn_put_contents($tmp_file, $content);
        $ftp_copy_result = fn_copy_by_ftp($tmp_file, $this->path, $settings);
        fn_rm($tmp_file);

        $status = $ftp_copy_result === true;

        return array($status, $ftp_copy_result);
    }

    protected function getPath()
    {
        $path = Registry::get('config.dir.root');
        if ($this->default) {
            $path .= '/var';
        }
        $path .= '/robots.txt';

        fn_set_hook('robots_get_path', $this, $path);

        return $path;
    }

    protected function saveBackup()
    {
        if (!$this->default) {
            $default = new self(true);
            $default_content = $default->get();
            if (empty($default_content)) { // It first update, need to save original
                $default->save($this->get());
            }
        }
    }
}
