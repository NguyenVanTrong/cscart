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

use Tygh\Common\OperationResult;
use Tygh\Exceptions\DatabaseException;
use Tygh\Exceptions\InputException;
use Tygh\Registry;
use Tygh\Template\Renderer;

/**
 * The service class that implements the logic of document template management.
 *
 * @package Tygh\Template\Document
 */
class Service
{
    /** @var Repository */
    protected $repository;

    /** @var Renderer */
    protected $renderer;

    /** @var array */
    protected $types = array();

    /** @var TypeFactory */
    protected $type_factory;

    /**
     * Document template service constructor.
     *
     * @param Repository        $repository     Instance of document template repository.
     * @param array             $types          Available document types.
     * @param Renderer          $renderer       Instance of template renderer.
     * @param TypeFactory       $type_factory   Instance of document type factory.
     */
    public function __construct(Repository $repository, array $types = array(), Renderer $renderer, TypeFactory $type_factory)
    {
        $this->repository = $repository;
        $this->types = $types;
        $this->type_factory = $type_factory;
        $this->renderer = $renderer;
    }

    /**
     * Create document template.
     *
     * @param array $data Document data.
     *
     * @return OperationResult
     */
    public function createDocument(array $data)
    {
        $result = new OperationResult();

        $data = $this->filterData($data);
        $errors = $this->validateData($data);
        $result->setErrors($errors);

        if (empty($errors)) {
            $document = new Document();
            $document->loadFromArray($data);
            $document->setCreated(time());
            $document->setUpdated(time());

            try {
                $this->repository->save($document);
                $result->setSuccess(true);
                $result->setData($document);
            } catch (DatabaseException $e) {
                $result->addError($e->getCode(), $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Update document.
     *
     * @param Document  $document   Instance of document.
     * @param array     $data       Document data.
     *
     * @return OperationResult
     */
    public function updateDocument(Document $document, array $data)
    {
        $result = new OperationResult();

        $data = $this->filterData($data);
        $errors = $this->validateData($data, $document);
        $result->setErrors($errors);

        if (empty($errors)) {
            $document->loadFromArray($data);
            $document->setUpdated(time());

            try {
                $this->repository->save($document);
                $result->setSuccess(true);
                $result->setData($document);
            } catch (DatabaseException $e) {
                $result->addError($e->getCode(), $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Restore document template to default.
     *
     * @param Document $document Instance of document template.
     *
     * @return bool
     */
    public function restoreTemplate(Document $document)
    {
        $document->setTemplate($document->getDefaultTemplate());
        return $this->repository->save($document);
    }

    /**
     * Remove document template.
     *
     * @param Document $document Instance of document template.
     */
    public function removeDocument(Document $document)
    {
        $this->repository->remove($document);
    }

    /**
     * Remove all documents by type.
     *
     * @param string $type Document type.
     */
    public function removeDocumentByType($type)
    {
        $documents = $this->repository->findByType($type);

        foreach ($documents as $document) {
            $this->removeDocument($document);
        }
    }

    /**
     * Remove all documents by add-on.
     *
     * @param string $addon Add-on code.
     */
    public function removeDocumentByAddon($addon)
    {
        $documents = $this->repository->findByAddon($addon);

        foreach ($documents as $document) {
            $this->removeDocument($document);
        }
    }

    /**
     * Filter data.
     *
     * @param array $data           Raw document data.
     * @param array $safe_fields    Safe document fields.
     * @param array $unsafe_fields  Unsafe document fields.
     *
     * @return array
     */
    public function filterData(array $data, array $safe_fields = array(), array $unsafe_fields = array())
    {
        if (empty($safe_fields)) {
            $safe_fields = array('addon', 'code', 'template', 'default_template', 'type');
        }

        $data = array_intersect_key($data, array_flip($safe_fields));

        foreach ($unsafe_fields as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * Validate document data.
     *
     * @param array         $data     Document data.
     * @param Document|null $document Current instance of document.
     *
     * @return array
     */
    public function validateData(array $data, Document $document = null)
    {
        $errors = array();

        if ($document == null) {
            $required_fields = array('code', 'template', 'type');
            $data += array_fill_keys($required_fields, '');
        }

        if (array_key_exists('code', $data)) {
            $errors['code'] = $this->validateCode($data['code'], $data['type'], $document ? $document->getId() : null);
        }

        if (array_key_exists('template', $data)) {
            $errors['template'] = $this->validateTemplate($data['template']);
        }

        if (array_key_exists('default_template', $data)) {
            $errors['default_template'] = $this->validateTemplate($data['template']);
        }

        return array_filter($errors, function ($val) {
            return $val !== true;
        });
    }

    /**
     * Validate document code.
     *
     * @param string    $code           Document code.
     * @param string    $type           Document type.
     * @param int|null  $document_id    Current document identifier.
     *
     * @return string|true
     */
    public function validateCode($code, $type, $document_id = null)
    {
        $error = true;

        if (empty($code)) {
            $error = __('error_validator_required', array('[field]' => __('code')));
        } elseif (preg_match('/[^-_a-z0-9]/', $code)) {
            $error = __('error_validator_message', array('[field]' => __('code')));
        } elseif ($this->repository->exists($type, $code, $document_id ? array($document_id) : array())) {
            $error = __('document_exists');
        }

        return $error;
    }

    /**
     * Validate document template.
     *
     * @param string $template Document template.
     *
     * @return string|true
     */
    public function validateTemplate($template)
    {
        $error = true;

        if (!empty($template)) {
            $result = $this->renderer->validate($template);

            if (!$result->isSuccess()) {
                $error = $result->getFirstError();
            }
        }

        return $error;
    }

    /**
     * Gets all documents.
     *
     * @return Document[]
     */
    public function getDocuments()
    {
        return $this->repository->find(array('type' => $this->types));
    }

    /**
     * Add document type on runtime.
     *
     * @param string $code
     */
    public function addType($code)
    {
        $this->types[] = $code;
    }

    /**
     * Render document for include to email.
     *
     * @param string    $type_code      Code identifier of document type.
     * @param string    $template_code  Code identifier of document template.
     * @param mixed     $params         Params for rendering.
     * @param string    $lang_code      Language code.
     * @throws InputException
     *
     * @return string
     */
    public function includeDocument($type_code, $template_code, $params, $lang_code)
    {
        $type = $this->type_factory->create($type_code);

        if (!$type instanceof IIncludableType) {
            throw new InputException("{$type_code} is not include able document type.");
        }

        return $type->includeDocument($template_code, $lang_code, $params);
    }

    /**
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }
}