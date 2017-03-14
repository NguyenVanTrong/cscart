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

/**
 * Upgrade validators: Check restore.php writable permissions
 */
class Restore implements IValidator
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
    protected $name = 'restore';

    /**
     * Connection status identifier
     *
     * @var bool $ftp_connection_status. Default null.
     */
    protected $ftp_connection_status = null;

    /**
     * Validate specified data by schema
     *
     * @param  array $schema  Incoming validator schema
     * @param  array $request Request data
     * @return array Validation result and Data to be displayed
     */
    public function check($schema, $request)
    {
        $result = true;
        $data = array();

        $upgrades_dir = $this->config['dir']['root'] . '/upgrades';
        $source_restore_file_path = $upgrades_dir . '/source_restore.php';

        if (!file_exists($source_restore_file_path) || !is_readable($source_restore_file_path)) {
            $result = false;
            $data = __('error_exim_file_doesnt_exist') . ': ' . $source_restore_file_path;
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
