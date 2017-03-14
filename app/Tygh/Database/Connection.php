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

namespace Tygh\Database;

use Tygh\Database;
use Tygh\Debugger;
use Tygh\Exceptions\DatabaseException;
use Tygh\Registry;
use Tygh\Tygh;
use Tygh\Backend\Database\IBackend;

/**
 * Database connection class
 */
class Connection
{
    /**
     * if set to true, next query will be executed without additional processing by hooks
     *
     * @var boolean
     */
    public $raw = false;

    /**
     * Driver instance
     *
     * @var IBackend
     */
    protected $driver;

    /**
     * Max reconnects count
     *
     * @var integer
     */
    protected $max_reconnects = 3;

    /**
     * List connection codes
     *
     * @var array
     */
    protected $lost_connection_codes = array(
        2006,
        2013
    );

    /**
     * Skip error codes
     *
     * @var array
     */
    protected $skip_error_codes = array(
        1091, // column exists/does not exist during alter table
        1176, // key does not exist during alter table
        1050, // table already exist
        1060  // column exists
    );

    /**
     * Database connections list
     *
     * @var array
     * @deprecated since 4.3.6
     */
    protected $dbs = array();

    /**
     * Current database connection
     *
     * @var IBackend Active driver instance
     * @deprecated since 4.3.6. Use $this->driver instead
     */
    protected $db;

    /**
     * Current database connection name (main by default)
     *
     * @var string
     * @deprecated since 4.3.6
     */
    protected $dbc_name;

    /**
     * Table prefix for current connection
     *
     * @var string
     */
    protected $table_prefix;

    /**
     * Table fields cache
     *
     * @var array
     */
    protected $table_fields_cache = array();

    /**
     * Connection constructor
     *
     * @param IBackend $driver Driver instance
     */
    public function __construct(IBackend $driver = null)
    {
        if ($driver) {
            $this->driver = $driver;
        } else {
            $driver_class = Tygh::$app['db.driver.class'];
            $this->driver = new $driver_class();
        }
        $this->db = $this->driver; // FIXME
    }

    /**
     * Connects to the database server
     *
     * @param  string  $user     user name
     * @param  string  $passwd   password
     * @param  string  $host     host name
     * @param  string  $database database name
     * @param  array   $params   connection params
     * @return boolean true on success, false otherwise
     */
    public function connect($user, $passwd, $host, $database, $params = array())
    {
        // Default params
        $params = array_merge(array(
            'dbc_name'     => 'main', // @deprecated since 4.3.6
            'table_prefix' => '',
        ), $params);

        if (empty($this->dbs[$params['dbc_name']])) {
            if ($params['dbc_name'] != 'main') { // Backward compatibility.
                $this->driver = new Tygh::$app['db.driver.class'];
            }
            $this->dbs[$params['dbc_name']] = array(
                'db'       => $this->driver,
                'user'     => $user,
                'passwd'   => $passwd,
                'host'     => $host,
                'database' => $database,
                'params'   => $params,
            );

            Debugger::checkpoint('Before database connect');
            $result = $this->driver->connect($user, $passwd, $host, $database);
            Debugger::checkpoint('After database connect');

            if (!$result) {
                $this->dbs[$params['dbc_name']] = null;
            }
        } else {
            $result = true;
        }

        if ($result) {
            $this->dbc_name = $params['dbc_name'];
            $this->db = & $this->dbs[$params['dbc_name']]['db'];
            $this->table_prefix = $params['table_prefix'];

            if (empty($params['names'])) {
                $params['names'] = 'utf8';
            }
            if (empty($params['group_concat_max_len'])) {
                $params['group_concat_max_len'] = 3000; // 3Kb
            }

            $this->db->initCommand(
                $this->quote(
                    "SET NAMES ?s, sql_mode = ?s, SESSION group_concat_max_len = ?i",
                    $params['names'], '', $params['group_concat_max_len']
                )
            );
        }

        return $result;
    }

