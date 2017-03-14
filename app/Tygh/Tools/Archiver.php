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


namespace Tygh\Tools;

use Tygh\Tools\Archivers\ArchiverException;
use Tygh\Tools\Archivers\IArchiveCreator;
use Tygh\Tools\Archivers\IArchiveReader;
use Tygh\Tools\Archivers\PearArchiveReader;
use Tygh\Tools\Archivers\PharArchiveCreator;
use Tygh\Tools\Archivers\PharArchiveReader;
use Tygh\Tools\Archivers\ZipArchiveCreator;
use Tygh\Tools\Archivers\ZipArchiveReader;

/**
 * Class Archive
 * @package Tygh\Tools
 */
class Archiver
{
    /**
     * Extract archive to dir
     *
     * @param string $path   Path to archive file
     * @param string $dir    Directory
     * @throws \Exception
     * @return bool
     */
    public function extractTo($path, $dir)
    {
        $reader = $this->open($path);

        try {
            $result = $reader->extractTo($dir);
        } catch (\PharException $e) {
            if (class_exists('Archive_Tar')) {
                try {
                    $reader = new PearArchiveReader($path);
                    $result = $reader->extractTo($dir);
                } catch (\Exception $tar_exception) {
                    throw $e;
                }
            } else {
                throw $e;
            }
        }

        return $result;
    }

    /**
     * Get archive files
     *
     * @param string $path      Path to archive file
     * @param bool   $root_only Only root directory files
     * @return array|false
     */
    public function getFiles($path, $root_only = false)
    {
        $reader = $this->open($path);
        $result = $reader->getFiles($root_only);

        if (empty($result) && $reader instanceof PharArchiveReader && class_exists('Archive_Tar')) {
            try {
                $reader = new PearArchiveReader($path);
                $result = $reader->getFiles($root_only);
            } catch (\Exception $e) {}
        }

        return !empty($result) ? $result : false;
    }

    /**
     * Compress files to archive
     *
     * @param string $path    Path to archive file
     * @param array  $files   List of files or directories
     * @return bool
     */
    public function compress($path, array $files)
    {
        $result = true;
        $creator = $this->create($path);

        foreach ($files as $relative_path => $absolute_path) {
            if (is_dir($absolute_path)) {
                if (!$creator->addDir($absolute_path)) {
                    $result = false;
                    break;
                }
            } else {
                if (!$creator->addFile($absolute_path, $relative_path)) {
                    $result = false;
                    break;
                }
            }
        }

        $creator->close();

        return $result;
    }

    /**
     * Get archive creator object
     *
     * @param string $path Path to archive
     * @return IArchiveCreator
     * @throws ArchiverException
     */
    public function create($path)
    {
        if (file_exists($path)) {
            throw new ArchiverException(__('error_file_already_exists', array('[file]' => $path)));
        }

        $result = null;
        $ext = $this->getFileExtension($path);

        if ($ext === 'zip') {
            if (class_exists('ZipArchive')) {
                $result = new ZipArchiveCreator($path);
            } elseif (class_exists('PharData')) {
                $result = new PharArchiveCreator($path);
            } else {
                throw new ArchiverException(__('error_class_zip_archive_not_found'));
            }
        } elseif ($ext === 'tgz' || $ext === 'gz') {
            if (class_exists('PharData')) {
                $result = new PharArchiveCreator($path);
            } else {
                throw new ArchiverException(__('error_class_phar_data_not_found'));
            }
        } else {
            throw new ArchiverException(__('error_unknown_archive_format', array('[ext]' => $ext)));
        }

        return $result;
    }

    /**
     * Get archive reader object
     *
     * @param string $path Path to archive
     * @return IArchiveReader
     * @throws ArchiverException
     */
    public function open($path)
    {
        if (!file_exists($path)) {
            throw new ArchiverException(__('error_file_not_found', array('[file]' => $path)));
        }

        $result = null;
        $ext = $this->getFileExtension($path);

        if ($ext === 'zip') {
            if (class_exists('ZipArchive')) {
                $result = new ZipArchiveReader($path);
            } else {
                throw new ArchiverException(__('error_class_zip_archive_not_found'));
            }
        } elseif ($ext === 'tgz' || $ext === 'gz') {
            if (class_exists('PharData')) {
                $result = new PharArchiveReader($path);
            } elseif (class_exists('Archive_Tar')) {
                $result = new PearArchiveReader($path);
            } else {
                throw new ArchiverException(__('error_class_phar_data_not_found'));
            }
        } else {
            throw new ArchiverException(__('error_unknown_archive_format', array('[ext]' => $ext)));
        }

        return $result;
    }

    /**
     * @param string $file
     * @return string
     */
    protected function getFileExtension($file)
    {
        return strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
    }
}