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

use Tygh\Template\ITemplate;

/**
 * The entity class of a document template.
 *
 * @package Tygh\Template\Document
 */
class Document implements ITemplate
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $template;

    /** @var string */
    protected $default_template;

    /** @var string */
    protected $type;

    /** @var string */
    protected $code;

    /** @var int */
    protected $created;

    /** @var int */
    protected $updated;

    /** @var string */
    protected $addon = '';

    /**
     * Gets document name.
     *
     * @return string
     */
    public function getName()
    {
        $result = __($this->getNameLangKey());

        /**
         * Allows to change the name of the document template.
         *
         * @param Document  $this   Instance of document.
         * @param string    $result Current document name.
         */
        fn_set_hook('template_document_get_name', $this, $result);

        return $result;
    }

    /**
     * Gets language variable key for template name.
     *
     * @return string
     */
    public function getNameLangKey()
    {
        return 'template_document_' . $this->type . '_' . $this->code;
    }

    /**
     * Gets document template.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template ? $this->template : $this->default_template;
    }

    /**
     * Sets document template.
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
     * Sets document default template.
     *
     * @param string|null $template
     */
    public function setDefaultTemplate($template)
    {
        if ($template !== null) {
            $template = (string) $template;
        }

        $this->default_template = $template;
    }

    /**
     * Gets document type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets document type.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = (string) $type;
    }

    /**
     * Gets document code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Sets document code.
     *
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = (string) $code;
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
     * Gets document template identifier.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets document template identifier.
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int) $id;
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
     * Checking if this template has been modified.
     */
    public function isModified()
    {
        return $this->template !== null && $this->template !== $this->default_template;
    }

    /**
     * Instantiate document template from array.
     *
     * @param array $data
     *
     * @return Document
     */
    public static function fromArray(array $data)
    {
        $document = new self();
        $document->loadFromArray($data);

        return $document;
    }

    /**
     * Load document template from array.
     *
     * @param array $data
     */
    public function loadFromArray(array $data)
    {
        if (array_key_exists('document_id', $data)) {
            $this->setId($data['document_id']);
        }

        if (array_key_exists('type', $data)) {
            $this->setType($data['type']);
        }

        if (array_key_exists('code', $data)) {
            $this->setCode($data['code']);
        }

        if (array_key_exists('default_template', $data)) {
            $this->setDefaultTemplate($data['default_template']);
        }

        if (array_key_exists('template', $data)) {
            $this->setTemplate($data['template']);
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
     * Convert document template instance to array.
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
            'document_id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'template' => $this->template == $default_template ? null : $this->template,
            'default_template' => $default_template,
            'addon' => $this->addon,
            'created' => $this->created,
            'updated' => $this->updated
        );

        foreach ($exclude_fields as $field) {
            unset($result[$field]);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getSnippetType()
    {
        return $this->type . '_' . $this->code;
    }
}
