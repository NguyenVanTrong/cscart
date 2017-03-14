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

namespace Tygh\Template\Snippet\Table;


use Tygh\Template\Document\Order\Context;
use Tygh\Template\IContext;
use Tygh\Template\IVariable;
use Tygh\Template\Renderer;
use Tygh\Template\Snippet\Snippet;
use Tygh\Template\VariableCollectionFactory;

/**
 * The class of the `table` variable that allows access to the table.
 *
 * @package Tygh\Template\Snippet\Table
 */
class TableVariable implements IVariable
{
    /** @var array */
    public $headers = array();

    /** @var array */
    public $rows = array();

    /** @var Context */
    protected $context;

    /** @var Snippet */
    protected $snippet;

    /** @var Renderer */
    protected $renderer;

    /** @var ColumnRepository */
    protected $column_repository;

    /** @var VariableCollectionFactory */
    protected $collection_factory;

    /**
     * TableVariable constructor.
     *
     * @param IContext                  $context            Instance of parent context.
     * @param Snippet                   $snippet            Instance of snippet.
     * @param Renderer                  $renderer           Instance of template renderer.
     * @param ColumnRepository          $column_repository  Instance of column repository.
     * @param VariableCollectionFactory $collection_factory Instance of variable collection factory.
     * @param array                     $items              Table items.
     */
    public function __construct(
        IContext $context,
        Snippet $snippet,
        Renderer $renderer,
        ColumnRepository $column_repository,
        VariableCollectionFactory $collection_factory,
        array $items = array()
    )
    {
        $this->context = $context;
        $this->snippet = $snippet;
        $this->renderer = $renderer;
        $this->column_repository = $column_repository;
        $this->collection_factory = $collection_factory;

        $columns = $this->getColumns();
        $variable_schema = $snippet->getParam('variable_schema', $snippet->getType() . '_' . $snippet->getCode());

        foreach ($columns as $column) {
            $this->headers[] = $column->getName();
        }

        foreach ($items as $item) {
            $item_context = new ItemContext($this->context, $item);
            $collection = $this->collection_factory->createCollection('snippets', $variable_schema, $item_context);
            $cols = array();

            foreach ($columns as $column) {
                $cols[] = $this->renderer->renderTemplate($column, $item_context, $collection);
            }

            $this->rows[] = $cols;
        }
    }

    /**
     * @return Column[]
     */
    protected function getColumns()
    {
        return $this->column_repository->findActiveBySnippet(
            $this->snippet->getType(),
            $this->snippet->getCode(),
            array('position' => 'asc'),
            $this->context->getLangCode()
        );
    }
}