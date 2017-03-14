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

/**
 * The factory of variable collections; it implements the logic of creating collections of variables based on a schema.
 *
 * @package Tygh\Template
 */
class VariableCollectionFactory
{
    /** @var ObjectFactory  */
    protected $object_factory;

    /**
     * VariableCollectionFactory constructor.
     *
     * @param ObjectFactory $object_factory Instance of object factory.
     */
    public function __construct(ObjectFactory $object_factory)
    {
        $this->object_factory = $object_factory;
    }

    /**
     * Create collection from schema.
     *
     * @param string    $schema_dir     Scheme name (subdirectory in /schema directory).
     * @param string    $schema_name    Scheme file name.
     * @param IContext  $context        Instance of context.
     *
     * @return Collection
     */
    public function createCollection($schema_dir, $schema_name, IContext $context)
    {
        $variables = array();
        $schema_variables = $this->getVariablesSchema($schema_dir, $schema_name);

        if ($schema_variables) {
            foreach ($schema_variables as $name => $config) {
                $config['name'] = $name;

                $variable = new VariableProxy($config, $context, $this->object_factory);
                $variable_meta_data = $variable->getMetaData();

                $variables[$variable_meta_data->getName()] = $variable;

                if ($variable_meta_data->getAlias()) {
                    $variables[$variable_meta_data->getAlias()] = $variable;
                }
            }
        }


        return new Collection($variables);
    }

    /**
     * Create collection of variable meta data from schema.
     *
     * @param string    $schema_dir     Scheme name (subdirectory in /schema directory).
     * @param string    $schema_name    Scheme file name.
     *
     * @return Collection
     */
    public function createMetaDataCollection($schema_dir, $schema_name)
    {
        $result = array();
        $schema_variables = $this->getVariablesSchema($schema_dir, $schema_name);

        if (is_array($schema_variables)) {
            foreach ($schema_variables as $name => $item) {
                $item['name'] = $name;
                $result[$name] = new VariableMetaData($item);
            }
        }


        return new Collection($result);
    }

    /**
     * Gets schema of variables.
     *
     * @param string $schema_dir    Scheme name (subdirectory in /schema directory).
     * @param string $schema_name   Scheme file name.
     *
     * @return array
     */
    protected function getVariablesSchema($schema_dir, $schema_name)
    {
        return fn_get_schema($schema_dir, $schema_name);
    }
}