    /**
     * Changes database for current or passed connection
     * @param  string  $database database name
     * @param  string  $dbc_name database connection name
     * @return boolean true if database was changed, false - otherwise
     * @deprecated since 4.3.6
     */
    public function changeDb($database, $params = array())
    {
        if (empty($params['dbc_name'])) {
            $params['dbc_name'] = 'main';
        }

        if (!empty($this->dbs[$params['dbc_name']])) {
            if ($this->dbs[$params['dbc_name']]['db']->changeDb($database)) {

                $this->dbc_name = $params['dbc_name'];
                $this->db = & $this->dbs[$params['dbc_name']]['db'];
                $this->table_prefix = !empty($params['table_prefix']) ? $params['table_prefix'] : $this->dbs[$params['dbc_name']]['params']['table_prefix'];

                return true;
            } elseif ($this->hasLostConnectionError() && $this->tryReconnect()) {
                return $this->changeDb($database, $params);
            }
        }

        return false;
    }

    /**
     * Execute query and format result as associative array with column names as keys
     *
     * @param string $query unparsed query
     * @param mixed ... unlimited number of variables for placeholders
     * @return array structured data
     */
    public function getArray($query)
    {
        if ($_result = call_user_func_array(array($this, 'query'), func_get_args())) {

            while ($arr = $this->db->fetchRow($_result)) {
                $result[] = $arr;
            }

            $this->db->freeResult($_result);
        }

        return !empty($result) ? $result : array();
    }

    /**
     * Execute query and format result as associative array with column names as keys and index as defined field
     *
     * @param string $query unparsed query
     * @param string $field field for array index
     * @param mixed ... unlimited number of variables for placeholders
     * @return array structured data
     */
    public function getHash($query, $field)
    {
        $args = array_slice(func_get_args(), 2);
        array_unshift($args, $query);

        if ($_result = call_user_func_array(array($this, 'query'), $args)) {
            while ($arr = $this->db->fetchRow($_result)) {
                if (isset($arr[$field])) {
                    $result[$arr[$field]] = $arr;
                }
            }

            $this->db->freeResult($_result);
        }

        return !empty($result) ? $result : array();
    }

    /**
     * Execute query and format result as associative array with column names as keys and then return first element of this array
     *
     * @param string $query unparsed query
     * @param mixed ... unlimited number of variables for placeholders
     * @return array structured data
     */
    public function getRow($query)
    {
        if ($_result = call_user_func_array(array($this, 'query'), func_get_args())) {

            $result = $this->db->fetchRow($_result);

            $this->db->freeResult($_result);

            return is_array($result) ? $result : array();
        }

        return array();
    }

    /**
     * Execute query and returns first field from the result
     *
     * @param string $query unparsed query
     * @param mixed ... unlimited number of variables for placeholders
     * @return array structured data
     */
    public function getField($query)
    {
        if ($_result = call_user_func_array(array($this, 'query'), func_get_args())) {

            $result = $this->db->fetchRow($_result, 'indexed');

            $this->db->freeResult($_result);

        }

        return (isset($result) && is_array($result)) ? $result[0] : '';
    }

    /**
     * Execute query and format result as set of first column from all rows
     *
     * @param string $query unparsed query
     * @param mixed ... unlimited number of variables for placeholders
     * @return array structured data
     */
    public function getColumn($query)
    {
        $result = array();

        if ($_result = call_user_func_array(array($this, 'query'), func_get_args())) {
            while ($arr = $this->db->fetchRow($_result, 'indexed')) {
                $result[] = $arr[0];
            }

            $this->db->freeResult($_result);
        }

        return $result;
    }

    /**
     * Execute query and format result as one of: field => array(field_2 => value), field => array(field_2 => row_data), field => array([n] => row_data)
     *
     * @param string $query  unparsed query
     * @param array  $params array with 3 elements (field, field_2, value)
     * @param mixed ... unlimited number of variables for placeholders
     * @return array structured data
     */
    public function getMultiHash($query, $params)
    {
        @list($field, $field_2, $value) = $params;

        $args = array_slice(func_get_args(), 2);
        array_unshift($args, $query);

        if ($_result = call_user_func_array(array($this, 'query'), $args)) {
            while ($arr = $this->db->fetchRow($_result)) {
                if (!empty($field_2)) {
                    $result[$arr[$field]][$arr[$field_2]] = !empty($value) ? $arr[$value] : $arr;
                } else {
                    $result[$arr[$field]][] = $arr;
                }
            }

            $this->db->freeResult($_result);

        }

        return !empty($result) ? $result : array();
    }

