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

namespace Tygh\Backend\Database;

class Pdo implements IBackend
{
    const PDO_MYSQL_ATTR_INIT_COMMAND = 1002;

    /**
     * @var \PDO
     */
    private $conn;
    private $last_result;

    /**
     * Connects to database server
     * @param  string  $user     user name
     * @param  string  $passwd   password
     * @param  string  $host     server host name
     * @param  string  $database database name
     * @return boolean true on success, false - otherwise
     */
    public function connect($user, $passwd, $host, $database)
    {
        if (!$host || !$user) {
            return false;
        }

        @list($host, $port) = explode(':', $host);

        try {
            $this->conn = new \PDO("mysql:host=$host;dbname=$database", $user, $passwd);
        } catch (\PDOException $e) {
            return false;
        }

        return !empty($this->conn);
    }

    /**
     * Disconnects from the database
     */
    public function disconnect()
    {
        return $this->conn = null;
    }

    /**
     * Changes current database
     * @param  string  $database database name
     * @return boolean true on success, false - otherwise
     */
    public function changeDb($database)
    {
        if ($this->conn->exec('USE ' . $database) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Queries database
     * @param  string $query SQL query
     * @return query  result
     */
    public function query($query)
    {
        $result = $this->conn->query($query);
        $this->last_result = $result;

        // need to return true for insert/replace/update/delete/alter query
        if (!empty($result) && preg_match("/^(INSERT|REPLACE|UPDATE|DELETE|ALTER)/", $result->queryString)) {
            return true;
        }

        return $result;
    }

    /**
     * Fetches row from query result set
     * @param  mixed  $result result set
     * @param  string $type   fetch type - 'assoc' or 'indexed'
     * @return array  fetched data
     */
    public function fetchRow($result, $type = 'assoc')
    {
        if ($type == 'assoc') {
            return $result->fetch(\PDO::FETCH_ASSOC);
        } else {
            return $result->fetch(\PDO::FETCH_NUM);
        }
    }

    /**
     * Frees result set
     * @param mixed $result result set
     */
    public function freeResult($result)
    {
        return $result->closeCursor();
    }

    /**
     * Return number of rows affected by query
     * @param  mixed $result result set
     * @return int   number of rows
     */
    public function affectedRows($result)
    {
        if (is_object($result)) {
            return $result->rowCount();
        } elseif (is_object($this->last_result)) {
            return $this->last_result->rowCount();
        }

        return 0;
    }

    /**
     * Returns last value of auto increment column
     * @return int value
     */
    public function insertId()
    {
        return $this->conn->lastInsertId();
    }

    /**
     * Gets last error code
     * @return int error code
     */
    public function errorCode()
    {
        $err = $this->conn->errorInfo();

        return $err[1];
    }

    /**
     * Gets last error description
     * @return string error description
     */
    public function error()
    {
        $err = $this->conn->errorInfo();

        return $err[2];
    }

    /**
     * Escapes value
     * @param  mixed  $value value to escape
     * @return string escaped value
     */
    public function escape($value)
    {
        return substr($this->conn->quote($value), 1, -1);
    }

    /**
     * Executes Command after when connecting to MySQL server
     * @param string $command Command to execute
     */
    public function initCommand($command)
    {
        if (!empty($command)) {
            $this->query($command);
            //$this->conn->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, $command);
            // FIXME: Workaround: Fatal error: Undefined class constant 'MYSQL_ATTR_INIT_COMMAND'
            // https://bugs.php.net/bug.php?id=47224
            // http://stackoverflow.com/questions/2424343/undefined-class-constant-mysql-attr-init-command-with-pdo
            // You should have extra extension to make it work or use 1002 instead

            $this->conn->setAttribute(self::PDO_MYSQL_ATTR_INIT_COMMAND, $command);
        }
    }

    /**
     * @inheritDoc
     */
    public function getVersion()
    {
        $version = 0;

        if (preg_match(
            '/^(?<major>\d+)\.(?<minor>\d+)\.(?<subver>\d+)/',
            $this->conn->getAttribute(\PDO::ATTR_SERVER_VERSION),
            $matches
        )) {
            $version = ($matches['major'] * 10000) + ($matches['minor'] * 100) + $matches['subver'];
        }

        return $version;
    }
}
