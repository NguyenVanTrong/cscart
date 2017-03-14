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

namespace Tygh\Tests\Unit\Functions\Cms;

class GetPagesTest extends \Tygh\Tests\Unit\ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    protected $app;

    protected function setUp()
    {
        define('BOOTSTRAP', true);
        define('AREA', 'A');
        define('CART_LANGUAGE', 'en');

        $this->requireCore('functions/fn.database.php');
        $this->requireCore('functions/fn.cms.php');

        $this->requireMockFunction('fn_set_hook');
        $this->requireMockFunction('fn_get_area_name');
        $this->requireMockFunction('fn_get_company_condition');
        $this->requireMockFunction('fn_get_schema');
        $this->requireMockFunction('fn_string_not_empty');

        $this->app = \Tygh\Tygh::createApplication();

        // Session (need for LastView)
        $this->app['session'] = new \Tygh\Web\Session($this->app);

        // Driver
        $driver = $this->getMockBuilder('\Tygh\Backend\Database\Pdo')
            ->setMethods(array('escape', 'query', 'insertId'))
            ->getMock();
        $driver->method('escape')->will($this->returnCallback('addslashes'));
        $this->app['db.driver'] = $driver;

        // Connection
        $this->app['db'] = $this->getMockBuilder('\Tygh\Database\Connection')
            ->setMethods(array('error', 'hasError'))
            ->setConstructorArgs(array($driver))
            ->getMock();
    }

    /**
     * @param array  $search Request search params
     * @param string $query  SQL query
     * @dataProvider getPagesQueries
     */
    public function testGetPagesFunc($search, $query)
    {
        $this->app['db.driver']
            ->expects($this->once())
            ->method('query')
            ->with($query);

        fn_get_pages($search);
    }

    public function getPagesQueries()
    {
        return array(
            array(
                array(),
                "SELECT pages.*, page_descriptions.* FROM pages LEFT JOIN page_descriptions ON pages.page_id = page_descriptions.page_id AND page_descriptions.lang_code = 'en' WHERE 1 AND pages.page_type IN ('T', 'L')   ORDER BY pages.position asc, page_descriptions.page asc "
            ),
            array(
                'search' => array(
                    'q' => ' Query " string'
                ),
                'query' => "SELECT pages.*, page_descriptions.* FROM pages LEFT JOIN page_descriptions ON pages.page_id = page_descriptions.page_id AND page_descriptions.lang_code = 'en' WHERE 1 AND ((page_descriptions.page LIKE '%Query \\\" string%')) AND pages.page_type IN ('T', 'L')   ORDER BY pages.position asc, page_descriptions.page asc "
            ),
            array(
                'search' => array(
                    'status' => 'D',
                    'get_tree' => true,
                    'page_type' => 'T',
                ),
                'query' => "SELECT pages.*, page_descriptions.* FROM pages LEFT JOIN page_descriptions ON pages.page_id = page_descriptions.page_id AND page_descriptions.lang_code = 'en' WHERE 1 AND pages.status IN ('D') AND pages.page_type IN ('T')   ORDER BY pages.position asc, pages.parent_id asc, page_descriptions.page asc "
            ),
        );
    }
}
