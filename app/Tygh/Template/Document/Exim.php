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
use Tygh\Exceptions\InputException;
use Tygh\Exceptions\PHPErrorException;
use Tygh\ExSimpleXmlElement;
use Tygh\Languages\Values;
use Tygh\Template\Snippet\Repository as SnippetRepository;
use Tygh\Template\Snippet\Exim as SnippetExim;

/**
 * The class that implements the logic of export and import of document templates.
 *
 * @package Tygh\Template\Document
 */
class Exim
{
    /** @var Service */
    protected $service;
    /** @var Repository */
    protected $repository;
    /** @var SnippetRepository */
    protected $snippet_repository;
    /** @var SnippetExim */
    protected $snippet_exim;
    /** @var array */
    protected $languages;
    /** @var Values */
    protected $translation;

    protected $default_document_data;

    /**
     * Documents export/import constructor.
     *
     * @param Service           $service            Instance of documents service.
     * @param SnippetRepository $snippet_repository Instance of snippet repository.
     * @param SnippetExim       $snippet_exim       Instance of snippet export/import.
     * @param array             $languages          List of available languages.
     * @param Values            $translation        Instance of translation manager.
     */
    public function __construct(
        Service $service,
        SnippetRepository $snippet_repository,
        SnippetExim $snippet_exim,
        array $languages,
        Values $translation
    )
    {
        $this->service = $service;
        $this->repository = $service->getRepository();
        $this->snippet_repository = $snippet_repository;
        $this->snippet_exim = $snippet_exim;
        $this->languages = $languages;
        $this->translation = $translation;
    }

    /**
     * Import documents from xml file.
     *
     * @param string $file      File path.
     * @param string $lang_code Language code.
     *
     * @throws InputException
     * @return OperationResult
     */
    public function importFromXmlFile($file, $lang_code = DESCR_SL)
    {
        if (!file_exists($file)) {
            throw new InputException("File not found");
        }

        $content = file_get_contents($file);

        return $this->importFromXml($content, $lang_code);
    }

    /**
     * Import documents from xml.
     *
     * @param string $xml       Xml document.
     * @param string $lang_code Language code.
     *
     * @throws InputException
     * @return OperationResult
     */
    public function importFromXml($xml, $lang_code = DESCR_SL)
    {
        if (!class_exists('\SimpleXMLElement')) {
            throw new InputException("Class 'SimpleXMLElement' not found ");
        }

        $xml = ExSimpleXmlElement::loadFromString($xml);
        $data = $xml->toArray();

        return $this->import($data, $lang_code);
    }

    /**
     * Import documents from data.
     *
     * @param array     $data       Data.
     * @param string    $lang_code  Language code.
     *
     * @return OperationResult
     */
    public function import(array $data, $lang_code = DESCR_SL)
    {
        $result = new OperationResult();
        $counter = array(
            'success_documents' => 0,
            'fail_documents' => 0
        );

        foreach ($data as $key => $item) {
            $import_result = $this->importDocument($item, $lang_code);

            foreach ($import_result->getErrors() as $code => $error) {
                $result->addError('document_' . $key . '_' . $code, "Document #{$key}: " . $error);
            }

            if ($import_result->isSuccess()) {
                $counter['success_documents']++;
            } else {
                $counter['fail_documents']++;
            }
        }

        $result->setData($counter);

        return $result;
    }

    /**
     * Import document from data.
     *
     * @param array     $data       Document data.
     * @param string    $lang_code  Language code.
     *
     * @return OperationResult
     */
    public function importDocument(array $data, $lang_code)
    {
        list($data, $descriptions) = $this->prepareData($data, $lang_code);

        $document = $this->repository->findByTypeAndCode($data['type'], $data['code']);

        if ($document) {
            $result = $this->service->updateDocument($document, $data);
        } else {
            $result = $this->service->createDocument($data);
        }

        if ($result->isSuccess()) {
            /** @var Document $document */
            $document = $result->getData();

            foreach ($descriptions as $descr_lang_code => $item) {
                $this->translation->updateLangVar(
                    array(array('name' => $document->getNameLangKey(), 'value' => $item['name'])),
                    $descr_lang_code
                );
            }

            if (!empty($data['snippets'])) {
                foreach ($data['snippets'] as $key => $item) {
                    $item['type'] = $document->getSnippetType();

                    $snippet_result = $this->snippet_exim->importSnippet($item, $lang_code);

                    foreach ($snippet_result->getErrors() as $code => $error) {
                        $result->addError('snippet_' . $key . '_' . $code, "Snippet #{$key}: " . $error);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Export selected documents to xml.
     *
     * @param array $document_ids List of document identifiers.
     *
     * @return string
     */
    public function exportToXmlByIds(array $document_ids)
    {
        $documents = $this->repository->findByIds($document_ids);
        return $this->exportToXml($documents);
    }

    /**
     * Export all documents to xml.
     *
     * @return string
     */
    public function exportAllToXml()
    {
        $documents = $this->repository->find();
        return $this->exportToXml($documents);
    }

    /**
     * Export documents to xml.
     *
     * @param Document[]    $documents  List of document instances.
     *
     * @throws InputException
     * @return string
     */
    public function exportToXml($documents)
    {
        if (!class_exists('\SimpleXMLElement')) {
            throw new InputException("Class 'SimpleXMLElement' not found ");
        }

        $xml_root = new ExSimpleXmlElement('<documents></documents>');
        $xml_root->addAttribute('scheme', '1.0');

        $result = $this->export($documents);

        foreach ($result as $item) {
            $xml_root->addChildFromArray(array('document' => $item));
        }

        return $xml_root->toString();
    }

    /**
     * Export documents to data.
     *
     * @param Document[]    $documents  List of document instances.
     *
     * @return string
     */
    public function export($documents)
    {
        $result = array();

        foreach ($documents as $document) {
            $result[] = $this->exportDocument($document);
        }

        return $result;
    }

    /**
     * Export document to data.
     *
     * @param Document $document Instance of document.
     *
     * @return array
     */
    public function exportDocument(Document $document)
    {
        $data = $document->toArray(array('document_id', 'created', 'updated'));
        $names = $this->translation->getByName($document->getNameLangKey(), false);

        $data['name'] = array();

        foreach ($names as $item) {
            $data['name'][$item['lang_code']] = $item['value'];
        }

        $snippets = $this->snippet_repository->findByType($document->getSnippetType());
        $data['snippets'] = $this->snippet_exim->export($snippets, array('type'));

        return $data;
    }

    /**
     * Prepare improted data.
     *
     * @param array     $data       Email template data.
     * @param string    $lang_code  Language code.
     * @return array
     */
    protected function prepareData(array $data, $lang_code)
    {
        if ($this->default_document_data === null) {
            $document = new Document();
            $this->default_document_data = $document->toArray();
        }

        $data = array_filter($data) + $this->default_document_data;
        $descriptions = array();

        if (isset($data['name']) && is_array($data['name'])) {
            $names = $data['name'];
            unset($data['name']);

            foreach ($names as $lang_code => $name) {
                if (in_array($lang_code, $this->languages, true)) {
                    $descriptions[$lang_code]['name'] = $name;
                }
            }
        }

        return array($data, $descriptions);
    }
}