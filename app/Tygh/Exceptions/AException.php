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

namespace Tygh\Exceptions;

use Tygh\Debugger;
use Tygh\Ajax;
use Tygh\Development;

abstract class AException extends \Exception
{
    /**
     * Outputs exception information
     *
     * @return void
     */
    public function output()
    {
        if (!defined('AJAX_REQUEST') && Ajax::validateRequest($_REQUEST)) {
            // Return valid JS in ajax requests if the 'fail' status was thrown before ajax initialization
            header('Content-type: application/json');
            $message = json_encode(array('error' => $this->message));
            if (!empty($_REQUEST['callback'])) {
                $message = $_REQUEST['callback'] . "(" . $message . ");";
            }
            echo($message);
            exit;

        } elseif (defined('CONSOLE') || Debugger::isActive() || (defined('DEVELOPMENT') && DEVELOPMENT)) {
            echo $this->printDebug(defined('CONSOLE'));
        } else {
            $debug = "<!--\n" . $this->printDebug(true) . "\n-->";

            Development::showStub(array(
                '[title]' => 'Service unavailable',
                '[banner]' => 'Service<br/> unavailable',
                '[message]' => 'Sorry, service is temporarily unavailable.'
            ), $debug);
        }
    }

    /**
     * Returns debug information
     *
     * @param boolean $plain_text output as plain text
     *
     * @return string Formatted debug info
     */
    protected function printDebug($plain_text = false)
    {
        $file = str_replace(DIR_ROOT . '/', '', $this->file);

        $trace
            = <<< EOU
<div style="margin: 0 0 30px 0; font-size: 1em; padding: 0 10px;">
<h2>{$this->getErrorTitle()}</h2>

<h3>Message</h3>
<p style="margin: 0; padding: 0 0 20px 0;">{$this->message}</p>

<h3>Error at</h3>
<p style="margin: 0; padding: 0 0 20px 0;">{$file}, line: {$this->line}</p>

<h3>Backtrace</h3>
<table cellspacing="0" cellpadding="3" style="font-size: 0.9em;">
EOU;
        $i = 0;
        if ($backtrace = $this->getTrace()) {

            $func = '';
            foreach ($backtrace as $v) {
                if (empty($v['file'])) {
                    $func = $v['function'];
                    continue;
                } elseif (!empty($func)) {
                    $v['function'] = $func;
                    $func = '';
                }
                $i = ($i == 0) ? 1 : 0;
                $color = ($i == 0) ? "#CCCCCC" : "#EEEEEE";
                if (strpos($v['file'], DIR_ROOT) !== false) {
                    $v['file'] = str_replace(DIR_ROOT . '/', '', $v['file']);
                }

                $trace .= "<tr bgcolor='$color'><td>File:</td><td>$v[file]</td></tr>\n";
                $trace .= "<tr bgcolor='$color'><td>Line:</td><td>$v[line]</td></tr>\n";
                $trace .= "<tr bgcolor='$color'><td>Function:</td><td><b>$v[function]</b></td></tr>\n\n";
            }
        }

        $trace .= '</table></div>';

        if ($plain_text) {
            $trace = strip_tags($trace);
        }

        return $trace;
    }

    public function getErrorTitle()
    {
        return __CLASS__;
    }
}
