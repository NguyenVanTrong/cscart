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

namespace Tygh\UpgradeCenter\Migrations;

use Tygh\UpgradeCenter\Log;

class Output extends \Symfony\Component\Console\Output\Output
{
    protected $buffer;
    protected $config = array();

    public function doWrite($message, $newline)
    {
        Log::instance($this->config['package_id'])->add($message);
        $this->buffer .= $message . ($newline ? PHP_EOL : '');
    }

    public function getBuffer()
    {
        return $this->buffer;
    }

    public function setConfig($config)
    {
        return $this->config = $config;
    }
}
