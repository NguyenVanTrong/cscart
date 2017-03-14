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

namespace Tygh\Twig;

use Tygh\Registry;
use Tygh\Template\Collection;
use Tygh\Template\Document\Service;
use Tygh\Template\ITemplate;
use Tygh\Tools\Url;
use Tygh\Template\IContext;
use Tygh\Template\Snippet\Service as SnippetService;
use Tygh\Template\Renderer as BaseRenderer;
use Tygh\Tygh;

/**
 * The extension class for the Twig template engine, that implements basic filters and functions.
 * @package Tygh\Twig
 */
class TwigCoreExtension extends \Twig_Extension
{
    /** @inheritdoc */
    public function getFilters()
    {
        return array(
            'date' => new \Twig_Filter_Method($this, 'dateFilter'),
            'price' => new \Twig_Filter_Method($this, 'priceFilter'),
            'filesize' => new \Twig_Filter_Method($this, 'filesizeFilter'),
            'puny_decode' => new \Twig_Filter_Method($this, 'punyDecodeFilter')
        );
    }

    /** @inheritdoc */
    public function getFunctions()
    {
        return array(
            '__' => new \Twig_Function_Method($this, 'translateFunction', array(
                'needs_environment' => true,
                'needs_context' => true
            )),
            'snippet' => new \Twig_Function_Method($this, 'snippetFunction', array(
                'needs_environment' => true,
                'needs_context' => true
            )),
            'include_doc' => new \Twig_Function_Method($this, 'includeDocFunction', array(
                'needs_environment' => true,
                'needs_context' => true
            )),
        );
    }

    /**
     * @param int|float $size
     * @return string
     */
    public function filesizeFilter($size)
    {
        if (empty($size)) {
            return 0;
        }

        $size = $size / 1024;
        return number_format($size, 0, '', '') . 'K';
    }

    /**
     * @param string $timestamp
     * @param string $format
     * @return string
     */
    public function dateFilter($timestamp, $format)
    {
        return fn_date_format($timestamp, $format);
    }

    /**
     * @param string $price
     * @return string
     */
    public function priceFilter($price)
    {
        $currency =  Registry::get('currencies.' . CART_PRIMARY_CURRENCY);
        $value = fn_format_rate_value(
            $price,
            null,
            $currency['decimals'],
            $currency['decimals_separator'],
            $currency['thousands_separator'],
            $currency['coefficient']
        );

        if ($currency['after'] == 'Y') {
            return $value . ' ' . $currency['symbol'];
        } else {
            return $currency['symbol'] . $value;
        }
    }

    /**
     * Puny decode filter.
     *
     * @param string $url
     * @return string
     */
    public function punyDecodeFilter($url)
    {
        return Url::decode($url, true);
    }

    /**
     * @param \Twig_Environment $env
     * @param string $context
     * @param string $name
     * @param array $placeholders
     * @return string
     */
    public function translateFunction($env, $context, $name, $placeholders = array())
    {
        return __($name, $placeholders, $context['lang_code']);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'tygh.core';
    }

    /**
     * @param TwigEnvironment $env
     * @param array $context
     * @param string $code
     * @param array $args
     * @return mixed
     */
    public function snippetFunction($env, $context, $code, $args = array())
    {
        if (isset($context[BaseRenderer::CONTEXT_VARIABLE_KEY])) {
            /** @var SnippetService $snippet_service */
            $snippet_service = Tygh::$app['template.snippet.service'];
            /** @var IContext $context_instance */
            $context_instance = $context[BaseRenderer::CONTEXT_VARIABLE_KEY];
            /** @var ITemplate $template_instance */
            $template_instance = $context[BaseRenderer::TEMPLATE_VARIABLE_KEY];
            /** @var Collection $variable_collection */
            $variable_collection = clone $context[BaseRenderer::VARIABLE_COLLECTION_VARIABLE_KEY];
            $type = $template_instance->getSnippetType();

            if (!empty($args)) {
                foreach ($args as $key => $val) {
                    $variable_collection->add($key, $val);
                }
            }

            return $snippet_service->renderSnippetByTypeAndCode($type, $code, $context_instance, $variable_collection);
        }

        return '';
    }

    /**
     * @param TwigEnvironment $env
     * @param array $context
     * @param string $code
     * @return string
     */
    public function includeDocFunction($env, $context, $code)
    {
        list($type_code, $template_code) = explode('.', $code, 2);

        if (empty($type_code) || empty($template_code)) {
            return '';
        }

        $params = array_slice(func_get_args(), 3);

        /** @var Service $service */
        $service = Tygh::$app['template.document.service'];

        try {
            return $service->includeDocument($type_code, $template_code, $params, $context['lang_code']);
        } catch (\Exception $e) {}

        return '';
    }
}