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

namespace Tygh;

class ServerEnvironment
{
    /**
     * @var string PHP version the software is runned with
     */
    protected $php_version;

    /**
     * @var string PHP SAPI the software is runned with
     */
    protected $php_sapi;

    /**
     * @var array Variables from php.ini
     */
    protected $ini_vars;

    /**
     * ServerEnvironment constructor.
     *
     * @param string $php_version PHP version the software is runned with
     * @param string $php_sapi    PHP SAPI the software is runned with
     * @param array  $ini_vars    Parameters from php.ini
     *
     */
    public function __construct($php_version = PHP_VERSION, $php_sapi = PHP_SAPI, $ini_vars = array())
    {
        $this->php_version = $php_version;

        $this->php_sapi = $php_sapi;

        $this->ini_vars = $ini_vars;
    }

    /**
     * Gets PHP version the software is runned with.
     *
     * @return string PHP version
     */
    public function getPhpVersion()
    {
        return $this->php_version;
    }

    /**
     * Gets PHP SAPI the software is runned with.
     *
     * @return string PHP SAPI
     */
    public function getSapi()
    {
        return $this->php_sapi;
    }

    /**
     * Gets specified php.ini variables.
     *
     * @return array Variables
     */
    public function getIniVars()
    {
        return $this->ini_vars;
    }

    /**
     * Returns parameter value from specified parameters.
     *
     * @param string $name Variable name
     *
     * @return mixed|null Parameter value
     */
    public function getIniVar($name)
    {
        return isset($this->ini_vars[$name]) ? $this->ini_vars[$name] : null;
    }
}
