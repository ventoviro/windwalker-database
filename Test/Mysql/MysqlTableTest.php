<?php declare(strict_types=1);
/**
 * Part of Windwalker project Test files.  @codingStandardsIgnoreStart
 *
 * @copyright  Copyright (C) 2019 LYRASOFT Taiwan, Inc.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Database\Test\Mysql;

use Windwalker\Database\Schema\Column;
use Windwalker\Database\Schema\DataType;
use Windwalker\Database\Schema\Schema;
use Windwalker\Query\Mysql\MysqlGrammar;

/**
 * Test class of MysqlTable
 *
 * @since 2.0
 */
class MysqlTableTest extends AbstractMysqlTestCase
{
    /**
     * Property builder.
     *
     * @var  string
     */
    protected $builder = 'Windwalker\Query\Mysql\MysqlQueryGrammar';

    /**
     * getBuilder
     *
     * @return  string
     */
    protected function getBuilder()
    {
        return $this->builder;
    }

    /**
     * Method to test getName().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Command\AbstractTable::getName
     */
    public function testGetName()
    {
        $table = $this->db->getTable('#__flower');

        $this->assertEquals('#__flower', $table->getName());

        $table->setDatabase($this->db->getDatabase());

        $this->assertEquals('#__flower', $table->getName());

        $table->setDatabase($this->db->getDatabase('foo'));

        $this->assertEquals('foo.#__flower', $table->getName());
    }

    /**
     * Method to test setName().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Command\AbstractTable::setName
     */
    public function testSetName()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test getDriver().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Command\AbstractTable::getDriver
     */
    public function testGetDriver()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test setDriver().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Command\AbstractTable::setDriver
     * @TODO   Implement testSetDriver().
     */
    public function testSetDriver()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test create().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Mysql\MysqlTable::create
     */
    public function testCreate()
    {
        $table = $this->db->getTable('#__cloud');

        $table->create(
            function (Schema $schema) {
                $schema->primary('id')->comment('PK');
                $schema->varchar('name')->length(190)->allowNull(false);
                $schema->varchar('alias')->length(190);
                $schema->float('float');
                $schema->datetime('created')->defaultValue(null);

                $schema->addIndex('name', 'idx_name')->comment('Test');
                $schema->addIndex('float');
                $schema->addUniqueKey('alias', 'idx_alias')->comment('Alias Index');
            }
        );

        $columns = $table->getColumnDetails();

        $this->assertEquals('int(11) unsigned', $columns['id']->Type);
        $this->assertEquals('varchar(190)', $columns['name']->Type);
        $this->assertEquals('UNI', $columns['alias']->Key);
        $this->assertEquals('float(10,2) unsigned', $columns['float']->Type);
        $this->assertEquals($this->db->getQuery(true)->getNullDate(), $columns['created']->Default);

        $this->assertTrue($table->hasIndex('idx_cloud_float'));
    }

    /**
     * testStrictModeCreate
     *
     * @return  void
     */
    public function testStrictMode()
    {
        $table = $this->db->getTable('#__strict');

        $table->create(
            function (Schema $schema) {
                $schema->primary('id')->comment('PK');
                $schema->datetime('date')->allowNull(false)->defaultValue(null);
                $schema->varchar('data')->allowNull(false)->defaultValue('test');
            }
        );

        $columns = $table->getColumnDetails();

        $this->assertEquals($this->db->getQuery(true)->getNullDate(), $columns['date']->Default);

        try {
            $this->db->setQuery('INSERT #__strict VALUES (1, "2013-07-12T03:00:00+07:00", "")')->execute();
        } catch (\PDOException $e) {
            // SQLSTATE[22007]: Invalid datetime format: 1292 Incorrect datetime value: '2013-07-12T03:00:00+07:00' for column 'date' at row 1
            $this->assertEquals(22007, $e->getCode());
        }

        try {
            $this->db->setQuery('INSERT #__strict VALUES (1, "2013-07-12 03:00:00", NULL)')->execute();
        } catch (\PDOException $e) {
            // SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'data' cannot be null
            $this->assertEquals(23000, $e->getCode());
        }
    }

    /**
     * Method to test rename().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Mysql\MysqlTable::rename
     */
    public function testRename()
    {
        $table = $this->db->getTable('#__cloud');

        $table = $table->rename('#__wind');

        $columns = $table->getColumnDetails();

        $this->assertEquals('int(11) unsigned', $columns['id']->Type);
        $this->assertEquals('varchar(190)', $columns['name']->Type);
    }

    /**
     * Method to test lock().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Mysql\MysqlTable::lock
     * @TODO   Implement testLock().
     */
    public function testLock()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test unlock().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Mysql\MysqlTable::unlock
     * @TODO   Implement testUnlock().
     */
    public function testUnlock()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test truncate().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Mysql\MysqlTable::truncate
     */
    public function testTruncate()
    {
        $table = $this->db->getTable('#__categories');

        $table->truncate();

        $items = $this->db->getReader('SELECT * FROM #__categories')->loadObjectList();

        $this->assertEquals([], $items);
    }

