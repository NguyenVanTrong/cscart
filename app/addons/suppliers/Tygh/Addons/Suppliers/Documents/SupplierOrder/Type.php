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

namespace Tygh\Addons\Suppliers\Documents\SupplierOrder;


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
 * @package Tygh\Addons\Suppliers\Documents\Invoice
 */
class Type implements IType, IPreviewableType, IIncludableType
{
    const DOCUMENT_TYPE = 'supplier_order';

    /** @var Repository */
    protected $repository;

    /** @var VariableCollectionFactory */
    protected $collection_factory;

    /** @var Connection */
    protected $connection;

    /** @var Renderer */
    protected $renderer;

    /**
     * Supplier order document type constructor.
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
        $order_id = (int) $this->connection->getField('SELECT MIN(order_id) FROM ?:orders WHERE parent_order_id = 0 ?p', $condition);

        if (empty($order_id)) {
            throw new InputException(__("document_preview_order_not_found"));
        }

        $condition = fn_get_company_condition('?:suppliers.company_id');
        $supplier_id = (int) $this->connection->getField('SELECT MIN(supplier_id) FROM ?:suppliers WHERE 1 ?p', $condition);

        if (empty($supplier_id)) {
            throw new InputException(__("document_preview_suppliers_not_found"));
        }

        $order = new Order($order_id);

        $supplier = fn_get_supplier_data($supplier_id);
        $supplier['cost'] = $order->data['shipping_cost'];
        $supplier['shippings'] = $order->data['shipping'];

        foreach ($supplier['shippings'] as $key => $item) {
            if (empty($item['shipping'])) {
                unset($supplier['shippings'][$key]);
            }
        }

        foreach ($order->data['products'] as &$product) {
            $product['extra']['supplier_id'] = $supplier_id;
        }
        unset($product);

        return $this->render($order, $supplier, $document);
    }

    /**
     * @inheritDoc
     */
    public function includeDocument($code, $lang_code, $params)
    {
        $order_id = (int) array_shift($params);
        $supplier = (array) array_shift($params);

        $order = new Order($order_id);

        if ($order->data) {
            return $this->render($order, $supplier, $code);
        }

        return '';
    }

    /**
     * Render invoice.
     *
     * @param Order             $order         Instance of order.
     * @param array             $supplier      Supplier data.
     * @param Document|string   $document      Instance of document or document template code.
     *
     * @return string
     */
    public function render(Order $order, array $supplier = array(), $document = null)
    {
        $context = new Context($order, $supplier);
        $variable_collection = $this->collection_factory->createCollection(self::SCHEMA_DIR, $this->getCode(), $context);

        if (!$document instanceof Document) {
            $code = is_string($document) ? $document : 'invoice';
            $document = $this->repository->findByTypeAndCode($this->getCode(), $code);
        }

        if ($document) {
            return $this->renderer->renderTemplate($document, $context, $variable_collection);
        }

        return '';
    }
}