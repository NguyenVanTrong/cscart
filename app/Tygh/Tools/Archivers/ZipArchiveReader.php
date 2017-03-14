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


namespace Tygh\Tools\Archivers;

/**
 * Class ZipArchiveReader
 * @package Tygh\Tools\Archivers
 */
class ZipArchiveReader implements IArchiveReader
{
    /** @var string  */
    protected $file;

    /**
     * ZipArchiveReader constructor
     *
     * @param string $file Path to archive
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * @inheritDoc
     */
    public function extractTo($dir)
    {
        $result = false;
        $zip = new \ZipArchive();

        if ($zip->open($this->file) === true) {
            $result = $zip->extractTo($dir);
        }
        $zip->close();
        $zip = null;

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getFiles($only_root = false)
    {
        $files = array();
        $zip = new \ZipArchive;

        if ($zip->open($this->file)) {
            $num_files = $zip->numFiles;

            $counter = 0;
            for ($i = 0; $i < $num_files; $i++) {
                $file = $zip->getNameIndex($i);
                $parent_directories = $this->getParentDirStack($file);
                if ($only_root) {
                    if (empty($parent_directories)) {
                        $files[$file] = $counter++;
                    } else {
                        $files[end($parent_directories)] = $counter++;
                    }
                } else {
                    $files[$file] = $counter++;
                    foreach ($parent_directories as $parent_dir_path) {
                        $files[$parent_dir_path] = $counter++;
                    }
                }
            }

            $files = array_flip($files);
            $zip->close();
        }
        $zip = null;
        sort($files);

        return $files;
    }

    /**
     * @param string $path
     * @return array
     */
    private function getParentDirStack($path)
    {
        $directories = array();
        while ($path = dirname($path)) {
            if (!empty($path) && $path !== '.' && $path !== DIRECTORY_SEPARATOR) {
                $directories[] = rtrim($path, '\\/') . DIRECTORY_SEPARATOR;
            } else {
                break;
            }
        }

        return $directories;
    }
}