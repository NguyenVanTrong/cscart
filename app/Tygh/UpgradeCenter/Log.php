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

namespace Tygh\UpgradeCenter;

/**
 * Class Log
 *
 * @todo Implement Psr\Log\LoggerInterface
 * @package Tygh\UpgradeCenter
 */
class Log
{
    /**
     * Instance of Log
     *
     * @var App $instance
     */
    private static $instance;

    /**
     * Current Package identifier
     *
     * @var string $package_id
     */
    private $package_id = '';

    /**
     * Global config
     *
     * @var array $config
     */
    private $config = array();


    public function drawHeader()
    {
        return $this->drawEmptyLine()->add(array(
            str_repeat('#', 80),
            str_repeat('#', 80),
        ), true, false);
    }

    public function drawEmptyLine()
    {
        return $this->add(PHP_EOL, true, false);
    }

    public function add($message, $append = true, $with_timestamp = true, $prepend_with_newline = true)
    {
        if (is_array($message)) {
            foreach ($message as $msg) {
                $this->add($msg, $append, $with_timestamp, $prepend_with_newline);
            }

            return $this;
        }

        if ($with_timestamp) {
            $message = date('Y-m-d H:i:s', time()) . ': ' . $message;
        }

        if ($prepend_with_newline) {
            $message .= PHP_EOL;
        }

        $flags = $append ? FILE_APPEND : 0;
        file_put_contents($this->getLogFilePath(), $message, $flags);

        $output_message = isset($this->config['upgrade_log']['output_message']) ? $this->config['upgrade_log']['output_message'] : false;

        if ($output_message) {
            fn_echo($message);
        }

        return $this;
    }

    public function lineStart($message)
    {
        return $this->add($message, true, true, false);
    }

    public function lineEnd($message)
    {
        return $this->add($message, true, false, true);
    }

    private function getLogFilePath()
    {
        return $this->config['dir']['root'] . '/var/upgrade/' . $this->package_id . '_log.txt';
    }

    /**
     * Returns instance of Log
     *
     * @return self
     */
    public static function instance($package_id)
    {
        if (empty(self::$instance)) {
            self::$instance = new self($package_id);
        }

        return self::$instance;
    }

    private function __construct($package_id, $config = array())
    {
        $this->package_id = $package_id;

        if (class_exists('\Tygh\Registry')) {
            $this->config = \Tygh\Registry::get('config');
        }

        $this->config = array_merge($this->config, $config);

    }
}
