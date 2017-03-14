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
 * Class ZipArchiveCreator
 * @package Tygh\Tools\Archivers
 */
class ZipArchiveCreator implements IArchiveCreator
{
    /** @var string  */
    protected $file;

    /** @var \ZipArchive */
    protected $zip;

    /**
     * ZipArchiveCreator constructor
     *
     * @param string $file Path to archive
     * @throws \Exception
     */
    public function __construct($file)
    {
        $this->zip = new \ZipArchive;
        $this->file = $file;

        if ($this->zip->open($this->file, \ZipArchive::CREATE) !== true) {
            throw new \Exception('Unable create archive');
        }
    }

    /**
     * @inheritDoc
     */
    public function addFile($file, $local_name)
    {
        return $this->zip->addFile($file, $local_name);
    }

    /**
     * @inheritDoc
     */
    public function addDir($dir)
    {
        $result = true;

        /**
         * @var \RecursiveDirectoryIterator|\RecursiveIteratorIterator|\SplFileInfo $iterator
         */
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir,
                \FilesystemIterator::SKIP_DOTS |
                \FilesystemIterator::CURRENT_AS_FILEINFO |
                \FilesystemIterator::KEY_AS_PATHNAME
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $key => $item) {
            $path = trim($iterator->getSubPathname(), '\\/');

            /** @var \SplFileInfo $item */
            if ($item->isDir()) {
                $result = $this->zip->addEmptyDir($path);
            } else {
                $result = $this->zip->addFile($item->getPathname(), $path);
            }

            if (!$result) {
                break;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        $this->zip->close();
        $this->zip = null;
    }
}