    /**
     * Method to test getColumns().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Mysql\MysqlTable::getColumns
     */
    public function testGetColumns()
    {
        $columns = $this->db->getTable('#__categories')->getColumns();

        $this->assertEquals(['id', 'title', 'ordering', 'params'], $columns);
    }

    /**
     * Method to test getColumnDetails().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Mysql\MysqlTable::getColumnDetails
     */
    public function testGetColumnDetails()
    {
        $columns = $this->db->getTable('#__categories')->getColumnDetails();

        $this->assertEquals('id', $columns['id']->Field);
        $this->assertEquals('varchar(255)', $columns['title']->Type);
    }

    /**
     * Method to test getColumnDetail().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Mysql\MysqlTable::getColumnDetail
     */
    public function testGetColumnDetail()
    {
        $column = $this->db->getTable('#__categories')->getColumnDetail('id');

        $this->assertEquals('id', $column->Field);
        $this->assertEquals('auto_increment', $column->Extra);
    }

    /**
     * Method to test addColumn().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Mysql\MysqlTable::addColumn
     */
    public function testAddColumn()
    {
        $table = $this->db->getTable('#__categories');

        $table->addColumn(
            'state',
            DataType::INTEGER,
            Column::SIGNED,
            Column::NOT_NULL,
            0,
            'State',
            ['position' => 'AFTER ordering', 'length' => 1]
        );

        $columns = $table->getColumns();

        $this->assertEquals(['id', 'title', 'ordering', 'state', 'params'], $columns);
    }

    /**
     * Method to test dropColumn().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Mysql\MysqlTable::dropColumn
     */
    public function testDropColumn()
    {
        $table = $this->db->getTable('#__categories', true);

        $table->dropColumn('state');

        $columns = $table->getColumns();

        $this->assertEquals(['id', 'title', 'ordering', 'params'], $columns);
    }

    /**
     * Method to test modifyColumn()
     *
     * @return  void
     *
     * @covers  \Windwalker\Database\Driver\Mysql\MysqlTable::modifyColumn
     */
    public function testModifyColumn()
    {
        $table = $this->db->getTable('#__categories', true);

        $table->addColumn(new Column\Varchar('foo'));

        $table->modifyColumn(new Column\Integer('foo'));

        $tables = $table->getColumnDetails();

        $this->assertEquals('int(11) unsigned', $tables['foo']->Type);

        $table->modifyColumn(new Column\Tinyint('foo', 3, Column::SIGNED));

        $tables = $table->getColumnDetails();

        $this->assertEquals('tinyint(3)', $tables['foo']->Type);
    }

    /**
     * testChangeColumn
     *
     * @return  void
     */
    public function testChangeColumn()
    {
        $table = $this->db->getTable('#__categories', true);

        $table->changeColumn('foo', new Column\Integer('bar'));

        $tables = $table->getColumnDetails(true);

        $this->assertEquals('int(11) unsigned', $tables['bar']->Type);
        $this->assertArrayNotHasKey('foo', $tables);
    }

    /**
     * Method to test getIndexes().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Mysql\MysqlTable::getIndexes
     */
    public function testGetIndexes()
    {
        $table = $this->db->getTable('#__categories', true);

        $indexes = $table->getIndexes();

        $this->assertEquals('PRIMARY', $indexes[0]->Key_name);
    }

    /**
     * Method to test addIndex().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Mysql\MysqlTable::addIndex
     */
    public function testAddIndex()
    {
        $table = $this->db->getTable('#__categories', true);

        $table->addIndex('key', ['ordering', 'id'], 'idx_ordering');

        $indexes = $table->getIndexes();

        $this->assertEquals('PRIMARY', $indexes[0]->Key_name);
        $this->assertEquals('idx_ordering', $indexes[1]->Key_name);
        $this->assertEquals('id', $indexes[2]->Column_name);
    }

    /**
     * Method to test dropIndex().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Mysql\MysqlTable::dropIndex
     */
    public function testDropIndex()
    {
        $table = $this->db->getTable('#__categories', true);

        $table->dropIndex('idx_ordering');

        $indexes = $table->getIndexes();

        $this->assertEquals(1, count($indexes));

        $table->modifyColumn('id', DataType::INTEGER, Column::UNSIGNED, Column::NOT_NULL, 0);
        $table->dropIndex('PRIMARY');

        $indexes = $table->getIndexes();

        $this->assertEquals(0, count($indexes));
    }

    /**
     * testDrop
     *
     * @return  void
     *
     * @since  3.5
     */
    public function testDrop()
    {
        $this->db->getTable('#__strict')->drop(true);

        self::assertFalse(in_array('ww_strict', $this->db->getDatabase()->getTables(true)));
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        parent::tearDownAfterClass();
    }

    /**
     * tearDownAfterClass
     *
     * @return  void
     */
    public static function tearDownAfterClass(): void
    {
        if (static::$dbo) {
            try {
                static::$dbo->setQuery(MysqlGrammar::dropTable('#__cloud', true))->execute();
            } catch (\Exception $e) {
                // Do nothing
            }

            try {
                static::$dbo->setQuery(MysqlGrammar::dropTable('#__wind', true))->execute();
            } catch (\Exception $e) {
                // Do nothing
            }
        }

        parent::tearDownAfterClass();
    }
}
