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
 * Interface IArchiveReader
 * @package Tygh\Tools\Archivers
 */
interface IArchiveReader
{
    /**
     * ArchiveReader constructor
     *
     * @param string $file Path to archive file
     */
    public function __construct($file);

    /**
     * Extract archive
     *
     * @param string $dir Path to directory
     * @return bool
     */
    public function extractTo($dir);

    /**
     * Get files contained in archive
     *
     * @param bool $only_root Only root directory files
     * @return bool
     */
    public function getFiles($only_root = false);
}