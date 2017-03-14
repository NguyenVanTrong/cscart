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

namespace Tygh\ElFinder;

class Volume extends \elFinderVolumeLocalFileSystem
{
    protected function getArchivers($use_cache = true)
    {
        $arcs = array(
            'create'  => array(),
            'extract' => array()
        );

        $obj = $this; // php 5.3 compatibility

        if (class_exists('ZipArchive')) {
            $arcs['extract']['application/zip'] = array(
                'cmd' => function($archive, $path) use ($obj) {
                    $zip = new \ZipArchive;
                    if ($zip->open($archive)) {
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $stat = $zip->statIndex($i);
                            if (empty($stat['size'])) { // directory
                                continue;
                            }
                            $filename = $stat['name'];

                            $newfile = $obj->tyghDecodeFilename($filename);

                            $obj->tyghMkdir(dirname($path .'/' . $newfile));

                            copy('zip://' . $archive . '#' . $filename, $path .'/' . $newfile);
                        }
                        $zip->close();

                        return true;
                    }

                    return false;
                },
                'ext' => 'zip'
            );

            $arcs['create']['application/zip'] = array(
                'cmd' => function($archive, $files) use ($obj) {
                    $zip = new \ZipArchive;
                    if ($zip->open($archive, \ZipArchive::CREATE) === true) {
                        $base_path = dirname($archive);
                        foreach ($files as $file) {
                            $path = $base_path . DIRECTORY_SEPARATOR . $file;
                            if (is_file($path)) {
                                $zip->addFile($path, $obj->tyghEncodeFilename($file));
                            } elseif (is_dir($path)) {

                                foreach ($obj->tyghGetFiles($path) as $_file) {
                                    $zip->addFile($path . DIRECTORY_SEPARATOR . $_file, $obj->tyghEncodeFilename($_file));
                                }
                            }
                        }
                        $zip->close();

                        return true;
                    }

                    return false;
                },
                'ext' => 'zip'
            );

        }

        if (class_exists('PharData')) {
            $arcs['extract']['application/x-gzip'] = array(
                'cmd' => function($archive, $path) {
                    $phar = new \PharData($archive);
                    $phar->extractTo($path, null, true);
                },
                'ext' => 'tgz'
            );
        }

        return $arcs;
    }

    protected function _unpack($path, $arc)
    {
        $dir = $this->_dirname($path);
        $arc['cmd']($path, $dir);
    }

    protected function _archive($dir, $files, $name, $arc)
    {
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        $arc['cmd']($path, $files);

        return file_exists($path) ? $path : false;
    }

    public function tyghGetFiles($path, $base_path = '')
    {
        $files = array();
        if ($dh = opendir($path)) {
            while (false !== ($file = readdir($dh))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                $file_path = $path . '/' . $file;
                if (is_dir($file_path)) {
                    $files += $this->tyghGetFiles($file_path, (!empty($base_path) ? $base_path . '/' : '') . $file);
                } else {
                    $files[] = (!empty($base_path) ? $base_path . '/' : '') . $file;
                }
            }
            closedir($dh);
        }

        return $files;
    }

    public function tyghIsUTF8($str)
    {
        $c = 0; $b = 0;
        $bits = 0;
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c >= 254)) {
                    return false;
                } elseif ($c >= 252) {
                    $bits = 6;
                } elseif ($c >= 248) {
                    $bits = 5;
                } elseif ($c >= 240) {
                    $bits = 4;
                } elseif ($c >= 224) {
                    $bits = 3;
                } elseif ($c >= 192) {
                    $bits = 2;
                } else {
                    return false;
                }

                if (($i + $bits) > $len) {
                    return false;
                }

                while ($bits > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) {
                        return false;
                    }
                    $bits--;
                }
            }
        }

        return true;
    }

    public function tyghMkdir($dir, $perms = 0777)
    {
        if (!is_dir($dir)) {
            $old = umask(0);
            $res = mkdir($dir, $perms, true);
            umask($old);

            return $res;
        }
    }

    /**
     * Encodes non-UTF-8 filenames for storing in ZIP archive
     *
     * @param  string $filename Name of a file to be archived
     * @return string Encoded filename
     */
    public function tyghEncodeFilename($filename)
    {
        if ($this->tyghIsUTF8($filename) && function_exists('iconv')) {
            return iconv('utf-8', 'cp866', $filename);
        }

        return $filename;
    }

    /**
     * Decodes non-UTF-8 filenames when extracting ZIP archive
     *
     * @param  string $filename Name of a file to be extracted
     * @return string Decoded filename
     */
    public function tyghDecodeFilename($filename)
    {
        if (!$this->tyghIsUTF8($filename) && function_exists('iconv')) {
            return iconv('cp866', 'utf-8', $filename);
        }

        return $filename;
    }
}
