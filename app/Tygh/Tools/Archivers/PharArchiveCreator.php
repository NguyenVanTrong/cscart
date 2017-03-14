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
 * Class PharArchiveCreator
 * @package Tygh\Tools\Archivers
 */
class PharArchiveCreator implements IArchiveCreator
{
    /** @var string  */
    protected $file;

    /** @var \PharData */
    protected $phar;

    /** @var string */
    protected $extension;

    /**
     * ZipArchiveCreator constructor
     *
     * @param string $file Path to archive
     */
    public function __construct($file)
    {
        $this->file = $file;
        $this->phar = new \PharData($this->file);
        $this->extension = strtolower((string) pathinfo($this->file, PATHINFO_EXTENSION));
    }

    /**
     * @inheritDoc
     */
    public function addFile($file, $local_name)
    {
        try {
            $this->phar->addFile($file, $local_name);
        } catch (\PharException $e) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function addDir($dir)
    {
        try {
            $this->phar->buildFromDirectory($dir);
        } catch (\PharException $e) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        if ($this->extension === 'zip') {
            $this->phar->compressFiles(\Phar::GZ);
            $this->phar = null;
        } else {
            /** @var \PharData $phar */
            $phar = $this->phar->compress(\Phar::GZ, 'tmp.' . $this->extension);
            $this->phar = null;
            $path = $phar->getPath();

            unset($phar);
            rename($path, $this->file);
        }
    }
}