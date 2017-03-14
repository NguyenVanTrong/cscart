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


use Tygh\Template\ITemplate;

/**
 * The entity class of an email template.
 *
 * @package Tygh\Template\Mail
 */
class Template implements ITemplate
{
    const STATUS_ACTIVE = 'A';

    const STATUS_DISABLE = 'D';

    const SNIPPET_TYPE = 'mail';

    /** @var int */
    protected $id;

    /** @var string */
    protected $code;

    /** @var string */
    protected $area;

    /** @var string */
    protected $status;

    /** @var string */
    protected $subject;

    /** @var string */
    protected $template;

    /** @var string|null */
    protected $default_subject;

    /** @var string|null */
    protected $default_template;

    /** @var array */
    protected $params_schema = array();

    /** @var array */
    protected $params = array();

    /** @var string */
    protected $addon = '';

    /** @var int */
    protected $created;

    /** @var int */
    protected $updated;

    /** @var string */
    protected $name;

    /**
     * Gets template name.
     *
     * @return string
     */
    public function getName()
    {
        if ($this->name === null) {
            $result = __($this->getNameLangKey());

            /**
             * Allows to change the name of the email template.
             *
             * @param Template  $this   Instance of email template.
             * @param string    $result Current template name.
             */
            fn_set_hook('template_email_get_name', $this, $result);
            $this->name = $result;
        }

        return $this->name;
    }

    /**
     * Gets language variable key for template name.
     *
     * @return string
     */
    public function getNameLangKey()
    {
        return 'email_template.' . $this->code;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int) $id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = (string) $code;
    }

    /**
     * @return string
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * @param string $area
     */
    public function setArea($area)
    {
        $this->area = (string) $area;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = (string) $status;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject ? $this->subject : $this->default_subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        if ($subject !== null) {
            $subject = (string) $subject;
        }

        $this->subject = $subject;

        if ($this->default_subject === null) {
            $this->default_subject = $subject;
        }
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template ? $this->template : $this->default_template;
    }

    /**
     * @param string $template
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
     * @return string|null
     */
    public function getDefaultSubject()
    {
        return $this->default_subject;
    }

    /**
     * @param string|null $default_subject
     */
    public function setDefaultSubject($default_subject)
    {
        $this->default_subject = $default_subject !== null ? (string) $default_subject : null;
    }

    /**
     * @return string|null
     */
    public function getDefaultTemplate()
    {
        return $this->default_template;
    }

    /**
     * @param string|null $default_template
     */
    public function setDefaultTemplate($default_template)
    {
        $this->default_template = $default_template !== null ? (string) $default_template : null;
    }

    /**
     * @return array
     */
    public function getParamsSchema()
    {
        return $this->params_schema;
    }

    /**
     * @param array $params_schema
     */
    public function setParamsSchema(array $params_schema)
    {
        $this->params_schema = $params_schema;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params)
    {
        $this->params = $params;
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
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param int $created
     */
    public function setCreated($created)
    {
        $this->created = (int) $created;
    }

    /**
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param int $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = (int) $updated;
    }

    /**
     * Checking if this template has been modified.
     */
    public function isModified()
    {
        return ($this->template !== null && $this->template !== $this->default_template)
                || ($this->subject !== null && $this->subject !== $this->default_subject);
    }

    /**
     * Instantiate email template from array.
     *
     * @param array $data
     *
     * @return Template
     */
    public static function fromArray(array $data)
    {
        $document = new self($data);
        $document->loadFromArray($data);

        return $document;
    }

    /**
     * Load email template from array.
     *
     * @param array $data
     */
    public function loadFromArray(array $data)
    {
        if (array_key_exists('template_id', $data)) {
            $this->setId($data['template_id']);
        }

        if (array_key_exists('code', $data)) {
            $this->setCode($data['code']);
        }

        if (array_key_exists('area', $data)) {
            $this->setArea($data['area']);
        }

        if (array_key_exists('status', $data)) {
            $this->setStatus($data['status']);
        }

        if (array_key_exists('template', $data)) {
            $this->setTemplate($data['template']);
        }

        if (array_key_exists('default_template', $data)) {
            $this->setDefaultTemplate($data['default_template']);
        }

        if (array_key_exists('subject', $data)) {
            $this->setSubject($data['subject']);
        }

        if (array_key_exists('default_subject', $data)) {
            $this->setDefaultSubject($data['default_subject']);
        }

        if (array_key_exists('params_schema', $data)) {
            $this->setParamsSchema($data['params_schema']);
        }

        if (array_key_exists('params', $data)) {
            $this->setParams($data['params']);
        }

        if (array_key_exists('addon', $data)) {
            $this->setAddon($data['addon']);
        }

        if (array_key_exists('created', $data)) {
            $this->setCreated($data['created']);
        }

        if (array_key_exists('updated', $data)) {
            $this->setUpdated($data['updated']);
        }
    }

    /**
     * Convert email template instance to array.
     *
     * @param array $exclude_fields List of excluded fields.
     *
     * @return array
     */
    public function toArray($exclude_fields = array())
    {
        $default_template = $this->default_template;
        $default_subject = $this->default_subject;

        if ($default_template === null) {
            $default_template = $this->template;
        }

        if ($default_subject === null) {
            $default_subject = $this->subject;
        }

        $result = array(
            'template_id' => $this->id,
            'code' => $this->code,
            'area' => $this->area,
            'status' => $this->status,
            'subject' => $this->subject == $default_subject ? null : $this->subject,
            'default_subject' => $default_subject,
            'template' => $this->template == $default_template ? null : $this->template,
            'default_template' => $default_template,
            'params_schema' => $this->params_schema,
            'params' => $this->params,
            'addon' => $this->addon,
            'created' => $this->created,
            'updated' => $this->updated,
        );

        foreach ($exclude_fields as $field) {
            unset($result[$field]);
        }

        return $result;
    }

    /**
     * Gets prepared schema of the params.
     * Check params schema, load variants.
     *
     * @return array
     */
    public function getPreparedParamsSchema()
    {
        $schema = array();

        if (!empty($this->params_schema)) {
            fn_get_schema('emails', 'variants.functions');

            $schema = $this->params_schema;

            foreach ($schema as &$item) {
                if (!empty($item['func']) && !isset($item['variants'])) {
                    $item['variants'] = call_user_func($item['func']);
                }
            }

            unset($item);
        }

        return $schema;
    }

    /**
     * @inheritDoc
     */
    public function getSnippetType()
    {
        return self::SNIPPET_TYPE;
    }
}