    /**
     * Execute query and format result as key => value array
     *
     * @param string $query  unparsed query
     * @param array  $params array with 2 elements (key, value)
     * @param mixed ... unlimited number of variables for placeholders
     * @return array structured data
     */
    public function getSingleHash($query, $params)
    {
        @list($key, $value) = $params;

        $args = array_slice(func_get_args(), 2);
        array_unshift($args, $query);

        if ($_result = call_user_func_array(array($this, 'query'), $args)) {
            while ($arr = $this->db->fetchRow($_result)) {
                $result[$arr[$key]] = $arr[$value];
            }

            $this->db->freeResult($_result);
        }

        return !empty($result) ? $result : array();
    }

    /**
     *
     * Prepare data and execute REPLACE INTO query to DB
     * If one of $data element is null function unsets it before querry
     *
     * @param  string    $table Name of table that condition generated. Must be in SQL notation without placeholder.
     * @param  array     $data  Array of key=>value data of fields need to insert/update
     * @return db_result
     */
    public function replaceInto($table, $data)
    {
        if (!empty($data)) {
            return $this->query('INSERT INTO ?:' . $table . ' ?e ON DUPLICATE KEY UPDATE ?u', $data, $data);
        }

        return false;
    }

    /**
     * Creates new database
     * @param  string  $database database name
     * @return boolean true on success, false - otherwise
     */
    public function createDb($database)
    {
        if ($this->query("CREATE DATABASE IF NOT EXISTS `" . $this->db->escape($database) . "`")) {
            return true;
        }

        return false;
    }

    /**
     * Execute query
     *
     * @param string $query unparsed query
     * @param mixed ... unlimited number of variables for placeholders
     * @return mixed result set for "SELECT" statement / generated ID for an AUTO_INCREMENT field for insert statement / Affected rows count for DELETE/UPDATE statements
     */
    public function query($query)
    {
        $this->raw = $this->raw ?: Database::$raw; // Backward compatibility

        if (!$this->raw) {
            fn_set_hook('db_query', $query);
        }

        $args = func_get_args();
        $query = $this->process($query, array_slice($args, 1), true);
        $result = false;

        if (!empty($query)) {
            if (!$this->raw) {
                fn_set_hook('db_query_process', $query);
            }
            if (defined('DEBUG_QUERIES')) {
                fn_print_r($query);
            }

            $time_start = microtime(true);

            $result = $this->db->query($query);

            if ($result && !$this->hasError()) {
                $insert_id = $this->db->insertId();
                Debugger::set_query($query, microtime(true) - $time_start);

                if (!$this->raw) {
                    fn_set_hook('db_query_executed', $query, $result);
                }

                // "true" will be returned for Update/Delete/Insert/Replace statements. "SELECT" returns MySQLi/PDO object
                if ($result === true) {
                    $cmd = substr($query, 0, 6);

                    // Check if it was insert statement with auto_increment value and return it
                    if (!empty($insert_id)) {
                        $result = $insert_id;

                    } elseif ($cmd == 'UPDATE' || $cmd == 'DELETE' || $cmd == 'INSERT') {
                        $result = $this->db->affectedRows($result);
                    }

                    // Check if query updated data in the database and run cache handlers
                    if (!empty($result) && preg_match("/^(UPDATE|INSERT INTO|REPLACE INTO|DELETE FROM) " . $this->table_prefix . "(\w+) /", $query, $m)) {
                        Registry::setChangedTables($m[2]);
                    }
                    // Clear table fields cache if table structure was changed
                    if (!empty($result) && preg_match("/^(ALTER( IGNORE)? TABLE) " . $this->table_prefix . "(\w+) /", $query, $m)) {
                        $this->clearTableFieldsCache($m[3]);
                    }
                }
            } elseif ($this->hasError()) {
                $error_code = $this->driver->errorCode();
                $error_message = $this->driver->error();

                // Lost connection, try to reconnect
                if ($this->hasLostConnectionError() && $this->tryReconnect()) {
                    return $this->query($query);
                }

                $this->throwError($query, $error_code, $error_message);
            }
        }

        $this->raw = false;
        Database::$raw = false; // Backward compatibility

        return $result;
    }

    /**
     * Parse query and replace placeholders with data
     *
     * @param string $query unparsed query
     * @param mixed ... unlimited number of variables for placeholders
     * @return string parsed query
     */
    public function quote()
    {
        $args = func_get_args();
        $pattern = array_shift($args);

        return $this->process($pattern, $args, false);
    }

