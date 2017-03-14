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


namespace Tygh\Twig;

/**
 * The class that extends the standard Twig class for template caching; it solves problems with file permissions.
 *
 * @package Tygh\Twig
 */
class TwigCacheFilesystem extends \Twig_Cache_Filesystem
{
    /**
     * @inheritDoc
     */
    public function __construct($directory, $options = 0)
    {
        if (!is_dir($directory)) {
            fn_mkdir($directory);
        }

        parent::__construct($directory, $options);
    }

    /**
     * @inheritDoc
     */
    public function write($key, $content)
    {
        $file_exists = file_exists($key);

        parent::write($key, $content);

        if (!$file_exists) {
            @chmod($key, DEFAULT_FILE_PERMISSIONS);
            @chmod(dirname($key), DEFAULT_DIR_PERMISSIONS);
        }
    }
}