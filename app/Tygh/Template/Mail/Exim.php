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

use Tygh\Common\OperationResult;
use Tygh\Exceptions\InputException;
use Tygh\ExSimpleXmlElement;
use Tygh\Languages\Values;
use Tygh\Template\Snippet\Repository as SnippetRepository;
use Tygh\Template\Snippet\Exim as SnippetExim;
use Tygh\Template\Snippet\Snippet;

/**
 * The class that implements the logic of import and export of email templates.
 *
 * @package Tygh\Template\Mail
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

    protected $default_template_data;

    /**
     * Email templates export/import constructor.
     *
     * @param Service           $service            Instance of email templates service.
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
     * Import email templates from xml file.
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
     * Import email templates from xml.
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
     * Import email templates from data.
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
            'success_templates' => 0,
            'success_snippets' => 0,
            'fail_templates' => 0,
            'fail_snippets' => 0,
        );

        if (!empty($data['templates'])) {
            foreach ($data['templates'] as $key => $item) {
                $item_result = $this->importTemplate($item, $lang_code);

                foreach ($item_result->getErrors() as $code => $error) {
                    $result->addError('template_' . $key . '_' . $code, "Template #{$key}: " . $error);
                }

                if ($item_result->isSuccess()) {
                    $counter['success_templates']++;
                } else {
                    $counter['fail_templates']++;
                }
            }
        }

        if (!empty($data['snippets'])) {
            foreach ($data['snippets'] as $key => $snippet) {
                $snippet['type'] = Template::SNIPPET_TYPE;
                $item_result = $this->snippet_exim->importSnippet($snippet, $lang_code);

                foreach ($item_result->getErrors() as $code => $error) {
                    $result->addError('snippet_' . $key . '_' . $code, "Snippet #{$key}: " . $error);
                }

                if ($item_result->isSuccess()) {
                    $counter['success_snippets']++;
                } else {
                    $counter['fail_snippets']++;
                }
            }
        }

        $result->setData($counter);

        return $result;
    }

    /**
     * Import email template from data.
     *
     * @param array     $data       Email template data.
     * @param string    $lang_code  Language code.
     *
     * @return OperationResult
     */
    public function importTemplate(array $data, $lang_code = DESCR_SL)
    {
        list($data, $descriptions) = $this->prepareData($data, $lang_code);

        $template = $this->repository->findByCodeAndArea($data['code'], $data['area']);

        if ($template) {
            $result = $this->service->updateTemplate($template, $data);
        } else {
            $result = $this->service->createTemplate($data);
        }

        if ($result->isSuccess()) {
            /** @var Template $template */
            $template = $result->getData();

            foreach ($descriptions as $lang_code => $item) {
                $this->translation->updateLangVar(
                    array(array('name' => $template->getNameLangKey(), 'value' => $item['name'])),
                    $lang_code
                );
            }
        }

        return $result;
    }

    /**
     * Export all email templates to xml.
     *
     * @return string
     */
    public function exportAllToXml()
    {
        $templates = $this->repository->find();
        $snippets = $this->snippet_repository->findByType(Template::SNIPPET_TYPE);

        return $this->exportToXml($templates, $snippets);
    }

    /**
     * Export email templates to xml.
     *
     * @param Template[]    $templates  List of email template instances.
     * @param Snippet[]     $snippets   List of snippet instances.
     *
     * @throws InputException
     * @return string
     */
    public function exportToXml($templates, $snippets)
    {
        if (!class_exists('\SimpleXMLElement')) {
            throw new InputException("Class 'SimpleXMLElement' not found ");
        }

        $xml_root = new ExSimpleXmlElement('<email_templates></email_templates>');
        $xml_root->addAttribute('scheme', '1.0');

        $result = $this->export($templates, $snippets);
        $xml_root->addChildFromArray($result);

        return $xml_root->toString();
    }

    /**
     * Export email templates to data.
     *
     * @param Template[]    $templates  List of email template instances.
     * @param Snippet[]     $snippets   List of snippet instances.
     *
     * @return array
     */
    public function export($templates, $snippets)
    {
        $result = array(
            'templates' => array()
        );

        foreach ($templates as $template) {
            $result['templates'][] = $this->exportTemplate($template);
        }

        $result['snippets'] = $this->snippet_exim->export($snippets, array('type'));

        return $result;
    }

    /**
     * Export email template to data.
     *
     * @param Template $template Instance of email template.
     *
     * @return array
     */
    public function exportTemplate(Template $template)
    {
        $data = $template->toArray(array('template_id', 'created', 'updated'));
        $names = $this->translation->getByName($template->getNameLangKey(), false);

        $data['name'] = array();

        foreach ($names as $item) {
            $data['name'][$item['lang_code']] = $item['value'];
        }

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
        if ($this->default_template_data === null) {
            $template = new Template();
            $this->default_template_data = $template->toArray();
        }

        $data = array_filter($data) + $this->default_template_data;
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