    /**
     * Parse query and replace placeholders with data
     *
     * @param  string $query unparsed query
     * @param  array  $data  data for placeholders
     * @return string parsed query
     */
    public function process($pattern, $data = array(), $replace = true)
    {
        // Replace table prefixes
        if ($replace) {
            $pattern = str_replace('?:', $this->table_prefix, $pattern);
        }

        if (!empty($data) && preg_match_all("/\?(i|s|l|d|a|n|u|e|m|p|w|f)+/", $pattern, $m)) {
            $offset = 0;
            foreach ($m[0] as $k => $ph) {
                if ($ph == '?u' || $ph == '?e') {

                    $table_pattern = '\?\:';
                    if ($replace) {
                        $table_pattern = $this->table_prefix;
                    }
                    if (preg_match("/^(UPDATE|INSERT INTO|REPLACE INTO|DELETE FROM) " . $table_pattern . "(\w+) /", $pattern, $m)) {
                        $data[$k] = $this->checkTableFields($data[$k], $m[2]);
                        if (empty($data[$k])) {
                            return false;
                        }
                    }
                }

                switch ($ph) {
                    // integer
                    case '?i':
                        $pattern = $this->strReplace($ph, $this->intVal($data[$k]), $pattern, $offset); // Trick to convert int's and longint's
                        break;

                    // string
                    case '?s':
                        $pattern = $this->strReplace($ph, "'" . $this->db->escape($data[$k]) . "'", $pattern, $offset);
                        break;

                    // string for LIKE operator
                    case '?l':
                        $pattern = $this->strReplace($ph, "'" . $this->db->escape(str_replace("\\", "\\\\", $data[$k])) . "'", $pattern, $offset);
                        break;

                    // float
                    case '?d':
                        if ($data[$k] == INF) {
                            $data[$k] = PHP_INT_MAX;
                        }
                        $pattern = $this->strReplace($ph, sprintf('%01.2f', $data[$k]), $pattern, $offset);
                        break;

                    // array of string
                    // @FIXME: add trim
                    case '?a':
                        $data[$k] = is_array($data[$k]) ? $data[$k] : array($data[$k]);
                        if (!empty($data[$k])) {
                            $pattern = $this->strReplace($ph, implode(', ', $this->filterData($data[$k], true, true)), $pattern, $offset);
                        } else {
                            if (Debugger::isActive() || fn_is_development()) {
                                trigger_error('Empty array was passed into SQL statement IN()', E_USER_DEPRECATED);
                            }
                            $pattern = $this->strReplace($ph, 'NULL', $pattern, $offset);
                        }
                        break;

                    // array of integer
                    // FIXME: add trim
                    case '?n':
                        $data[$k] = is_array($data[$k]) ? $data[$k] : array($data[$k]);
                        $pattern = $this->strReplace($ph, !empty($data[$k]) ? implode(', ', array_map(array('self', 'intVal'), $data[$k])) : "''", $pattern, $offset);
                        break;

                    // update
                    case '?u':
                        $clue = ($ph == '?u') ? ', ' : ' AND ';
                        $q = implode($clue, $this->filterData($data[$k], false));
                        $pattern = $this->strReplace($ph, $q, $pattern, $offset);

                        break;

                    //condition with and
                    case '?w':
                        $q = $this->buildConditions($data[$k]);
                        $pattern = $this->strReplace($ph, $q, $pattern, $offset);

                        break;

                    // insert
                    case '?e':
                        $filtered = $this->filterData($data[$k], true);
                        $pattern = $this->strReplace($ph,
                            "(" . implode(', ', array_keys($filtered)) . ") VALUES (" . implode(', ', array_values($filtered)) . ")", $pattern,
                            $offset);
                        break;

                    // insert multi
                    case '?m':
                        $values = array();
                        foreach ($data[$k] as $value) {
                            $filtered = $this->filterData($value, true);
                            $values[] = "(" . implode(', ', array_values($filtered)) . ")";
                        }
                        $pattern = $this->strReplace($ph, "(" . implode(', ', array_keys($filtered)) . ") VALUES " . implode(', ', $values), $pattern, $offset);
                        break;

                    // field/table/database name
                    case '?f':
                        $pattern = $this->strReplace($ph, $this->field($data[$k]), $pattern, $offset);
                        break;

                    // prepared statement
                    case '?p':
                        $pattern = $this->strReplace($ph, $this->tablePrefixReplace('?:', $this->table_prefix, $data[$k]), $pattern, $offset);
                        break;
                }
            }
        }

        return $pattern;
    }

