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

namespace Tygh\Template\Mail;


use Tygh\Database\Connection;

/**
 * The repository class that implements the logic of interaction with the storage for email templates.
 *
 * @package Tygh\Template\Mail
 */
class Repository
{
    /** @var Connection */
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
     * Find email templates.
     *
     * @param array $conditions List of conditions.
     *
     * @return Template[]
     */
    public function find(array $conditions = array())
    {
        $result = array();

        if (empty($conditions)) {
            $conditions[] = array('template_id', '>', 0);
        }

        $rows = $this->connection->getArray(
            "SELECT * FROM ?:template_emails WHERE ?w ORDER BY code",
            $conditions
        );

        foreach ($rows as $row) {
            $document = $this->createTemplate($row);
            $result[$document->getId()] = $document;
        }

        return $result;
    }

    /**
     * Find email template by code and area.
     *
     * @param string $code Email template code.
     * @param string $area Email template area.
     *
     * @return Template|false
     */
    public function findByCodeAndArea($code, $area)
    {
        $results = $this->find(array('code' => $code, 'area' => $area));

        return reset($results);
    }

    /**
     * Find email templates by code.
     *
     * @param string $code Email template code.
     *
     * @return Template[]
     */
    public function findByCode($code)
    {
        return $this->find(array('code' => $code));
    }

    /**
     * Find email templates by add-on.
     *
     * @param string $addon Add-on code.
     *
     * @return Template[]
     */
    public function findByAddon($addon)
    {
        return $this->find(array('addon' => $addon));
    }

    /**
     * Find active email template by code and area.
     *
     * @param string $code Email template code.
     * @param string $area Email template area.
     *
     * @return Template|false
     */
    public function findActiveByCodeAndArea($code, $area)
    {
        $results = $this->find(array('code' => $code, 'area' => $area, 'status' => Template::STATUS_ACTIVE));

        return reset($results);
    }

    /**
     * Find email template by identifier.
     *
     * @param int $id Email template identifier.
     *
     * @return Template|false
     */
    public function findById($id)
    {
        $id = (int) $id;
        $results = $this->find(array('template_id' => $id));

        return reset($results);
    }

    /**
     * Check exists email template.
     *
     * @param string    $area           Area.
     * @param string    $code           Template code identifier.
     * @param array     $exclude_ids    List of excluded template identifiers.
     *
     * @return bool
     */
    public function exists($area, $code, array $exclude_ids = array())
    {
        $conditions = array(
            'area' => $area,
            'code' => $code
        );

        if (!empty($exclude_ids)) {
            $conditions[] = array('template_id', 'NOT IN', $exclude_ids);
        }

        $template_id = $this->connection->getColumn("SELECT template_id FROM ?:template_emails WHERE ?w LIMIT 1", $conditions);

        return !empty($template_id);
    }

    /**
     * Save email template.
     *
     * @param Template $template Instance of email template.
     *
     * @return bool
     */
    public function save(Template $template)
    {
        $data = $template->toArray(array('template_id'));

        if (!empty($data['params_schema'])) {
            $data['params_schema'] = json_encode($data['params_schema']);
        } else {
            $data['params_schema'] = null;
        }

        if (!empty($data['params'])) {
            $data['params'] = json_encode($data['params']);
        } else {
            $data['params'] = null;
        }

        if (!$template->getId()) {
            $id = $this->connection->query("INSERT INTO ?:template_emails ?e", $data);
            $template->setId($id);
        } else {
            $this->connection->query("UPDATE ?:template_emails SET ?u WHERE template_id = ?i", $data, $template->getId());
        }

        return true;
    }

    /**
     * Remove email template.
     *
     * @param Template $template Instance of email template.
     *
     * @return bool
     */
    public function remove(Template $template)
    {
        $this->connection->query("DELETE FROM ?:template_emails WHERE template_id = ?i", $template->getId());

        /**
         * Allows to perform additional actions after deleting an email template.
         *
         * @param self      $this       Instance of email template repository.
         * @param Template  $template   Instance of email template.
         */
        fn_set_hook('template_email_remove_post', $this, $template);

        return true;
    }

    /**
     * @param array $row
     * @return Template
     */
    protected function createTemplate(array $row)
    {
        if (isset($row['params_schema'])) {
            $row['params_schema'] = (array) @json_decode($row['params_schema'], true);
        } else {
            $row['params_schema'] = array();
        }

        if (isset($row['params'])) {
            $row['params'] = (array) @json_decode($row['params'], true);
        } else {
            $row['params'] = array();
        }


        return Template::fromArray($row);
    }
}