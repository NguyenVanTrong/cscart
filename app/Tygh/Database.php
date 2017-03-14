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

/**
 * Database connection static deprecated class
 *
 * @deprecated since 4.3.6
 */
class Database
{
    /**
     * if set to true, next query will be executed without additional processing by hooks
     *
     * @var boolean
     */
    public static $raw = false;

    /**
     * Connects to the database server
     * @param  string  $user     user name
     * @param  string  $passwd   password
     * @param  string  $host     host name
     * @param  string  $database database name
     * @param  array   $params   connection params
     * @return boolean true on success, false otherwise
     */
    public static function connect($user, $passwd, $host, $database, $params = array())
    {
        return call_user_func_array(array(Tygh::$app['db'], 'connect'), func_get_args());
    }

    /**
     * Changes database for current or passed connection
     * @param  string  $database database name
     * @param  string  $dbc_name database connection name
     * @return boolean true if database was changed, false - otherwise
     */
    public static function changeDb($database, $params = array())
    {
        return call_user_func_array(array(Tygh::$app['db'], 'changeDb'), func_get_args());
    }

    /**
     * Execute query and format result as associative array with column names as keys
     *
     * @param string $query unparsed query
     * @param mixed ... unlimited number of variables for placeholders
     * @return array structured data
     */
    public static function getArray($query)
    {
        return call_user_func_array(array(Tygh::$app['db'], 'getArray'), func_get_args());
    }

    /**
     * Execute query and format result as associative array with column names as keys and index as defined field
     *
     * @param string $query unparsed query
     * @param string $field field for array index
     * @param mixed ... unlimited number of variables for placeholders
     * @return array structured data
     */
    public static function getHash($query, $field)
    {
        return call_user_func_array(array(Tygh::$app['db'], 'getHash'), func_get_args());
    }

    /**
     * Execute query and format result as associative array with column names as keys and then return first element of this array
     *
     * @param string $query unparsed query
     * @param mixed ... unlimited number of variables for placeholders
     * @return array structured data
     */
    public static function getRow($query)
    {
        return call_user_func_array(array(Tygh::$app['db'], 'getRow'), func_get_args());
    }

    /**
     * Execute query and returns first field from the result
     *
     * @param string $query unparsed query
     * @param mixed ... unlimited number of variables for placeholders
     * @return array structured data
     */
    public static function getField($query)
    {
        return call_user_func_array(array(Tygh::$app['db'], 'getField'), func_get_args());
    }

    /**
     * Execute query and format result as set of first column from all rows
     *
     * @param string $query unparsed query
     * @param mixed ... unlimited number of variables for placeholders
     * @return array structured data
     */
    public static function getColumn($query)
    {
        return call_user_func_array(array(Tygh::$app['db'], 'getColumn'), func_get_args());
    }

    /**
     * Execute query and format result as one of: field => array(field_2 => value), field => array(field_2 => row_data), field => array([n] => row_data)
     *
     * @param string $query  unparsed query
     * @param array  $params array with 3 elements (field, field_2, value)
     * @param mixed ... unlimited number of variables for placeholders
     * @return array structured data
     */
    public static function getMultiHash($query, $params)
    {
        return call_user_func_array(array(Tygh::$app['db'], 'getMultiHash'), func_get_args());
    }

    /**
     * Execute query and format result as key => value array
     *
     * @param string $query  unparsed query
     * @param array  $params array with 2 elements (key, value)
     * @param mixed ... unlimited number of variables for placeholders
     * @return array structured data
     */
    public static function getSingleHash($query, $params)
    {
        return call_user_func_array(array(Tygh::$app['db'], 'getSingleHash'), func_get_args());
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
    public static function replaceInto($table, $data)
    {
        return call_user_func_array(array(Tygh::$app['db'], 'replaceInto'), func_get_args());
    }

    /**
     * Creates new database
     * @param  string  $database database name
     * @return boolean true on success, false - otherwise
     */
    public static function createDb($database)
    {
        return call_user_func_array(array(Tygh::$app['db'], 'createDb'), func_get_args());
    }

    /**
     * Execute query
     *
     * @param string $query unparsed query
     * @param mixed ... unlimited number of variables for placeholders
     * @return mixed result set for "SELECT" statement / generated ID for an AUTO_INCREMENT field for insert statement / Affected rows count for DELETE/UPDATE statements
     */
    public static function query($query)
    {
        return call_user_func_array(array(Tygh::$app['db'], 'query'), func_get_args());
    }

    /**
     * Parse query and replace placeholders with data
     *
     * @param string $query unparsed query
     * @param mixed ... unlimited number of variables for placeholders
     * @return string parsed query
     */
    public static function quote()
    {
        return call_user_func_array(array(Tygh::$app['db'], 'quote'), func_get_args());
    }

    /**
     * Parse query and replace placeholders with data
     *
     * @param  string $query unparsed query
     * @param  array  $data  data for placeholders
     * @return string parsed query
     */
    public static function process($pattern, $data = array(), $replace = true)
    {
        return call_user_func_array(array(Tygh::$app['db'], 'process'), func_get_args());
    }

    /**
     * Get column names from table
     *
     * @param  string $table_name table name
     * @param  array  $exclude    optional array with fields to exclude from result
     * @param  bool   $wrap_quote optional parameter, if true, the fields will be enclosed in quotation marks
     * @return array  columns array
     */
    public static function getTableFields($table_name, $exclude = array(), $wrap = false)
    {
        return call_user_func_array(array(Tygh::$app['db'], 'getTableFields'), func_get_args());
    }

    /**
     * Check if passed data corresponds columns in table and remove unnecessary data
     *
     * @param  array $data       data for compare
     * @param  array $table_name table name
     * @return mixed array with filtered data or false if fails
     */
    public static function checkTableFields($data, $table_name)
    {
        return call_user_func_array(array(Tygh::$app['db'], 'checkTableFields'), func_get_args());
    }

    /**
     * Get enum/set possible values in field of database
     *
     * @param  string $table_name Table name
     * @param  string $field_name Field name
     * @return array  List of elements
     */
    public static function getListElements($table_name, $field_name)
    {
        return call_user_func_array(array(Tygh::$app['db'], 'getListElements'), func_get_args());
    }

    public static function getServerVersion()
    {
        return call_user_func_array(array(Tygh::$app['db'], 'getServerVersion'), func_get_args());
    }
}
