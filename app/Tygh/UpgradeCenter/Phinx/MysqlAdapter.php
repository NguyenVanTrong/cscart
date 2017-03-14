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


namespace Tygh\UpgradeCenter\Phinx;

use Phinx\Db\Table;
use Phinx\Db\Adapter\MysqlAdapter as PhinxMysqlAdapter;
use Exception;
use InvalidArgumentException;

/**
 * Phinx MySQL adapter.
 *
 * @since 4.4.1
 * @package Tygh\UpgradeCenter\Phinx
 */
class MysqlAdapter extends PhinxMysqlAdapter
{
    /**
     * {@inheritdoc}
     * Method overridden for solve problem with strict mode.
     * @see https://github.com/robmorgan/phinx/issues/243
     */
    public function createSchemaTable()
    {
        try {
            $options = array(
                'id' => false
            );

            $table = new Table($this->getSchemaTableName(), $options, $this);
            $table->addColumn('version', 'biginteger')
                ->addColumn('start_time', 'timestamp', array('default' => null, 'null' => true))
                ->addColumn('end_time', 'timestamp', array('default' => null, 'null' => true))
                ->save();
        } catch (Exception $exception) {
            throw new InvalidArgumentException('There was a problem creating the schema table: ' . $exception->getMessage());
        }
    }
}