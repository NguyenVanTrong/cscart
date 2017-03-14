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
 * Class PearArchiveReader
 * @package Tygh\Tools\Archivers
 */
class PearArchiveReader implements IArchiveReader
{
    /** @var string  */
    protected $file;

    /** @var \Archive_Tar */
    protected $tar;

    /**
     * PearArchiveReader constructor
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
        $tar = $this->initTar();
        return $tar->extract($dir);
    }

    /**
     * @inheritDoc
     */
    public function getFiles($only_root = false)
    {
        $files = array();
        $tar = $this->initTar();

        $items = $tar->listContent();

        foreach ($items as $item) {
            $filename = $item['filename'];

            if (strpos($filename, './') === 0) {
                $filename = substr($filename, 2);
            }

            if (empty($filename)) {
                continue;
            }

            if (!$only_root) {
                $files[] = $filename;
            } elseif (dirname($filename) === '.') {
                $files[] = $filename;
            }
        }
        sort($files);

        return $files;
    }

    /**
     * Init Archive_Tar
     * @return \Archive_Tar
     * @throws \Exception
     */
    protected function initTar()
    {
        $tar = new \Archive_Tar($this->file);
        /** @var \PEAR_Error $error */
        $error = $tar->error_object;

        if ($error !== null) {
            throw new \Exception($error->getMessage());
        }

        return $tar;
    }
}