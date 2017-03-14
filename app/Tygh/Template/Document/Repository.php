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


namespace Tygh\Template\Document;


use Tygh\Database\Connection;

/**
 * The repository class that implements the logic of interaction with the storage for document templates.
 *
 * @package Tygh\Template\Document
 */
class Repository
{
    /** @var Connection  */
    protected $connection;

    /**
     * Repository constructor.
     *
     * @param Connection $connection Instance of database connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Find documents.
     *
     * @param array $conditions List of conditions.
     *
     * @return Document[]
     */
    public function find(array $conditions = array())
    {
        $result = array();

        if (empty($conditions)) {
            $conditions[] = array('document_id', '>', 0);
        }

        $rows = $this->connection->getArray(
            "SELECT * FROM ?:template_documents WHERE ?w ORDER BY type, code",
            $conditions
        );

        foreach ($rows as $row) {
            $document = $this->createDocument($row);
            $result[$document->getId()] = $document;
        }

        return $result;
    }

    /**
     * Find document by type and code.
     *
     * @param string $type Document type.
     * @param string $code Document code.
     *
     * @return Document|false
     */
    public function findByTypeAndCode($type, $code)
    {
        $code = trim($code);
        $results = $this->find(array('type' => $type, 'code' => $code));

        return reset($results);
    }

    /**
     * Find documents by type.
     *
     * @param string $type Document type.
     *
     * @return Document[]
     */
    public function findByType($type)
    {
        return $this->find(array('type' => $type));
    }

    /**
     * Find documents by add-on.
     *
     * @param string $addon Add-on code.
     *
     * @return Document[]
     */
    public function findByAddon($addon)
    {
        return $this->find(array('addon' => $addon));
    }

    /**
     * Find document by identifier.
     *
     * @param int $id Document identifier.
     *
     * @return Document|false
     */
    public function findById($id)
    {
        $id = (int) $id;
        $results = $this->find(array('document_id' => $id));

        return reset($results);
    }

    /**
     * Find documents by identifiers.
     *
     * @param int[] $ids List of Document identifier.
     *
     * @return Document[]
     */
    public function findByIds($ids)
    {
        return $this->find(array('document_id' => $ids));
    }

    /**
     * Check exists document.
     *
     * @param string    $type           Document type (order, order_supplier).
     * @param string    $code           Document code.
     * @param array     $exclude_ids    List of excluded document identifiers.
     *
     * @return bool
     */
    public function exists($type, $code, array $exclude_ids = array())
    {
        $conditions = array(
            'type' => $type,
            'code' => $code
        );

        if (!empty($exclude_ids)) {
            $conditions[] = array('document_id', 'NOT IN', $exclude_ids);
        }

        $document_id = $this->connection->getColumn("SELECT document_id FROM ?:template_documents WHERE ?w LIMIT 1", $conditions);

        return !empty($document_id);
    }

    /**
     * Save document.
     *
     * @param Document $document Instance of document template.
     *
     * @return bool
     */
    public function save(Document $document)
    {
        $data = $document->toArray(array('document_id'));

        if (!$document->getId()) {
            $id = $this->connection->query("INSERT INTO ?:template_documents ?e", $data);
            $document->setId($id);
        } else {
            $this->connection->query("UPDATE ?:template_documents SET ?u WHERE document_id = ?i", $data, $document->getId());
        }

        return true;
    }

    /**
     * Remove document.
     *
     * @param Document $document Instance of document template.
     *
     * @return bool
     */
    public function remove(Document $document)
    {
        $this->connection->query("DELETE FROM ?:template_documents WHERE document_id = ?i", $document->getId());

        /**
         * Allows to perform additional actions after deleting a document template.
         *
         * @param self      $this       Instance of document template repository.
         * @param Document  $document   Instance of document template.
         */
        fn_set_hook('template_document_remove_post', $this, $document);

        return true;
    }

    /**
     * @param array $row
     * @return Document
     */
    protected function createDocument(array $row)
    {
        return Document::fromArray($row);
    }
}