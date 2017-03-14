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

namespace Tygh\Addons\Rma\Documents\PackingSlip;


use Tygh\Database\Connection;
use Tygh\Exceptions\InputException;
use Tygh\Template\Document\Document;
use Tygh\Template\Document\IIncludableType;
use Tygh\Template\Document\IPreviewableType;
use Tygh\Template\Document\IType;
use Tygh\Template\Document\Order\Order;
use Tygh\Template\Document\Repository;
use Tygh\Template\Renderer;
use Tygh\Template\VariableCollectionFactory;

/**
 * Class Type
 * @package Tygh\Addons\Rma\Documents\PackingSlip
 */
class Type implements IType, IPreviewableType, IIncludableType
{
    const DOCUMENT_TYPE = 'rma_packing_slip';

    /** @var Repository */
    protected $repository;

    /** @var VariableCollectionFactory */
    protected $collection_factory;

    /** @var Connection */
    protected $connection;

    /** @var Renderer */
    protected $renderer;

    /**
     * Rma packing slip document type constructor.
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
    public function includeDocument($code, $lang_code, $params)
    {
        $return_id = (int) array_shift($params);

        return $this->renderByReturnId($return_id, $code, $lang_code);
    }

    /**
     * @inheritDoc
     */
    public function getCode()
    {
        return self::DOCUMENT_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function preview(Document $document)
    {
        $condition = fn_get_company_condition('?:orders.company_id');

        $return_id = (int) $this->connection->getField(
            'SELECT MIN(return_id) FROM ?:rma_returns'
            . ' INNER JOIN ?:orders ON ?:orders.order_id = ?:rma_returns.order_id'
            . ' WHERE 1 ?p LIMIT 1',
            $condition
        );

        if (empty($return_id)) {
            throw new InputException(__("document_preview_return_request_not_found"));
        }

        $return_info = fn_get_return_info($return_id);
        $order = new Order($return_info['order_id']);

        return $this->render($order, $return_info, $document);
    }

    /**
     * Render packing slip.
     *
     * @param Order      $order         Instance of order.
     * @param array      $return_info   Return request data.
     * @param Document   $document      Instance of document template.
     *
     * @return string
     */
    public function render(Order $order, array $return_info = array(), Document $document)
    {
        $context = new Context($order, $return_info);
        $variable_collection = $this->collection_factory->createCollection(self::SCHEMA_DIR, $this->getCode(), $context);

        return $this->renderer->renderTemplate($document, $context, $variable_collection);
    }

    /**
     * Render packing slip document by return request identifier.
     *
     * @param int       $return_id      Return request identifier.
     * @param string    $lang_code      Language code.
     * @param string    $code           Packing slip code.
     *
     * @return string
     */
    public function renderByReturnId($return_id, $code = 'default', $lang_code)
    {
        $return_info = fn_get_return_info($return_id);

        if ($return_info) {
            $order = new Order($return_info['order_id'], $lang_code);
            $document = $this->repository->findByTypeAndCode($this->getCode(), $code);

            if ($order->data && $document) {
                return $this->render($order, $return_info, $document);
            }
        }

        return '';
    }
}