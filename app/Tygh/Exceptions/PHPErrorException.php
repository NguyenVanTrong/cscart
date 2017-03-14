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

class PHPErrorException extends AException
{
    public function __construct($message, $type, $filename, $line_number)
    {
        parent::__construct($message, $type);
        $this->file = $filename;
        $this->line = $line_number;
    }

    public function getErrorTitle()
    {
        $titles = array(
            E_ERROR => 'PHP Fatal Error',
            E_PARSE => 'PHP Parse Error',
            E_CORE_ERROR => 'PHP Core Error',
            E_CORE_WARNING => 'PHP Core Warning',
            E_COMPILE_ERROR => 'PHP Compile Error',
            E_COMPILE_WARNING => 'PHP Compile Warning',

            E_NOTICE => 'PHP Notice',
            E_USER_NOTICE => 'Notice',
            E_WARNING => 'PHP Warning',
            E_USER_WARNING => 'Warning',
            E_DEPRECATED => 'PHP Deprecated',
            E_USER_DEPRECATED => 'Deprecated',
        );

        return isset($titles[$this->code]) ? $titles[$this->code] : 'PHP Error';
    }

    public function __toString()
    {
        return "{$this->getErrorTitle()}: $this->message in {$this->file} on line {$this->line}";
    }
}