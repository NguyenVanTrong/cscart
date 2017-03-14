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


namespace Tygh\Addons\GiftCertificates\Documents\GiftCertificate;

use Tygh\Database\Connection;
use Tygh\Exceptions\InputException;
use Tygh\Template\Document\Document;
use Tygh\Template\Document\IIncludableType;
use Tygh\Template\Document\IPreviewableType;
use Tygh\Template\Document\IType;
use Tygh\Template\Document\Repository;
use Tygh\Template\ITemplate;
use Tygh\Template\Renderer;
use Tygh\Template\VariableCollectionFactory;

/**
 * Class Type
 * @package Tygh\Addons\GiftCertifications\Documents\GiftCertificate
 */
class Type implements IType, IPreviewableType, IIncludableType
{
    const DOCUMENT_TYPE = 'gift_certificate';

    /** @var Repository */
    protected $repository;

    /** @var VariableCollectionFactory */
    protected $collection_factory;

    /** @var Connection */
    protected $connection;

    /** @var Renderer */
    protected $renderer;

    /**
     * Gift certificate document type constructor.
     *
     * @param Repository                        $repository            Instance of document repository.
     * @param Connection                        $connection            Instance of database connection.
     * @param Renderer                          $renderer              Instance of template renderer.
     * @param VariableCollectionFactory         $collection_factory    Instance of variable collection factory.
     */
    public function __construct(
        Repository $repository,
        Connection $connection,
        Renderer $renderer,
        VariableCollectionFactory $collection_factory
    )
    {
        $this->connection = $connection;
        $this->renderer = $renderer;
        $this->repository = $repository;
        $this->collection_factory = $collection_factory;
    }

    /**
     * @inheritDoc
     */
    public function preview(Document $document)
    {
        $condition = fn_get_gift_certificate_company_condition('?:gift_certificates.company_id');
        $gift_cert_id = (int) $this->connection->getField('SELECT MIN(gift_cert_id) FROM ?:gift_certificates WHERE 1 ?p', $condition);

        if (empty($gift_cert_id)) {
            throw new InputException(__("document_preview_gift_certificate_not_found"));
        }

        $gift_cert_data = fn_get_gift_certificate_info($gift_cert_id);

        return $this->render($gift_cert_data, $document, DESCR_SL);
    }

    /**
     * @inheritDoc
     */
    public function includeDocument($code, $lang_code, $params)
    {
        $gift_cert_id = (int) array_shift($params);

        return $this->renderById($gift_cert_id, $code, $lang_code);
    }

    /**
     * @inheritDoc
     */
    public function getCode()
    {
        return self::DOCUMENT_TYPE;
    }

    /**
     * Render gift certificate.
     *
     * @param array     $gift_certificate   Gift certificate data.
     * @param string    $code               Document code.
     * @param string    $lang_code          Language code.
     *
     * @return string
     */
    public function renderByData($gift_certificate, $code = 'default', $lang_code)
    {
        $document = $this->repository->findByTypeAndCode($this->getCode(), $code);

        if (!$document && $code != 'default') {
            $document = $this->repository->findByTypeAndCode($this->getCode(), 'default');
        }

        if ($document) {
            return $this->render($gift_certificate, $document, $lang_code);
        }

        return '';
    }

    /**
     * Render gift certificate.
     *
     * @param int       $gift_certificate_id    Gift certificate identifier.
     * @param string    $code                   Document code.
     * @param string    $lang_code              Language code.
     *
     * @return string
     */
    public function renderById($gift_certificate_id, $code = 'default', $lang_code)
    {
        $gift_cert_data = fn_get_gift_certificate_info($gift_certificate_id);


        if ($gift_cert_data) {
            return $this->renderByData($gift_cert_data, $code, $lang_code);
        }

        return '';
    }

    /**
     * Render gift certificate document.
     *
     * @param array     $gift_certificate   Gift certificate data.
     * @param ITemplate $document           Instance of gift certificate document.
     * @param string    $lang_code          Language code.
     *
     * @return string
     */
    public function render($gift_certificate, ITemplate $document, $lang_code)
    {
        $context = new Context($gift_certificate, $lang_code);
        $variable_collection = $this->collection_factory->createCollection(self::SCHEMA_DIR, $this->getCode(), $context);

        return $this->renderer->renderTemplate($document, $context, $variable_collection);
    }
}