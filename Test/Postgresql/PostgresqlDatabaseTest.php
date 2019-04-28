<?php declare(strict_types=1);
/**
 * Part of Windwalker project Test files.  @codingStandardsIgnoreStart
 *
 * @copyright  Copyright (C) 2019 LYRASOFT Taiwan, Inc.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Database\Test\Postgresql;

use Windwalker\Database\Driver\Postgresql\PostgresqlDriver;
use Windwalker\Query\Postgresql\PostgresqlGrammar;

/**
 * Test class of PostgresqlDatabase
 *
 * @since 2.0
 */
class PostgresqlDatabaseTest extends AbstractPostgresqlTestCase
{
    /**
     * testAutoSelect
     *
     * @return  void
     * @throws \ReflectionException
     */
    public function testAutoSelect()
    {
        $option = static::$dsn;

        $option['database'] = static::$dsn['dbname'];
        $option['password'] = static::$dsn['pass'];

        $db = new PostgresqlDriver(null, $option);

        $e = null;

        try {
            $db->setQuery('SELECT * FROM #__flower')->loadAll();
        } catch (\Exception $e) {
            // No action
        }

        $this->assertNull($e, '$e should not be an exception.');
    }

    /**
     * Method to test select().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Postgresql\PostgresqlDatabase::select
     */
    public function testSelect()
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
     * @covers \Windwalker\Database\Driver\Postgresql\PostgresqlDatabase::create
     */
    public function testCreate()
    {
        $database = $this->db->getDatabase('windwalker_foo_test');

        $database->create(true);

        $dbs = $this->db->listDatabases();

        $this->assertContains('windwalker_foo_test', $dbs, 'DB: "windwalker_foo_test" not in db name list.');
    }

    /**
     * Method to test drop().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Postgresql\PostgresqlDatabase::drop
     */
    public function testDrop()
    {
        $database = $this->db->getDatabase('windwalker_foo_test');

        $database->drop(true);

        $dbs = $this->db->listDatabases();

        $this->assertNotContains('windwalker_foo_test', $dbs, 'DB: "windwalker_foo_test" should not in db name list.');
    }

    /**
     * Methos to test DB exists.
     *
     * @return  void
     *
     * @covers \Windwalker\Database\Driver\Postgresql\PostgresqlDatabase::exsts
     */
    public function testExists()
    {
        $database = $this->db->getDatabase('windwalker_foo_test');

        $database->create(true);

        $this->assertTrue($this->db->getDatabase('windwalker_foo_test')->exists());
        $this->assertFalse($this->db->getDatabase('windwalker_bar_test')->exists());

        $database->drop();
    }

    /**
     * Method to test rename().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Postgresql\PostgresqlDatabase::rename
     */
    public function testRename()
    {
        $database = $this->db->getDatabase(static::$dsn['dbname']);

        $tables = $database->getTables(true);

        $database = $database->rename('windwalker_bar_test');

        // Check new db object
        $this->assertEquals('windwalker_bar_test', $database->getName(), 'Returned object should be new database');

        $dbs = $this->db->listDatabases();

        // Check new DB exists
        $this->assertContains('windwalker_bar_test', $dbs, 'DB: "windwalker_bar_test" not in db name list.');

        // Check new DB tables
        $this->assertEquals($tables, $database->getTables(true));

        // Rename back
        $database->rename(static::$dsn['dbname']);
    }

    /**
     * Method to test getTables().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Postgresql\PostgresqlDatabase::getTables
     */
    public function testGetTables()
    {
        $tables = $this->db->getDatabase(static::$dbname)->getTables();

        $this->assertEquals(
            [
                static::$dsn['prefix'] . 'categories',
                static::$dsn['prefix'] . 'flower',
                static::$dsn['prefix'] . 'nestedsets',
            ],
            $tables
        );
    }

    /**
     * Method to test getTableDetails().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Postgresql\PostgresqlDatabase::getTableDetails
     */
    public function testGetTableDetails()
    {
        $tables = $this->db->getDatabase(static::$dbname)->getTableDetails();

        $this->assertEquals(static::$dsn['prefix'] . 'flower', $tables[static::$dsn['prefix'] . 'flower']->Name);
    }

    /**
     * Method to test getTableDetail().
     *
     * @return void
     *
     * @covers \Windwalker\Database\Driver\Postgresql\PostgresqlDatabase::getTableDetail
     */
    public function testGetTableDetail()
    {
        $table = $this->db->getDatabase(static::$dbname)->getTableDetail('#__flower');

        $this->assertEquals(static::$dsn['prefix'] . 'flower', $table->Name);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if (!$this->db) {
            return;
        }

        $this->db->setQuery(PostgresqlGrammar::dropDatabase('windwalker_foo_test', true))->execute();
        $this->db->setQuery(PostgresqlGrammar::dropDatabase('windwalker_bar_test', true))->execute();

        parent::__destruct();
    }

    /**
     * tearDownAfterClass
     *
     * @return  void
     */
    public static function tearDownAfterClass(): void
    {
        static::$dbo->setQuery(PostgresqlGrammar::dropDatabase('windwalker_foo_test', true))->execute();
        static::$dbo->setQuery(PostgresqlGrammar::dropDatabase('windwalker_bar_test', true))->execute();

        parent::tearDownAfterClass();
    }
}
