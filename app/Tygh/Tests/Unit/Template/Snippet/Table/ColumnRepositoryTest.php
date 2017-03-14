<?php


namespace Tygh\Tests\Unit\Template\Snippet\Table;


use Tygh\Template\Snippet\Table\Column;
use Tygh\Template\Snippet\Table\ColumnRepository;
use Tygh\Tests\Unit\ATestCase;

class ColumnRepositoryTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;
    
    public function setUp()
    {
        define('DESCR_SL', 'en');
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
     * @param $sort
     * @param $lang_code
     * @param $expected_sql
     * @param $expected_count
     * @dataProvider dpFind
     */
    public function testFind($conditions, $sort, $lang_code, $expected_sql)
    {
        $connection = $this->getConnection();
        $repository = new ColumnRepository($connection, array('en', 'ru'));

        $connection->expects($this->once())
            ->method('getArray')
            ->with($expected_sql, $lang_code, $conditions)
            ->willReturnCallback(array($this, 'findResult'));

        $result = $repository->find($conditions, $sort, $lang_code);

        foreach ($result as $item) {
            $this->assertInstanceOf('\Tygh\Template\Snippet\Table\Column', $item);
        }
    }

    public function dpFind()
    {
        $expected_sql = "SELECT ?:template_table_column_descriptions.*, ?:template_table_columns.* FROM ?:template_table_columns" .
            " LEFT JOIN ?:template_table_column_descriptions ON ?:template_table_columns.column_id = ?:template_table_column_descriptions.column_id AND ?:template_table_column_descriptions.lang_code = ?s" .
            " WHERE ?w";

        return array(
            array(array('column_id' => 100), array('position' => 'ASC'), 'en', $expected_sql . ' ORDER BY ?:template_table_columns.position ASC'),
            array(array('status' => 'A'), array('position' => 'DESC', 'name' => 'ASC'), 'en', $expected_sql . ' ORDER BY ?:template_table_columns.position DESC, ?:template_table_column_descriptions.name ASC'),
        );
    }


    public function testInsert()
    {
        $connection = $this->getConnection();
        $repository = new ColumnRepository($connection, array('en' => 'en', 'ru' => 'ru'));

        $column = Column::fromArray(array(
            'snippet_id' => 120,
            'position' => 500,
            'template' => 'template',
            'default_template' => 'default_template',
            'status' => 'A',
            'addon' => 'addon',
            'name' => 'name',
        ));

        $connection->expects($this->at(0))
            ->method('query')
            ->with("INSERT INTO ?:template_table_columns ?e", $column->toArray(array('column_id', 'name')))
            ->willReturn(10);

        $connection->expects($this->at(1))
            ->method('query')
            ->with("INSERT INTO ?:template_table_column_descriptions ?e", array('column_id' => 10, 'lang_code' => 'en', 'name' => $column->getName()));

        $connection->expects($this->at(2))
            ->method('query')
            ->with("INSERT INTO ?:template_table_column_descriptions ?e", array('column_id' => 10, 'lang_code' => 'ru', 'name' => $column->getName()));

        $repository->save($column);
    }

    public function testUpdate()
    {
        $connection = $this->getConnection();
        $repository = new ColumnRepository($connection, array('en' => 'en', 'ru' => 'ru'));

        $column = Column::fromArray(array(
            'column_id' => 10,
            'snippet_id' => 120,
            'position' => 500,
            'template' => 'template',
            'default_template' => 'default_template',
            'status' => 'A',
            'name' => 'name',
        ));

        $connection->expects($this->at(0))
            ->method('query')
            ->with("UPDATE ?:template_table_columns SET ?u WHERE column_id = ?i", $column->toArray(array('column_id', 'name')), $column->getId());

        $connection->expects($this->at(1))
            ->method('query')
            ->with("UPDATE ?:template_table_column_descriptions SET ?u WHERE column_id = ?i AND lang_code = ?s", array('name' => $column->getName()), $column->getId(), 'en');

        $repository->save($column, 'en');
    }

    public function testRemove()
    {
        $connection = $this->getConnection();
        $repository = new ColumnRepository($connection, array('en' => 'en', 'ru' => 'ru'));

        $column = Column::fromArray(array(
            'column_id' => 10,
            'snippet_id' => 120,
            'position' => 500,
            'template' => 'template',
            'default_template' => 'default_template',
            'status' => 'A',
            'name' => 'name',
        ));

        $connection->expects($this->at(0))
            ->method('query')
            ->with("DELETE FROM ?:template_table_columns WHERE column_id = ?i", $column->getId());

        $connection->expects($this->at(1))
            ->method('query')
            ->with("DELETE FROM ?:template_table_column_descriptions WHERE column_id = ?i", $column->getId());

        $repository->remove($column);
    }

    public function testFindBySnippet()
    {
        /** @var ColumnRepository|\PHPUnit_Framework_MockObject_MockObject  $repository */
        $repository = $this->getMockBuilder('\Tygh\Template\Snippet\Table\ColumnRepository')
            ->setMethods(array('find'))
            ->disableOriginalConstructor()
            ->getMock();

        $type = 'type';
        $code = 'code';
        $sort = array('id' => 'asc');
        $lang = 'ru';

        $repository->expects($this->once())->method('find')->with(array('snippet_type' => $type, 'snippet_code' => $code), $sort, $lang);
        $repository->findBySnippet($type, $code, $sort, $lang);
    }

    public function testRemoveBySnippet()
    {
        /** @var ColumnRepository|\PHPUnit_Framework_MockObject_MockObject  $repository */
        $repository = $this->getMockBuilder('\Tygh\Template\Snippet\Table\ColumnRepository')
            ->setMethods(array('findBySnippet', 'remove'))
            ->disableOriginalConstructor()
            ->getMock();

        $column1 = Column::fromArray(array('id' => 100));
        $column2 = Column::fromArray(array('id' => 200));

        $repository->expects($this->at(0))->method('findBySnippet')->with('order', 'products')->willReturn(array(
            $column1, $column2
        ));

        $repository->expects($this->at(1))->method('remove')->with($column1);
        $repository->expects($this->at(2))->method('remove')->with($column2);

        $repository->removeBySnippet('order', 'products');
    }

    public function findResult()
    {
        return array(
            array(
                'column_id' => 100,
                'snippet_id' => 120,
                'position' => 500,
                'template' => 'template',
                'default_template' => 'default_template',
                'status' => 'A',
                'name' => 'name',
            ),
            array(
                'column_id' => 120,
                'snippet_id' => 120,
                'position' => 500,
                'template' => 'template',
                'default_template' => 'default_template',
                'status' => 'A',
                'name' => 'name',
            ),
        );
    }
}