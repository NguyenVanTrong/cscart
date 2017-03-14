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

namespace Tygh\Template\Snippet\Table;


use Tygh\Database\Connection;

/**
 * The repository class that implements the logic of interaction with the storage for column templates.
 *
 * @package Tygh\Template\Snippet\Table
 */
class ColumnRepository
{
    /** @var Connection  */
    protected $connection;

    /** @var array  */
    protected $languages;

    /**
     * ColumnRepository constructor.
     *
     * @param Connection    $connection Instance of database connection.
     * @param array         $languages  List of available languages.
     */
    public function __construct(Connection $connection, array $languages)
    {
        $this->connection = $connection;
        $this->languages = $languages;
    }

    /**
     * Find column.
     *
     * @param array     $conditions List of query conditions.
     * @param array     $sort       List of sorted fields.
     * @param string    $lang_code  Language code.
     *
     * @return Column[]
     */
    public function find(array $conditions, array $sort, $lang_code = DESCR_SL)
    {
        $result = array();
        $sql = "SELECT ?:template_table_column_descriptions.*, ?:template_table_columns.* FROM ?:template_table_columns" .
            " LEFT JOIN ?:template_table_column_descriptions ON ?:template_table_columns.column_id = ?:template_table_column_descriptions.column_id AND ?:template_table_column_descriptions.lang_code = ?s" .
            " WHERE ?w";

        if (!empty($sort)) {
            $sql .= $this->getOrderBy($sort);
        }

        $rows = $this->connection->getArray($sql, $lang_code, $conditions);

        foreach ($rows as $row) {
            $result[] = $this->createColumn($row);
        }

        return $result;
    }

    /**
     * Find columns by snippet.
     *
     * @param string    $snippet_type   Snippet type.
     * @param string    $snippet_code   Snippet code
     * @param array     $sort           List of sorted fields.
     * @param string    $lang_code      Language code.
     *
     * @return Column[]
     */
    public function findBySnippet($snippet_type, $snippet_code, array $sort = array('position' => 'asc'), $lang_code = DESCR_SL)
    {
        return $this->find(array('snippet_type' => $snippet_type, 'snippet_code' => $snippet_code), $sort, $lang_code);
    }

    /**
     * Find active columns by snippet.
     *
     * @param string    $snippet_type   Snippet type.
     * @param string    $snippet_code   Snippet code.
     * @param array     $sort           List of sorted fields.
     * @param string    $lang_code      Language code.
     *
     * @return Column[]
     */
    public function findActiveBySnippet($snippet_type, $snippet_code, array $sort = array(), $lang_code = DESCR_SL)
    {
        return $this->find(
            array('snippet_type' => $snippet_type, 'snippet_code' => $snippet_code, 'status' => 'A'),
            $sort,
            $lang_code
        );
    }

    /**
     * Find columns by snippet and column code.
     *
     * @param string    $snippet_type   Snippet type.
     * @param string    $snippet_code   Snippet code
     * @param array     $code           Column code.
     * @param string    $lang_code      Language code.
     *
     * @return Column|false
     */
    public function findBySnippetAndCode($snippet_type, $snippet_code, $code, $lang_code = DESCR_SL)
    {
        $result = $this->find(array('snippet_type' => $snippet_type, 'snippet_code' => $snippet_code, 'code' => $code), array(), $lang_code);

        return reset($result);
    }

    /**
     * Find column by identifier.
     *
     * @param int       $id         Column identifier.
     * @param string    $lang_code  Language code.
     *
     * @return Column|false
     */
    public function findById($id, $lang_code = DESCR_SL)
    {
        $id = (int) $id;

        $result = $this->find(array('?:template_table_columns.column_id' => $id), array(), $lang_code);

        return reset($result);
    }

