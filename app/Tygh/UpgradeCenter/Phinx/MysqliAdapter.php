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

namespace Tygh\UpgradeCenter\Phinx;

use InvalidArgumentException;
use Tygh\Exceptions\DatabaseException;

/**
 * Phinx MySQLi adapter.
 *
 * @since 4.3.3
 * @package Tygh\UpgradeCenter\Phinx
 */
class MysqliAdapter extends MysqlAdapter
{
    /**
     * @var Mysqli
     */
    protected $connection;

    public function connect()
    {
        if ($this->connection !== null) {
            return;
        }

        $options = $this->getOptions();

        // Fail-safe defaults
        array_key_exists('host', $options) || ($options['host'] = ini_get('mysqli.default_host'));
        array_key_exists('user', $options) || ($options['user'] = ini_get('mysqli.default_user'));
        array_key_exists('pass', $options) || ($options['pass'] = ini_get('mysqli.default_pw'));
        array_key_exists('port', $options) || ($options['port'] = ini_get('mysqli.default_port'));
        array_key_exists('unix_socket', $options) || ($options['unix_socket'] = ini_get('mysqli.default_socket'));
        array_key_exists('name', $options) || ($options['name'] = '');

        $connection = new Mysqli(
            $options['host'], $options['user'], $options['pass'], $options['name'], $options['port'],
            $options['unix_socket']
        );

        if ($connection->connect_error) {
            throw new InvalidArgumentException(sprintf(
                'There was a problem connecting to the database: (%s) %s',
                $connection->errno,
                $connection->connect_error
            ));
        }

        $this->connection = $connection;

        if (!$this->hasSchemaTable()) {
            $this->createSchemaTable();
        }
    }

    public function disconnect()
    {
        $this->connection->close();
        $this->connection = null;
    }


    /**
     * Executes a SQL statement and returns the number of affected rows.
     *
     * @param string $sql SQL
     *
     * @return int
     */
    public function execute($sql)
    {
        if (!$this->getConnection()->query($sql)) {
            $this->onQueryError($sql);
        }

        return $this->connection->affected_rows;
    }

    /**
     * Executes a SQL statement and returns the result as an array.
     *
     * @param string $sql SQL
     *
     * @return array
     */
    public function query($sql)
    {
        if ($result = $this->getConnection()->query($sql)) {
            return $result;
        } else {
            $this->onQueryError($sql);
        }
    }

    /**
     * Executes a query and returns an array of rows.
     *
     * @param string $sql SQL
     *
     * @return array
     */
    public function fetchAll($sql)
    {
        $rows = array();

        if ($result = $this->getConnection()->query($sql)) {
            while ($row = $result->fetch_array(MYSQLI_BOTH)) {
                $rows[] = $row;
            }
            $result->free();
        } else {
            $this->onQueryError($sql);
        }


        return $rows;
    }

    /**
     * Executes a query and returns only one row as an array.
     *
     * @param string $sql SQL
     *
     * @return array
     */
    public function fetchRow($sql)
    {
        $row = array();
        if ($result = $this->getConnection()->query($sql)) {
            $row = $result->fetch_array(MYSQLI_BOTH);
            $result->free();
        } else {
            $this->onQueryError($sql);
        }

        return $row;
    }

    /**
     * @return Mysqli
     */
    public function getConnection()
    {
        return parent::getConnection();
    }

    public function onQueryError($sql)
    {
        throw new DatabaseException($this->getConnection()->error, $this->getConnection()->errno);
    }
}