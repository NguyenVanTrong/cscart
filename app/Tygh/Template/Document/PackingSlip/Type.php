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

namespace Tygh\Template\Document\PackingSlip;


use Tygh\Database\Connection;
use Tygh\Exceptions\InputException;
use Tygh\Template\Document\Document;
use Tygh\Template\Document\IPreviewableType;
use Tygh\Template\Document\IType;
use Tygh\Template\Document\Order\Order;
use Tygh\Template\Document\Repository;
use Tygh\Template\Renderer;
use Tygh\Template\VariableCollectionFactory;

/**
 * The class that implements the `packing_slip` document type.
 *
 * @package Tygh\Template\Invoice\PackingSlip
 */
class Type implements IType, IPreviewableType
{
    const DOCUMENT_TYPE = 'packing_slip';

    /** @var Repository */
    protected $repository;

    /** @var Connection */
    protected $connection;

    /** @var Renderer */
    protected $renderer;

    /** @var VariableCollectionFactory */
    protected $collection_factory;

    /**
     * Packing slip document type constructor.
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
        $this->repository = $repository;
        $this->connection = $connection;
        $this->renderer = $renderer;
        $this->collection_factory = $collection_factory;
    }
    /**
     * @inheritDoc
     */
    public function preview(Document $document)
    {
        $condition = fn_get_company_condition('?:orders.company_id');
        $order_id = (int) $this->connection->getField('SELECT MIN(order_id) FROM ?:orders WHERE parent_order_id = 0 ?p', $condition);

        if (empty($order_id)) {
            throw new InputException(__("document_preview_order_not_found"));
        }
        
        $order = new Order($order_id);

        return $this->render($order, array(), $document);
    }

    /**
     * @inheritDoc
     */
    public function getCode()
    {
        return self::DOCUMENT_TYPE;
    }

    /**
     * Render packing slip.
     *
     * @param Order      $order      Instance of order.
     * @param array      $shipment   Shipment data.
     * @param Document   $document   Instance of document template.
     *
     * @return string
     */
    public function render(Order $order, array $shipment = array(), Document $document)
    {
        $context = new Context($order, $shipment);
        $variable_collection = $this->collection_factory->createCollection(self::SCHEMA_DIR, $this->getCode(), $context);

        return $this->renderer->renderTemplate($document, $context, $variable_collection);
    }

    /**
     * Render packing slip document by order identifier.
     *
     * @param int       $order_id       Order identifier.
     * @param string    $lang_code      Language code.
     * @param string    $code           Packing slip code.
     *
     * @return string
     */
    public function renderByOrderId($order_id, $lang_code, $code = 'default')
    {
        $order = new Order($order_id, $lang_code);
        $document = $this->repository->findByTypeAndCode(self::DOCUMENT_TYPE, $code);

        if ($order->data && $document) {
            return $this->render($order, array(), $document);
        }

        return '';
    }

    /**
     * Render packing slip document by shipment identifier.
     *
     * @param int       $shipment_id    Shipment identifier.
     * @param string    $lang_code      Language code.
     * @param string    $code           Packing slip code.
     *
     * @return string
     */
    public function renderByShipmentId($shipment_id, $lang_code, $code = 'default')
    {
        list($shipments) = fn_get_shipments_info(array('shipment_id' => $shipment_id, 'advanced_info' => true));

        $shipment = reset($shipments);

        if ($shipment) {
            $order = new Order($shipment['order_id'], $lang_code);
            $document = $this->repository->findByTypeAndCode(Type::DOCUMENT_TYPE, $code);

            if ($order->data && $document) {
                return $this->render($order, $shipment, $document);
            }
        }

        return '';
    }
}