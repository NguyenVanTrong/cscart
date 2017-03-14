<?php


namespace Tygh\Tests\Unit\Template\Snippet;

use Tygh\Template\Snippet\Repository;
use Tygh\Template\Snippet\Snippet;
use Tygh\Tests\Unit\ATestCase;

class RepositoryTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    public function setUp()
    {
        define('CART_LANGUAGE', 'en');

        $this->requireMockFunction('fn_set_hook');
        parent::setUp();
    }

    protected function getConnection()
    {
        $connection = $this->getMockBuilder('\Tygh\Database\Connection')
            ->setMethods(array('error', 'getRow', 'query', 'getArray', 'hasError'))
            ->disableOriginalConstructor()
            ->getMock();

        return $connection;
    }

    /**
     * @param $conditions
     * @param $lang_code
     * @param $expected_sql
     * @param $expected_count
     * @dataProvider dpFind
     */
    public function testFind($conditions, $lang_code, $expected_sql, $expected_count)
    {
        $connection = $this->getConnection();
        $repository = new Repository($connection, array('en', 'ru'));

        $connection->expects($this->once())
            ->method('getArray')
            ->with($expected_sql, $lang_code, $conditions)
            ->willReturnCallback(array($this, 'findResult'));

        $result = $repository->find($conditions, $lang_code);

        $this->assertCount($expected_count, $result);

        foreach ($result as $item) {
            $this->assertInstanceOf('Tygh\Template\Snippet\Snippet', $item);
        }
    }

    public function dpFind()
    {
        $expected_sql = "SELECT ?:template_snippet_descriptions.*, ?:template_snippets.*"
                . " FROM ?:template_snippets"
                ." LEFT JOIN ?:template_snippet_descriptions"
                ." ON ?:template_snippets.snippet_id = ?:template_snippet_descriptions.snippet_id AND ?:template_snippet_descriptions.lang_code = ?s"
                ." WHERE ?w ORDER BY ?:template_snippet_descriptions.name ASC";

        return array(
            array(array('code' => 'code', 'find_result' => 1), 'en', $expected_sql, 2),
            array(array('code' => 'code', 'find_result' => 2), 'en', $expected_sql, 1)
        );
    }

    /**
     * @param $type
     * @param $code
     * @param $lang_code
     * @dataProvider dpFindByTypeAndCode
     */
    public function testFindByTypeAndCode($type, $code, $lang_code)
    {
        $connection = $this->getConnection();
        $repository = new Repository($connection, array('en', 'ru'));

        $connection->expects($this->once())
            ->method('getArray')
            ->with($this->anything(), $lang_code, array('type' => $type, 'code' => $code))
            ->willReturn(array());

        $repository->findByTypeAndCode($type, $code, $lang_code);
    }

    public function dpFindByTypeAndCode()
    {
        return array(
            array('mail', 'code1', 'ru'),
            array('order', 'code2', 'en'),
        );
    }

    /**
     * @param $type
     * @param $lang_code
     * @dataProvider dpFindByType
     */
    public function testFindByType($type, $lang_code)
    {
        $connection = $this->getConnection();
        $repository = new Repository($connection, array('en', 'ru'));

        $connection->expects($this->once())
            ->method('getArray')
            ->with($this->anything(), $lang_code, array('type' => $type))
            ->willReturn(array());

        $repository->findByType($type, $lang_code);
    }

    public function dpFindByType()
    {
        return array(
            array('type1', 'ru'),
            array('type2', 'en'),
        );
    }

    /**
     * @param $id
     * @param $lang_code
     * @dataProvider dpFindById
     */
    public function testFindById($id, $lang_code)
    {
        $connection = $this->getConnection();
        $repository = new Repository($connection, array('en', 'ru'));

        $connection->expects($this->once())
            ->method('getArray')
            ->with($this->anything(), $lang_code, array('?:template_snippets.snippet_id' => $id))
            ->willReturn(array());

        $repository->findById($id, $lang_code);
    }

    public function dpFindById()
    {
        return array(
            array(1, 'ru'),
            array(2, 'en'),
        );
    }

    /**
     * @param $snippet
     * @param $languages
     * @param $expected_queries
     * @param $lang_code
     * @dataProvider dpSave
     */
    public function testSave($snippet, $languages, $expected_queries, $lang_code)
    {
        $connection = $this->getConnection();
        $repository = new Repository($connection, $languages);

        foreach ($expected_queries as $key => $item) {
            $connection->expects($this->at($key))
                ->method('query')
                ->withConsecutive($item)
                ->willReturn(1);
        }

        $repository->save($snippet, $lang_code);
    }

    public function dpSave()
    {
        $base_data = array(
            'code' => 'code1',
            'status' => 'A',
            'template' => 'template1',
            'default_template' => 'default_template1',
            'type' => 'type',
            'created' => 1234567,
            'updated' => 1234567,
            'params' => array('key' => 'val'),
            'addon' => 'addon',
            'handler' => 'func',
        );

        $lang_data = array(
            'name' => 'name',
        );

        $snippet1 = Snippet::fromArray(array_merge($base_data, $lang_data));

        $snippet2 = clone $snippet1;
        $snippet2->setId(100);

        $base_data['params'] = json_encode($base_data['params']);
        $base_data['handler'] = json_encode($base_data['handler']);

        return array(
            array(
                $snippet1,
                array('en' => 'en', 'ru' => 'ru'),
                array(
                    array("INSERT INTO ?:template_snippets ?e", $base_data),
                    array("INSERT INTO ?:template_snippet_descriptions ?e", array_merge($lang_data, array('snippet_id' => 1, 'lang_code' => 'en'))),
                    array("INSERT INTO ?:template_snippet_descriptions ?e", array_merge($lang_data, array('snippet_id' => 1, 'lang_code' => 'ru')))
                ),
                'en'
            ),
            array(
                $snippet2,
                array('en' => 'en', 'ru' => 'ru'),
                array(
                    array("UPDATE ?:template_snippets SET ?u WHERE snippet_id = ?i", $base_data, 100),
                    array("UPDATE ?:template_snippet_descriptions SET ?u WHERE snippet_id = ?i AND lang_code = ?s", $lang_data, 100, 'en'),
                ),
                'en'
            )
        );
    }

    public function testRemove()
    {
        $connection = $this->getConnection();
        $repository = new Repository($connection, array('en'));

        $snippet = new Snippet();
        $snippet->setId(100);

        $connection->expects($this->at(0))
            ->method('query')
            ->with("DELETE FROM ?:template_snippets WHERE snippet_id = ?i", 100)
            ->willReturn(1);

        $connection->expects($this->at(1))
            ->method('query')
            ->with("DELETE FROM ?:template_snippet_descriptions WHERE snippet_id = ?i", 100)
            ->willReturn(1);

        $repository->remove($snippet);
    }

    public function findResult($expected_sql, $lang_code, $conditions)
    {
        switch ($conditions['find_result']) {
            case 1:
                $result = array(
                    array(
                        'snippet_id' => 1,
                        'code' => 'code1',
                        'context_handler' => '',
                        'name' => 'name1',
                        'updated' => '143568899',
                        'created' => '143568899',
                    ),
                    array(
                        'snippet_id' => 2,
                        'code' => 'code2',
                        'context_handler' => '',
                        'name' => 'name2',
                        'updated' => '143568899',
                        'created' => '143568899',
                    ),
                );
                break;
            case 2:
                $result = array(
                    array(
                        'snippet_id' => 3,
                        'context_handler' => '',
                        'code' => 'code3',
                        'name' => 'name3',
                        'updated' => '143568899',
                        'created' => '143568899',
                    )
                );
                break;
            default:
                $result = array();
                break;
        }

        return $result;
    }
}