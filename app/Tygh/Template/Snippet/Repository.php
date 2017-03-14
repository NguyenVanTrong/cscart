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

namespace Tygh\Template\Snippet;


use Tygh\Database\Connection;

/**
 * The repository class that implements the logic of interaction with the storage for snippet templates.
 *
 * @package Tygh\Template\Snippet
 */
class Repository
{
    /** @var Connection  */
    protected $connection;

    /** @var array  */
    protected $languages;

    /**
     * Repository constructor.
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
     * Find snippets.
     *
     * @param array     $conditions List of query conditions.
     * @param string    $lang_code  Language code.
     *
     * @return Snippet[]
     */
    public function find(array $conditions, $lang_code = CART_LANGUAGE)
    {
        $result = array();
        $sql = "SELECT ?:template_snippet_descriptions.*, ?:template_snippets.* FROM ?:template_snippets" .
            " LEFT JOIN ?:template_snippet_descriptions ON ?:template_snippets.snippet_id = ?:template_snippet_descriptions.snippet_id AND ?:template_snippet_descriptions.lang_code = ?s" .
            " WHERE ?w ORDER BY ?:template_snippet_descriptions.name ASC";

        $rows = $this->connection->getArray($sql, $lang_code, $conditions);

        foreach ($rows as $row) {
            $result[] = $this->createSnippet($row);
        }

        return $result;
    }

    /**
     * Find snippet by identifier.
     *
     * @param int       $id         Snippet identifier.
     * @param string    $lang_code  Language code.
     *
     * @return Snippet|false
     */
    public function findById($id, $lang_code = CART_LANGUAGE)
    {
        $id = (int) $id;
        $result = $this->find(array('?:template_snippets.snippet_id' => $id), $lang_code);

        return reset($result);
    }

    /**
     * Find snippet by identifiers.
     *
     * @param int|int[] $ids         Snippet identifier.
     * @param string    $lang_code  Language code.
     *
     * @return Snippet[]
     */
    public function findByIds($ids, $lang_code = CART_LANGUAGE)
    {
        $ids = (array) $ids;
        return $this->find(array('?:template_snippets.snippet_id' => $ids), $lang_code);
    }

    /**
     * Find snippet by type and code.
     *
     * @param string $type      Snippet type (order, mail, etc).
     * @param string $code      Snippet code.
     * @param string $lang_code Language code.
     *
     * @return Snippet|false
     */
    public function findByTypeAndCode($type, $code, $lang_code = CART_LANGUAGE)
    {
        $result = $this->find(array('code' => $code, 'type' => $type), $lang_code);

        return reset($result);
    }

    /**
     * Find active snippet by type and code.
     *
     * @param string $type      Snippet type (order, mail, etc).
     * @param string $code      Snippet code.
     * @param string $lang_code Language code.
     *
     * @return Snippet|false
     */
    public function findActiveByTypeAndCode($type, $code, $lang_code = CART_LANGUAGE)
    {
        $result = $this->find(array('code' => $code, 'type' => $type, 'status' => 'A'), $lang_code);

        return reset($result);
    }

    /**
     * Find snippets by type.
     *
     * @param string|array  $type        Snippet type(s) (order, mail, etc).
     * @param string        $lang_code   Language code.
     *
     * @return Snippet[]
     */
    public function findByType($type, $lang_code = CART_LANGUAGE)
    {
        return $this->find(array('type' => $type), $lang_code);
    }

    /**
     * Find snippets by add-on.
     *
     * @param string $addon       Add-on code
     * @param string $lang_code   Language code.
     *
     * @return Snippet[]
     */
    public function findByAddon($addon, $lang_code = CART_LANGUAGE)
    {
        return $this->find(array('addon' => $addon), $lang_code);
    }

    /**
     * Check exists snippet.
     *
     * @param string    $type           Snippet type (mail, order).
     * @param string    $code           Snippet code (header, footer).
     * @param array     $exclude_ids    List of excluded snippet identifiers.
     *
     * @return bool|int Return snippet identifier if snippet exists
     */
    public function exists($type, $code, array $exclude_ids = array())
    {
        $conditions = array(
            'type' => $type,
            'code' => $code
        );

        if (!empty($exclude_ids)) {
            $conditions[] = array('snippet_id', 'NOT IN', $exclude_ids);
        }

        $snippet_id = $this->connection->getField("SELECT snippet_id FROM ?:template_snippets WHERE ?w LIMIT 1", $conditions);

        return !empty($snippet_id) ? $snippet_id : false;
    }

