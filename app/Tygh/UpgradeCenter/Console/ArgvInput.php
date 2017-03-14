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

namespace Tygh\UpgradeCenter\Console;


use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\ArgvInput as BaseArgvInput;

/**
 * ArgvInput represents an input coming from the CLI arguments.
 *
 * @package Tygh\UpgradeCenter\Console
 */
class ArgvInput extends BaseArgvInput
{
    /**
     * @inheritdoc
     */
    public function __construct(array $argv = null, InputDefinition $definition = null)
    {
        if (null === $argv) {
            $argv = $_SERVER['argv'];
        }

        foreach ($argv as $key => $value) {
            if (strpos($value, '--dispatch') === 0) {
                unset($argv[$key]);
                break;
            }
        }

        parent::__construct($argv, $definition);
    }
}