    /**
     * Get column names from table
     *
     * @param  string $table_name table name
     * @param  array  $exclude    optional array with fields to exclude from result
     * @param  bool   $wrap_quote optional parameter, if true, the fields will be enclosed in quotation marks
     * @return array  columns array
     */
    public function getTableFields($table_name, $exclude = array(), $wrap = false)
    {
        if (!isset($this->table_fields_cache[$table_name])) {
            $this->table_fields_cache[$table_name] = $this->getColumn("SHOW COLUMNS FROM ?:$table_name");
        }

        $fields = $this->table_fields_cache[$table_name];
        if (!$fields) {
            return false;
        }

        if ($exclude) {
            $fields = array_diff($fields, $exclude);
        }

        if ($wrap) {
            foreach ($fields as &$v) {
                $v = "`$v`";
            }
        }

        return $fields;
    }

    /**
     * Check if passed data corresponds columns in table and remove unnecessary data
     *
     * @param  array $data       data for compare
     * @param  array $table_name table name
     * @return mixed array with filtered data or false if fails
     */
    public function checkTableFields($data, $table_name)
    {
        $fields = $this->getTableFields($table_name);
        if (is_array($fields)) {
            foreach ($data as $k => $v) {
                if (!in_array($k, $fields)) {
                    unset($data[$k]);
                }
            }
            if (func_num_args() > 3) {
                for ($i = 3; $i < func_num_args(); $i++) {
                    unset($data[func_get_arg($i)]);
                }
            }

            return $data;
        }

        return false;
    }

    /**
     * Get enum/set possible values in field of database
     *
     * @param  string $table_name Table name
     * @param  string $field_name Field name
     * @return array  List of elements
     */
    public function getListElements($table_name, $field_name)
    {
        $column_info = $this->getRow('SHOW COLUMNS FROM ?:?p WHERE Field = ?s', $table_name, $field_name);

        if (
            !empty($column_info)
            && preg_match('/^(\w{3,4})\((.*)\)$/', $column_info['Type'], $matches)
            && in_array($matches[1], array('set', 'enum'))
            && !empty($matches[2])
        ) {
            $elements = array();
            foreach (explode(',', $matches[2]) as $element) {
                $elements[] = trim($element, "'");
            }

            return $elements;
        }

        return false;

    }

    /**
     * Placeholder replace helper
     *
     * @param  string $needle      string to replace
     * @param  string $replacement replacement
     * @param  string $subject     string to search for replace
     * @param  int    $offset      offset to search from
     * @return string with replaced fragment
     */
    protected function strReplace($needle, $replacement, $subject, &$offset)
    {
        $pos = strpos($subject, $needle, $offset);
        $offset = $pos + strlen($replacement);

        // substr_replace does not work properly with mb_* and UTF8 encoded strings.
        //$return = substr_replace($subject, $replacement, $pos, 2);
        $return = substr($subject, 0, $pos) . $replacement . substr($subject, $pos + 2);

        return $return;
    }

    /**
     * Function finds $needle and replace it by $replacement only when $needle is not in quotes.
     * For example in sting "SELECT ?:products ..." ?: will be replaced,
     * but in "... WHERE name = '?:products'" ?: will not be replaced by table_prefix
     *
     * @param  string $needle      string to replace
     * @param  string $replacement replacement
     * @param  string $subject     string to search for replace
     * @return string
     */
    protected function tablePrefixReplace($needle, $replacement, $subject)
    {
        // check that needle exists
        if (($pos = strpos($subject, $needle)) === false) {
            return $subject;
        }

        // if there are no ', replace all occurrences
        if (strpos($subject, "'") === false) {
            return str_replace($needle, $replacement, $subject);
        }

        $needle_len = strlen($needle);
        // find needle
        while (($pos = strpos($subject, $needle, $pos)) !== false) {
            // get the first part of string
            $tmp = substr($subject, 0, $pos);
            // remove slashed single quotes
            $tmp = str_replace("\'", '', $tmp);
            // if we have even count of ', it means that we are not in the quotes
            if (substr_count($tmp, "'") % 2 == 0) {
                // so we should make a replacement
                $subject = substr_replace($subject, $replacement, $pos, $needle_len);
            } else {
                // we are in the quotes, skip replacement and move forward
                $pos += $needle_len;
            }
        }

        return $subject;
    }

