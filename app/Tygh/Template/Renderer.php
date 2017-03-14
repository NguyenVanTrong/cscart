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


namespace Tygh\Template;


use Tygh\Common\OperationResult;
use Tygh\Twig\TwigEnvironment;

/**
 * The class that implements the logic of template rendering.
 *
 * @package Tygh\Template
 */
class Renderer
{
    const LANGUAGE_VARIABLE_KEY = 'lang_code';
    const CONTEXT_VARIABLE_KEY = '__context';
    const TEMPLATE_VARIABLE_KEY = '__template';
    const VARIABLE_COLLECTION_VARIABLE_KEY = '__variable_collection';

    /** @var \Twig_Environment  */
    protected $twig;

    /**
     * Renderer constructor.
     * @param TwigEnvironment $twig
     */
    public function __construct(TwigEnvironment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Render template.
     *
     * @param ITemplate     $template               Instance of template.
     * @param IContext      $context                Instance of context.
     * @param Collection    $variable_collection    Instance of variable collection.
     *
     * @return string
     */
    public function renderTemplate(ITemplate $template, IContext $context, Collection $variable_collection)
    {
        $variables = $variable_collection->getAll();
        $variables[self::TEMPLATE_VARIABLE_KEY] = $template;
        $variables[self::CONTEXT_VARIABLE_KEY] = $context;
        $variables[self::VARIABLE_COLLECTION_VARIABLE_KEY] = $variable_collection;
        $variables[self::LANGUAGE_VARIABLE_KEY] = $context->getLangCode();

        return $this->render($template->getTemplate(), $variables);
    }

    /**
     * Render string.
     *
     * @param string    $template   String template.
     * @param array     $variables  List of variables.
     *
     * @return string
     */
    public function render($template, array $variables = array())
    {
        return $this->twig->renderString($template, $variables);
    }

    /**
     * Validate template.
     *
     * @param string $template  String template.
     *
     * @return OperationResult
     */
    public function validate($template)
    {
        $result = new OperationResult(true);

        try {
            $this->twig->parse($this->twig->tokenize($template));
        } catch (\Twig_Error_Syntax $e) {
            $result->setSuccess(false);
            $result->addError($e->getCode(), $e->getMessage());
        }

        return $result;
    }

    /**
     * Retrieve variables from template.
     *
     * @param string $template  String template.
     *
     * @return array
     */
    public function retrieveVariables($template)
    {
        $stream = $this->twig->tokenize($template);
        $template_vars = array_unique($this->parseNodes($this->twig->parse($stream)->getNode('body')));

        return array_unique($template_vars);
    }

    /**
     * Parses template nodes to get variables from it.
     *
     * @param object $nodes template nodes.
     *
     * @return array list of variables
     */
    protected function parseNodes($nodes)
    {
        $variables = array();

        foreach ($nodes as $node) {
            $node_class = get_class($node);
            if ($node_class == 'Twig_Node_Expression_Name' || $node_class == 'Twig_Node_Expression_TempName') { // TempName - for php 5.3
                $variables[] = $node->getAttribute('name');
            } elseif ($node instanceof \Twig_Node_Expression_GetAttr) {
                $variables = array_merge($variables, $this->parseNodes($node));
            } elseif ($node instanceof \Traversable) {
                $variables = array_merge($variables, $this->parseNodes($node));
            }
        }

        return $variables;
    }
}