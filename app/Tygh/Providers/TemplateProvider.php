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

namespace Tygh\Providers;


use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Languages\Values;
use Tygh\Template\Document\Exim as DocumentExim;
use Tygh\Template\Document\Order\Type as OrderType;
use Tygh\Template\Document\PackingSlip\Type as PackingSlipType;
use Tygh\Template\Document\TypeFactory;
use Tygh\Template\Mail\Exim as MailExim;
use Tygh\Template\ObjectFactory;
use Tygh\Template\Renderer;
use Tygh\Template\Snippet\Exim as SnippetExim;
use Tygh\Template\Snippet\Table\ColumnService;
use Tygh\Template\VariableCollectionFactory;
use Tygh\Template\Snippet\Table\ColumnRepository;
use Tygh\Template\Snippet\Repository as SnippetRepository;
use Tygh\Template\Document\Repository as DocumentRepository;
use Tygh\Template\Snippet\Service as SnippetService;
use Tygh\Template\Document\Service as DocumentService;
use Tygh\Template\Mail\Repository as MailRepository;
use Tygh\Template\Mail\Service as MailService;

/**
 * The provider class that registers components for working with the templates of documents, email notifications, and snippets.
 *
 * @package Tygh\Providers
 */
class TemplateProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['template.renderer'] = function ($app) {
            return new Renderer($app['twig']);
        };

        $app['template.object_factory'] = function ($app) {
            return new ObjectFactory($app);
        };

        $app['template.variable_collection_factory'] = function ($app) {
            return new VariableCollectionFactory($app['template.object_factory']);
        };

        $app['template.document.repository'] = function ($app) {
            return new DocumentRepository($app['db']);
        };

        $app['template.document.type_factory'] = function ($app) {
            $types = (array) fn_get_schema('documents', 'types');
            return new TypeFactory($types, $app);
        };

        $app['template.document.service'] = function ($app) {
            $types = (array) fn_get_schema('documents', 'types');
            return new DocumentService($app['template.document.repository'], $types, $app['template.renderer'], $app['template.document.type_factory']);
        };

        $app['template.document.exim'] = function ($app) {
            return new DocumentExim($app['template.document.service'], $app['template.snippet.repository'], $app['template.snippet.exim'], array_keys($app['languages']), new Values());
        };

        $app['template.document.order.type'] = function ($app) {
            return new OrderType($app['template.document.repository'], $app['db'], $app['template.renderer'], $app['template.variable_collection_factory']);
        };

        $app['template.document.packing_slip.type'] = function ($app) {
            return new PackingSlipType($app['template.document.repository'], $app['db'], $app['template.renderer'], $app['template.variable_collection_factory']);
        };

        $app['template.snippet.repository'] = function ($app) {
            return new SnippetRepository($app['db'], $app['languages']);
        };

        $app['template.snippet.service'] = function ($app) {
            return new SnippetService($app['template.snippet.repository'], $app['template.renderer'], $app['template.snippet.table.column_repository']);
        };

        $app['template.snippet.table.column_repository'] = function ($app) {
            return new ColumnRepository($app['db'], $app['languages']);
        };

        $app['template.snippet.table.column_service'] = function ($app) {
            return new ColumnService($app['template.snippet.table.column_repository'], $app['template.snippet.repository'], $app['template.renderer']);
        };

        $app['template.snippet.exim'] = function ($app) {
            return new SnippetExim($app['template.snippet.service'], $app['template.snippet.repository'], $app['template.snippet.table.column_service'], $app['template.snippet.table.column_repository']);
        };

        $app['template.mail.repository'] = function ($app) {
            return new MailRepository($app['db']);
        };

        $app['template.mail.service'] = function ($app) {
            return new MailService($app['template.mail.repository'], $app['template.renderer']);
        };

        $app['template.mail.exim'] = function ($app) {
            return new MailExim($app['template.mail.service'], $app['template.snippet.repository'], $app['template.snippet.exim'], array_keys($app['languages']), new Values());
        };
    }
}