    /**
     * Convert variable to int/longint type
     *
     * @param  mixed $int variable to convert
     * @return mixed int/intval variable
     */
    protected function intVal($int)
    {
        if ($int === true) {
            $int = 1;
        }

        if ($int == INF) {
            $int = PHP_INT_MAX;
        }

        return $int + 0;
    }

    /**
     * Check if variable is valid database table name, table field or database name
     *
     * @param  string $field field to check
     * @return mixed  passed variable if valid, empty string otherwise
     */
    protected function field($field)
    {
        if (preg_match("/([\w]+)/", $field, $m) && $m[0] == $field) {
            return $field;
        }

        return '';
    }

    /**
     * Display database error
     *
     * @param  resource $result result, returned by database server
     * @param  string   $query  SQL query, passed to server
     * @return mixed    false if no error, dies with error message otherwise
     */
    protected function error($result, $query)
    {
        if (empty($result) && $this->hasError()) {
            $this->throwError($query, $this->driver->error(), $this->driver->errorCode());
        }

        return false;
    }

    /**
     * Filters data to form correct SQL string
     *
     * @param array $data        Key-value array of fields and values to filter
     * @param bool  $key_value   Return result as key-value array if set true or as array of field-value pairs if set to false
     * @param bool  $force_quote If true, values will be wrapped with quotes regardles their type
     *
     * @return array filtered data
     */
    protected function filterData($data, $key_value, $force_quote = false)
    {
        $filtered = array();
        foreach ($data as $field => $value) {
            $value = $this->prepareValue($value, $force_quote);

            if ($key_value == true) {
                $filtered['`' . $this->field($field) . '`'] = $value;
            } else {
                $filtered[] = '`' . $this->field($field) . '` = ' . $value;
            }

        }

        return $filtered;
    }

    /**
     * Prepare value for use at query
     *
     * @param mixed $value       Value to prepare
     * @param bool  $force_quote If true, value will be wrapped with quotes regardles its type
     *
     * @return int|string
     */
    protected function prepareValue($value, $force_quote = false)
    {
        if ($force_quote) {
            $value = "'" . $this->driver->escape($value) . "'";
        } elseif (is_int($value) || is_float($value)) {
            //ok
        } elseif (is_numeric($value) && $value === strval($value + 0)) {
            $value += 0;
        } elseif (is_null($value)) {
            $value = 'NULL';
        } else {
            $value = "'" . $this->driver->escape($value) . "'";
        }

        return $value;
    }

    /**
     * Gets last error code
     * @return integer last error code
     */
    protected function errorCode()
    {
        $errno = $this->db->errorCode();

        return in_array($errno, $this->skip_error_codes) ? 0 : $errno;
    }

    /**
     * Tries to reconnect to current database.
     *
     * @return boolean true on reconnect try
     */
    protected function tryReconnect()
    {
        $reconnects = 0;
        $this->db->disconnect();
        $dbc_data = $this->dbs[$this->dbc_name];
        unset($this->dbs[$this->dbc_name]);

        while ($reconnects < $this->max_reconnects) {
            $reconnects++;

            if ($this->connect($dbc_data['user'], $dbc_data['passwd'], $dbc_data['host'], $dbc_data['database'], $dbc_data['params'])) {
                return true;
            }
        }

        return false;
    }

    public function getServerVersion()
    {
        return $this->db->getVersion();
    }