    /**
     * Find columns by identifiers.
     *
     * @param int[]     $ids        Column identifiers.
     * @param string    $lang_code  Language code.
     *
     * @return Column[]
     */
    public function findByIds($ids, $lang_code = DESCR_SL)
    {
        $ids = (array) $ids;

        return $this->find(array('?:template_table_columns.column_id' => $ids), array(), $lang_code);
    }

    /**
     * Gets column descriptions.
     *
     * @param int $column_id
     *
     * @return array
     */
    public function getDescriptions($column_id)
    {
        $result = $this->connection->getHash(
            "SELECT lang_code, name FROM ?:template_table_column_descriptions WHERE column_id = ?i",
            'lang_code', $column_id
        );

        return $result;
    }

    /**
     * Save column.
     *
     * @param Column    $column     Instance of column.
     * @param string    $lang_code  Language code.
     *
     * @return bool
     */
    public function save(Column $column, $lang_code = DESCR_SL)
    {
        $column_id = $column->getId();
        $base_data = $column->toArray(array('column_id', 'name'));

        if (empty($column_id)) {
            $column_id = $this->connection->query("INSERT INTO ?:template_table_columns ?e", $base_data);
            foreach ($this->languages as $lang_code => $item) {
                $this->connection->query("INSERT INTO ?:template_table_column_descriptions ?e", array(
                    'column_id' => $column_id,
                    'lang_code' => $lang_code,
                    'name' => $column->getName()
                ));
            }

            $column->setId($column_id);
        } else {
            $this->connection->query("UPDATE ?:template_table_columns SET ?u WHERE column_id = ?i", $base_data, $column_id);
            $this->updateDescription($column_id, array('name' => $column->getName()), $lang_code);
        }

        return true;
    }

    /**
     * Update descriptions.
     *
     * @param int       $column_id  Column identifier.
     * @param array     $items      List of descriptions.
     * @param string    $lang_code  Language code.
     *
     * @return bool
     */
    public function updateDescription($column_id, $items, $lang_code)
    {
        if (!isset($this->languages[$lang_code])) {
            return false;
        }

        return $this->connection->query(
            "UPDATE ?:template_table_column_descriptions SET ?u WHERE column_id = ?i AND lang_code = ?s",
            $items, $column_id, $lang_code
        );
    }

    /**
     * Remove column.
     *
     * @param Column $column Instance of column.
     *
     * @return bool
     */
    public function remove(Column $column)
    {
        $this->connection->query("DELETE FROM ?:template_table_columns WHERE column_id = ?i", $column->getId());
        $this->connection->query("DELETE FROM ?:template_table_column_descriptions WHERE column_id = ?i", $column->getId());

        return true;
    }

    /**
     * Remove columns by snippet identifier.
     *
     * @param string    $snippet_type   Snippet type.
     * @param string    $snippet_code   Snippet code.
     */
    public function removeBySnippet($snippet_type, $snippet_code)
    {
        $columns = $this->findBySnippet($snippet_type, $snippet_code);

        foreach ($columns as $column) {
            $this->remove($column);
        }
    }

    /**
     * Create column
     *
     * @param array $data
     *
     * @return Column
     */
    protected function createColumn(array $data)
    {
        return Column::fromArray($data);
    }

    /**
     * Gets order by
     *
     * @param array $sort List of sorted fields.
     *
     * @return string
     */
    protected function getOrderBy(array $sort = array())
    {
        $result = array();
        $map = array(
            'position' => '?:template_table_columns.position',
            'column_id' => '?:template_table_columns.column_id',
            'name' => '?:template_table_column_descriptions.name',
            'status' => '?:template_table_columns.status',
        );

        foreach ($sort as $sort_by => $sort_order) {
            $sort_order = strtoupper($sort_order);

            if (!in_array($sort_order, array('DESC', 'ASC'), true)) {
                $sort_order = 'ASC';
            }

            if (isset($map[$sort_by])) {
                $result[] = "{$map[$sort_by]} {$sort_order}";
            }
        }

        if (!empty($result)) {
            return ' ORDER BY '. implode(', ', $result);
        }

        return '';
    }
}