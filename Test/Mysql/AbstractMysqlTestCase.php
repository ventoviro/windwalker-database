<?php declare(strict_types=1);
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Database\Test\Mysql;

use Windwalker\Database\Driver\Mysql\MysqlDriver;
use Windwalker\Database\Test\AbstractDatabaseTestCase;

/**
 * The AbstractMysqlTest class.
 *
 * @since  2.0
 */
abstract class AbstractMysqlTestCase extends AbstractDatabaseTestCase
{
    /**
     * Property driver.
     *
     * @var  string
     */
    protected static $driver = 'mysql';

    /**
     * Property quote.
     *
     * @var  array
     */
    protected static $quote = ['`', '`'];

    /**
     * Property db.
     *
     * @var MysqlDriver
     */
    protected $db;

    /**
     * Property connection.
     *
     * @var \PDO
     */
    protected $connection;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = static::$dbo;
        $this->connection = $this->db->getConnection();

        // Set Mysql to strict mode
        $modes = [
            'ONLY_FULL_GROUP_BY',
            'STRICT_TRANS_TABLES',
            'ERROR_FOR_DIVISION_BY_ZERO',
            'NO_AUTO_CREATE_USER',
            'NO_ENGINE_SUBSTITUTION',
            'NO_ZERO_DATE',
            'NO_ZERO_IN_DATE',
        ];

        $this->connection->exec("SET @@SESSION.sql_mode = '" . implode(',', $modes) . "';");
    }
}
