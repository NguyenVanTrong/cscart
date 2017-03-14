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


use Tygh\Template\ITemplate;

/**
 * The entity class of the snippet template.
 *
 * @package Tygh\Template\Snippet
 */
class Snippet implements ITemplate
{
    const STATUS_ACTIVE = 'A';

    const STATUS_DISABLE = 'D';

    /** @var int */
    protected $id;

    /** @var string */
    protected $code;

    /** @var string */
    protected $type;

    /** @var string */
    protected $name;

    /** @var string */
    protected $template;

    /** @var string|null */
    protected $default_template;

    /** @var string */
    protected $status;

    /** @var int */
    protected $created;

    /** @var int */
    protected $updated;

    /** @var mixed */
    protected $handler;

    /** @var string */
    protected $addon = '';

    /** @var array */
    protected $params = array();

    /**
     * Gets snippet identifier.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets snippet identifier.
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int) $id;
    }

    /**
     * Gets snippet type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets snippet type.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = (string) $type;
    }

    /**
     * Gets snippet code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Sets snippet code.
     *
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = (string) $code;
    }

    /**
     * Gets snippet name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets snippet name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = (string) $name;
    }

    /**
     * Gets snippet template.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template ? $this->template : $this->default_template;
    }

    /**
     * Sets snippet template.
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
     * Gets default snippet template.
     *
     * @return string|null
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
     * Gets snippet status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets snippet status.
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = (string) $status;
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
     * @return bool
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Gets create timestamp.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Sets create timestamp.
     *
     * @param int $created
     */
    public function setCreated($created)
    {
        $this->created = (int) $created;
    }

    /**
     * Gets update timestamp.
     *
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Sets update timestamp.
     *
     * @param int $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = (int) $updated;
    }

    /**
     * Gets snippet params.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Gets snippet param by key.
     *
     * @param string        $key        Param key.
     * @param mixed|null    $default    Default value.
     *
     * @return mixed|null
     */
    public function getParam($key, $default = null)
    {
        return array_key_exists($key, $this->params) ? $this->params[$key] : $default;
    }

    /**
     * Sets snippet params.
     *
     * @param array $params
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * Gets handler.
     * 
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Sets handler.
     *
     * @param mixed $handler
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
    }

    /**
     * Checking if this template has been modified.
     */
    public function isModified()
    {
        return $this->template !== null && $this->template !== $this->default_template;
    }

    /**
     * Create snippet instance from data.
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
     * Load snippet from data.
     *
     * @param array $data
     */
    public function loadFromArray(array $data)
    {
        if (array_key_exists('snippet_id', $data)) {
            $this->setId($data['snippet_id']);
        }

        if (array_key_exists('code', $data)) {
            $this->setCode($data['code']);
        }

        if (array_key_exists('type', $data)) {
            $this->setType($data['type']);
        }

        if (array_key_exists('template', $data)) {
            $this->setTemplate($data['template']);
        }

        if (array_key_exists('default_template', $data)) {
            $this->setDefaultTemplate($data['default_template']);
        }

        if (array_key_exists('status', $data)) {
            $this->setStatus($data['status']);
        }

        if (array_key_exists('name', $data)) {
            $this->setName($data['name']);
        }

        if (array_key_exists('params', $data)) {
            $this->setParams($data['params']);
        }

        if (array_key_exists('handler', $data)) {
            $this->setHandler($data['handler']);
        }

        if (array_key_exists('addon', $data)) {
            $this->setAddon($data['addon']);
        }

        if (array_key_exists('updated', $data)) {
            $this->setUpdated($data['updated']);
        }

        if (array_key_exists('created', $data)) {
            $this->setCreated($data['created']);
        }
    }

    /**
     * Convert snippet to array.
     *
     * @param array $exclude_fields List of excluded fields.
     *
     * @return array
     */
    public function toArray($exclude_fields = array())
    {
        $default_template = $this->default_template;

        if ($default_template === null) {
            $default_template = $this->template;
        }

        $result = array(
            'snippet_id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'template' => $this->template == $default_template ? null : $this->template,
            'default_template' => $default_template,
            'status' => $this->status,
            'name' => $this->name,
            'params' => $this->params,
            'addon' => $this->addon,
            'handler' => $this->handler,
            'updated' => $this->updated,
            'created' => $this->created
        );

        foreach ($exclude_fields as $field) {
            unset($result[$field]);
        }

        return $result;
    }

    /**
     * Gets snippet call tag.
     *
     * @return string
     */
    public function getCallTag()
    {
        return 'snippet("' . $this->code. '")';
    }

    /**
     * @inheritDoc
     */
    public function getSnippetType()
    {
        return $this->type;
    }
}