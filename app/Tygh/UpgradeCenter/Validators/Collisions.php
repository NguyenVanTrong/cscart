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

namespace Tygh\UpgradeCenter\Validators;

use Tygh\Registry;
use Tygh\UpgradeCenter\ContentSchema;

/**
 * Upgrade validators: Check collisions
 */
class Collisions implements IValidator
{
    /**
     * Global App config
     *
     * @var array $config
     */
    protected $config = array();

    /**
     * Validator identifier
     *
     * @var array $name ID
     */
    protected $name = 'collisions';

    /**
     * Validate specified data by schema
     *
     * @param  ContentSchema $schema  Incoming validator schema
     * @param  array $request Request data
     * @return array Validation result and Data to be displayed
     */
    public function check($schema, $request)
    {
        $result = true;
        $data = array();
        $files = array();

        if (empty($schema['files'])) {
            return array($result, $data);
        }

        $schema_files = $schema->getFiles(true);

        $files_hash = md5(serialize($schema_files));
        if (!empty($request['skip_collisions'])) {
            fn_set_storage_data('collisions_hash', $files_hash);
        }

        $collisions_hash = fn_get_storage_data('collisions_hash');

        if ($files_hash == $collisions_hash) {
            $request['skip_collisions'] = true;
        }

        if (!empty($schema_files) && !isset($request['skip_collisions'])) {
            foreach ($schema_files as $path => $file_data) {
                $original_path = $this->config['dir']['root'] . '/' . $path;

                switch ($file_data['status']) {
                    case 'changed':
                        if (!file_exists($original_path) || (file_exists($original_path) && md5_file($original_path) != $file_data['hash'])) {
                            $data['changed'][] = $path;
                            $result = false;
                            $files[] = $path;
                        }

                        break;

                    case 'deleted':
                        if (file_exists($original_path) && md5_file($original_path) != $file_data['hash']) {
                            $data['deleted'][] = $path;
                            $result = false;
                            $files[] = $path;
                        }
                        break;

                    case 'new':
                        if (file_exists($original_path)) {
                            $data['new'][] = $path;
                            $result = false;
                            $files[] = $path;
                        }
                        break;
                }
            }

            fn_set_storage_data('collision_files', serialize($files));
        }

        return array($result, $data);
    }

    /**
     * Gets validator name (ID)
     *
     * @return string Name
     */
    public function getName()
    {
        return $this->name;
    }

    public function __construct()
    {
        $this->config = Registry::get('config');
    }
}
