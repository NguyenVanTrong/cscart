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

namespace Tygh\Tests\Unit;

/**
 * Abstract class for unit test cases
 */
abstract class ATestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Require core file from /app dir, e.g. controller or functions file.
     *
     * @param string $path Path in /app dir.
     */
    public function requireCore($path)
    {
        $path = __DIR__ . '/../../../' . $path;
        if (file_exists($path)) {
            require_once $path;
        } else {
            throw new \Exception('Core file not found: ' . $path);
        }
    }

    /**
     * Require mock function from /_tools/unit_tests/mock_functions dir.
     * 
     * @param string $function Function name
     */
    public function requireMockFunction($function)
    {
        $path = __DIR__ . '/../../../../_tools/unit_tests/mock_functions/' . $function . '.php';
        if (file_exists($path)) {
            require_once $path;
        } else {
            throw new \Exception('You need to create mock function file in: ' . $path);
        }
    }
}
