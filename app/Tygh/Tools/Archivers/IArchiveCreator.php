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
 * Interface IArchiveCreator
 * @package Tygh\Tools\Archivers
 */
interface IArchiveCreator
{
    /**
     * ArchiveCreator constructor
     *
     * @param string $file Path to archive file
     */
    public function __construct($file);

    /**
     * Add file to archive
     *
     * @param string $file       Path to file
     * @param string $local_name Local name in archive
     * @return bool
     */
    public function addFile($file, $local_name);

    /**
     * Add directory to archive
     *
     * @param string $dir Path to directory
     * @return bool
     */
    public function addDir($dir);

    /**
     * Finalize and close creating archive
     */
    public function close();
}