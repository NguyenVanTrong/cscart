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


use Tygh\Template\ITemplate;

/**
 * The entity class of a table column.
 *
 * @package Tygh\Template\Snippet\Table
 */
class Column implements ITemplate
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $code;

    /** @var string */
    protected $snippet_code;

    /** @var string */
    protected $snippet_type;

    /** @var string */
    protected $status;

    /** @var int */
    protected $position = 0;

    /** @var string */
    protected $name;

    /** @var string */
    protected $template;

    /** @var string */
    protected $default_template;

    /** @var string */
    protected $addon = '';

    /**
     * Gets column identifier.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets column identifier.
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int) $id;
    }

    /**
     * Gets column status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets column status.
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Gets column position.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Sets column position.
     *
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = (int) $position;
    }

    /**
     * Gets column name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets column name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = (string) $name;
    }

    /**
     * Gets column template.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template ? $this->template : $this->default_template;
    }

    /**
     * Sets column template.
     *
     * @param string|null $template
     */
    public function setTemplate($template)
    {
        if ($template !== null) {
            $template = (string) $template;
        }

        $this->template = $template;

        if ($this->default_template === null) {
            $this->default_template = $template;
        }
    }

    /**
     * Gets default template.
     *
     * @return string
     */
    public function getDefaultTemplate()
    {
        return $this->default_template;
    }

    /**
     * Sets default template.
     *
     * @param string|null $default_template
     */
    public function setDefaultTemplate($default_template)
    {
        if ($default_template !== null) {
            $default_template = (string) $default_template;
        }

        $this->default_template = $default_template;
    }

    /**
     * Gets column code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Sets column code.
     *
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Gets document addon.
     *
     * @return string
     */
    public function getAddon()
    {
        return $this->addon;
    }

    /**
     * Sets addon.
     *
     * @param string $addon
     */
    public function setAddon($addon)
    {
        $this->addon = (string) $addon;
    }

    /**
     * Gets snippet type
     *
     * @return string
     */
    public function getSnippetType()
    {
        return $this->snippet_type;
    }

    /**
     * Sets snippet type
     *
     * @param string $snippet_type
     */
    public function setSnippetType($snippet_type)
    {
        $this->snippet_type = $snippet_type;
    }

    /**
     * Gets snippet code
     *
     * @return string
     */
    public function getSnippetCode()
    {
        return $this->snippet_code;
    }

    /**
     * Sets snippet code
     *
     * @param string $snippet_code
     */
    public function setSnippetCode($snippet_code)
    {
        $this->snippet_code = $snippet_code;
    }

    /**
     * Checking if this template has been modified.
     */
    public function isModified()
    {
        return $this->template !== null && $this->template !== $this->default_template;
    }

    /**
     * Create column instance from data.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $snippet = new self();
        $snippet->loadFromArray($data);

        return $snippet;
    }

    /**
     * Load column from data.
     *
     * @param array $data
     */
    public function loadFromArray(array $data)
    {
        if (isset($data['column_id'])) {
            $this->setId($data['column_id']);
        }

        if (isset($data['code'])) {
            $this->setCode($data['code']);
        }

        if (isset($data['snippet_type'])) {
            $this->setSnippetType($data['snippet_type']);
        }

        if (isset($data['snippet_code'])) {
            $this->setSnippetCode($data['snippet_code']);
        }

        if (isset($data['position'])) {
            $this->setPosition($data['position']);
        }

        if (isset($data['template'])) {
            $this->setTemplate($data['template']);
        }

        if (isset($data['default_template'])) {
            $this->setDefaultTemplate($data['default_template']);
        }

        if (isset($data['status'])) {
            $this->setStatus($data['status']);
        }

        if (isset($data['name'])) {
            $this->setName($data['name']);
        }

        if (isset($data['addon'])) {
            $this->setAddon($data['addon']);
        }
    }

    /**
     * Convert column to array.
     *
     * @param array $exclude_fields List of excluded fields.
     *
     * @return array
     */
    public function toArray($exclude_fields = array())
    {
        $result = array(
            'column_id' => $this->id,
            'code' => $this->code,
            'snippet_type' => $this->snippet_type,
            'snippet_code' => $this->snippet_code,
            'position' => $this->position,
            'template' => $this->template == $this->default_template ? null : $this->template,
            'addon' => $this->addon,
            'default_template' => $this->default_template,
            'status' => $this->status,
            'name' => $this->name,
        );

        foreach ($exclude_fields as $field) {
            unset($result[$field]);
        }

        return $result;
    }
}