    /**
     * Gets snippet descriptions.
     *
     * @param int $snippet_id
     *
     * @return array
     */
    public function getDescriptions($snippet_id)
    {
        $result = $this->connection->getHash(
            "SELECT lang_code, name FROM ?:template_snippet_descriptions WHERE snippet_id = ?i",
            'lang_code', $snippet_id
        );

        return $result;
    }

    /**
     * Save snippet.
     *
     * @param Snippet   $snippet    Instance of snippet.
     * @param string    $lang_code  Language code.
     *
     * @return bool
     */
    public function save(Snippet $snippet, $lang_code = CART_LANGUAGE)
    {
        $snippet_id = $snippet->getId();
        $base_data = $snippet->toArray(array('snippet_id', 'name'));

        if (!empty($base_data['params'])) {
            $base_data['params'] = json_encode($base_data['params']);
        } else {
            $base_data['params'] = null;
        }

        if ($base_data['handler'] !== null) {
            $base_data['handler'] = json_encode($base_data['handler']);
        }

        if (empty($snippet_id)) {
            $snippet_id = $this->connection->query("INSERT INTO ?:template_snippets ?e", $base_data);
            foreach ($this->languages as $lang_code => $item) {
                $this->connection->query("INSERT INTO ?:template_snippet_descriptions ?e", array(
                    'snippet_id' => $snippet_id,
                    'lang_code' => $lang_code,
                    'name' => $snippet->getName()
                ));
            }

            $snippet->setId($snippet_id);
        } else {
            $this->connection->query("UPDATE ?:template_snippets SET ?u WHERE snippet_id = ?i", $base_data, $snippet_id);
            $this->updateDescription($snippet_id, array('name' => $snippet->getName()), $lang_code);
        }

        /**
         * Allows to perform additional actions after saving a snippet.
         *
         * @param self      $this       Instance of snippet repository.
         * @param Snippet   $snippet    Instance of snippet.
         * @param string    $lang_code  Language code.
         */
        fn_set_hook('template_snippet_save_post', $this, $snippet, $lang_code);

        return true;
    }

    /**
     * Update snippet status.
     *
     * @param Snippet   $snippet      Instance of snippet.
     * @param string    $new_status   New snippet status.
     *
     * @return bool
     */
    public function updateStatus(Snippet $snippet, $new_status)
    {
        $snippet_id = $snippet->getId();

        if (empty($snippet_id)) {
            return false;
        }

        $this->connection->query("UPDATE ?:template_snippets SET status = ?s WHERE snippet_id = ?i", $new_status, $snippet_id);

        /**
         * Allows to perform additional actions after changing the status of a snippet.
         *
         * @param self      $this       Instance of snippet repository.
         * @param Snippet   $snippet    Instance of snippet.
         * @param string    $new_status New status.
         */
        fn_set_hook('template_snippet_update_status_post', $this, $snippet, $new_status);

        return true;
    }

    /**
     * Update description.
     *
     * @param int       $snippet_id Snippet identifier.
     * @param array     $items      List of descriptions.
     * @param string    $lang_code  Language code.
     *
     * @return bool
     */
    public function updateDescription($snippet_id, $items, $lang_code)
    {
        if (!isset($this->languages[$lang_code])) {
            return false;
        }

        return $this->connection->query(
            "UPDATE ?:template_snippet_descriptions SET ?u WHERE snippet_id = ?i AND lang_code = ?s",
            $items, $snippet_id, $lang_code
        );
    }

    /**
     * Remove snippet.
     *
     * @param Snippet $snippet Instance of snippet.
     *
     * @return bool
     */
    public function remove(Snippet $snippet)
    {
        $this->removeById($snippet->getId());

        /**
         * Allows to perform additional actions after deleting a snippet template.
         *
         * @param self      $this       Instance of snippet repository.
         * @param Snippet   $snippet    Instance of snippet.
         */
        fn_set_hook('template_snippet_remove_post', $this, $snippet);

        return true;
    }

    /**
     * Remove snippet by identifier.
     *
     * @param int $snippet_id Snippet identifier.
     *
     * @return bool
     */
    protected function removeById($snippet_id)
    {
        $this->connection->query("DELETE FROM ?:template_snippets WHERE snippet_id = ?i", $snippet_id);
        $this->connection->query("DELETE FROM ?:template_snippet_descriptions WHERE snippet_id = ?i", $snippet_id);

        return true;
    }

    /**
     * @param array $data
     * @return Snippet
     */
    protected function createSnippet(array $data)
    {
        if (isset($data['params'])) {
            $data['params'] = (array) @json_decode($data['params'], true);
        } else {
            $data['params'] = array();
        }

        if (isset($data['handler'])) {
            $data['handler'] = @json_decode($data['handler'], true);
        }

        $snippet = Snippet::fromArray($data);
        return $snippet;
    }
}