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

class Mysqli implements IBackend
{
    /**
     * @var \mysqli
     */
    private $conn;

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

        $this->conn = new \mysqli($host, $user, $passwd, $database, $port);

        if (!empty($this->conn) && empty($this->conn->connect_errno)) {
            return true;
        }

        return false;
    }

    /**
     * Disconnects from the database
     */
    public function disconnect()
    {
        $this->conn->close();
        $this->conn = null;
    }

    /**
     * Changes current database
     * @param  string  $database database name
     * @return boolean true on success, false - otherwise
     */
    public function changeDb($database)
    {
        if ($this->conn->select_db($database)) {
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
        return $this->conn->query($query);
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
            return $result->fetch_assoc();
        } else {
            return $result->fetch_row();
        }
    }

    /**
     * Frees result set
     * @param mixed $result result set
     */
    public function freeResult($result)
    {
        return $result->free();
    }

    /**
     * Return number of rows affected by query
     * @param  mixed $result result set
     * @return int   number of rows
     */
    public function affectedRows($result)
    {
        return $this->conn->affected_rows;
    }

    /**
     * Returns last value of auto increment column
     * @return int value
     */
    public function insertId()
    {
        return $this->conn->insert_id;
    }

    /**
     * Gets last error code
     * @return int error code
     */
    public function errorCode()
    {
        return $this->conn->errno;
    }

    /**
     * Gets last error description
     * @return string error description
     */
    public function error()
    {
        return $this->conn->error;
    }

    /**
     * Escapes value
     * @param  mixed  $value value to escape
     * @return string escaped value
     */
    public function escape($value)
    {
        return $this->conn->real_escape_string($value);
    }

    /**
     * Executes Command after when connecting to MySQL server
     * @param string $command Command to execute
     */
    public function initCommand($command)
    {
        if (!empty($command)) {
            $this->query($command);
            $this->conn->options(MYSQLI_INIT_COMMAND, $command);
        }
    }

    /**
     * @inheritDoc
     */
    public function getVersion()
    {
        return $this->conn->server_version;
    }
}