    /**
     * Build string conditions
     *
     * ```php
     * [
     *  'field' => 'value',
     *  ['field', 'operator', 'value']
     * ]
     * ```
     *
     * Available operators: '=', '<', '>', '<=', '>=', '!=', '<>', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'NULL'.
     * Example:
     *
     * ```php
     * [
     *  'status' => 'A',
     *  ['install_datetime', '>=', strtotime('-1 day')],
     *  ['name', 'IN', ['name1', 'name2']],
     *  ['title', 'LIKE', '%sub_title%],
     *  ['parent_id', 'NULL', true],
     *  ['has_child', 'NULL', false],
     * ]
     * ```
     *
     *
     * @param  array             $data
     * @return string
     * @throws DatabaseException
     */
    public function buildConditions(array $data)
    {
        $available_operators = array(
            '=', '<', '>', '<=', '>=', '!=', '<>', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'NULL'
        );

        $conditions = array();

        foreach ($data as $key => $item) {
            if (is_string($key)) {
                $field = $key;
                $operator = '=';
                $value = $item;

                if ($value === null) {
                    $operator = 'NULL';
                    $value = true;
                } elseif (is_array($value)) {
                    $operator = 'IN';
                }
            } else {
                if (!is_array($item) || count($item) < 3) {
                    throw new DatabaseException("Unsupported condition");
                }
                $item = array_values($item);

                $field = $item[0];
                $operator = strtoupper($item[1]);
                $value = $item[2];
            }

            if (!in_array($operator, $available_operators, true)) {
                throw new DatabaseException("Unsupported operator: {$operator}");
            }

            if (strpos($field, '.') !== false) {
                $field_parts = explode('.', $field, 2);

                $table = $this->process($field_parts[0]);
                $field = $field_parts[1];

                $field = '`' . $this->field($table) . '`.`' . $this->field($field) . '`';
            } else {
                $field = '`' . $this->field($field) . '`';
            }

            if ($operator === 'NULL') {
                $value = (bool) $value;

                if ($value) {
                    $conditions[] = "{$field} IS NULL";
                } else {
                    $conditions[] = "{$field} IS NOT NULL";
                }
            } elseif ($operator === 'IN' || $operator === 'NOT IN') {
                $value = (array) $value;
                $force_quote = false;
                foreach ($value as $datum) {
                    if (is_string($datum)) {
                        $force_quote = true;
                        break;
                    }
                }
                $value = implode(', ', $this->filterData($value, true, $force_quote));
                $conditions[] = "{$field} {$operator} ({$value})";
            } elseif ($operator === 'LIKE' || $operator === 'NOT LIKE') {
                $value = $this->driver->escape(str_replace("\\", "\\\\", $value));
                $conditions[] = "{$field} {$operator} '{$value}'";
            } else {
                $value = $this->prepareValue($value);
                $conditions[] = "{$field} {$operator} {$value}";
            }
        }

        return implode(' AND ', $conditions);
    }

    /**
     * Clear table fields cache
     *
     * @param string $table_name Table to clean fields cache for. Cache for all tables is cleaned if empty.
     */
    protected function clearTableFieldsCache($table_name = '')
    {
        if (empty($table_name)) {
            $this->table_fields_cache = array();
        } else {
            unset($this->table_fields_cache[$table_name]);
        }
    }

    /**
     * Check if the table exists in the database
     *
     * @param string $table_name Table name
     * @param bool $set_prefix Set prefix before check
     * @return bool
     */
    public function hasTable($table_name, $set_prefix = true)
    {
        if ($set_prefix) {
            $table_name = $this->table_prefix . $table_name;
        }

        if ($this->getRow("SHOW TABLES LIKE ?s", $table_name)) {
            return true;
        }

        return false;
    }

    /**
     * Checks last query has error.
     *
     * @return bool
     */
    protected function hasError()
    {
        return $this->errorCode() != 0;
    }

    /**
     * Checks last query has lost connection error.
     *
     * @return bool
     */
    protected function hasLostConnectionError()
    {
        return in_array($this->errorCode(), $this->lost_connection_codes);
    }

    /**
     * Throw database query error.
     *
     * @param string $query     SQL query.
     * @param string $code      Error code.
     * @param string $message   Error message.
     * @throws DatabaseException
     */
    protected function throwError($query, $code, $message)
    {
        $error = array (
            'message' => $message . ' <b>(' . $code . ')</b>',
            'query' => $query,
        );

        if (Registry::get('runtime.database.skip_errors') == true) {
            Registry::push('runtime.database.errors', $error);
        } else {
            Registry::set('runtime.database.skip_errors', true);

            // Log database errors
            fn_log_event('database', 'error', array(
                'error' => $error,
                'backtrace' => debug_backtrace()
            ));

            Registry::set('runtime.database.skip_errors', false);

            throw new DatabaseException($error['message'] . "<p>{$error['query']}</p>");
        }
    }
}
