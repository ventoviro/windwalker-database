<?php declare(strict_types=1);
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Database\Test;

use Windwalker\Database\DatabaseFactory;
use Windwalker\Database\DatabaseHelper;
use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\Database\Monitor\CallbackMonitor;
use Windwalker\Query\Query;
use Windwalker\Test\TestHelper;

/**
 * Class DatabaseTestCase
 *
 * @since 2.0
 */
abstract class AbstractDatabaseTestCase extends AbstractQueryTestCase
{
    /**
     * Property db.
     *
     * @var  AbstractDatabaseDriver
     */
    protected static $dbo = null;

    /**
     * Property db.
     *
     * @var  AbstractDatabaseDriver
     */
    protected $db = null;

    /**
     * Property driver.
     *
     * @var string
     */
    protected static $driver = null;

    /**
     * Property quote.
     *
     * @var  array
     */
    protected static $quote = ['"', '"'];

    /**
     * Property dbname.
     *
     * @var string
     */
    protected static $dbname = '';

    /**
     * Property dsn.
     *
     * @var array
     */
    protected static $dsn = [];

    /**
     * Property debug.
     *
     * @var  boolean
     */
    protected static $debug = true;

    /**
     * setUpBeforeClass
     *
     * @throws \LogicException
     * @return  void
     */
    public static function setUpBeforeClass(): void
    {
        if (!static::$driver) {
            throw new \LogicException('static::$driver variable is empty.');
        }

        static::$dsn = $dsn = TestDsnResolver::getDsn(static::$driver);

        if (!$dsn) {
            static::markTestSkipped('DSN of driver ' . static::$driver . ' not available');
        }

        static::$dbname = $dbname = $dsn['dbname'] ?? null;

        if (!$dbname) {
            throw new \LogicException(sprintf('No dbname in %s DSN', static::$driver));
        }

        // Id db exists, return.
        if (static::$dbo) {
            static::$dbo->select($dbname);

            return;
        }

        try {
            DatabaseFactory::reset();

            // Use factory create dbo, only create once and will be singleton.
            $db = self::$dbo = DatabaseFactory::getDbo(
                static::$driver,
                [
                    'host' => $dsn['host'] ?? null,
                    'user' => $dsn['user'] ?? null,
                    'password' => $dsn['pass'] ?? null,
                    'port' => $dsn['port'] ?? null,
                    'prefix' => $dsn['prefix'] ?? null,
                ],
                true
            );

            $db->setDebug(true);

            $logfile = fopen(
                __DIR__ . '/logs/' . str_replace('\\', '_', static::class) . '.sql',
                'ab'
            );

            $db->setMonitor(new CallbackMonitor(
                function ($query) use ($logfile) {
                    fwrite($logfile, $query . "\n\n");
                }
            ));
        } catch (\RangeException $e) {
            static::markTestSkipped($e->getMessage());

            return;
        }

        $database = $db->getDatabase($dbname);

        if (static::$debug) {
            $database->drop(true);
        }

        $database->create(true);

        $db->select($dbname);

        static::setupFixtures();
    }

    /**
     * getInstallSql
     *
     * @return  string
     */
    protected static function getSetupSql()
    {
        return file_get_contents(__DIR__ . '/Stub/' . static::$driver . '.sql');
    }

    /**
     * getTearDownSql
     *
     * @return  string
     */
    protected static function getTearDownSql()
    {
        return 'DROP DATABASE IF EXISTS ' . self::$dbo->quoteName(static::$dbname);
    }

    /**
     * setupFixtures
     *
     * @return  void
     */
    protected static function setupFixtures()
    {
        $queries = static::getSetupSql();

        self::$dbo->execute($queries);
    }

    /**
     * tearDownFixtures
     *
     * @return  void
     */
    protected function tearDownFixtures()
    {
        $queries = static::getTearDownSql();

        DatabaseHelper::batchQuery(static::$dbo, $queries);
    }

    /**
     * tearDownAfterClass
     *
     * @return  void
     */
    public static function tearDownAfterClass(): void
    {
        if (!self::$dbo) {
            return;
        }

        static::$debug or static::tearDownFixtures();

        self::$dbo->disconnect();

        self::$dbo = null;
    }

    /**
     * Destruct.
     */
    public function __destruct()
    {
        if (!self::$dbo) {
            return;
        }

        static::$debug or static::tearDownFixtures();

        self::$dbo->disconnect();

        self::$dbo = null;
    }

    /**
     * Sets up the fixture.
     *
     * This method is called before a test is executed.
     *
     * @return  void
     *
     * @since   2.0
     */
    protected function setUp(): void
    {
        if (empty(static::$dbo)) {
            $this->markTestSkipped('There is no database driver.');
        }

        parent::setUp();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     * @throws \ReflectionException
     */
    protected function tearDown(): void
    {
        $tables = TestHelper::getValue($this->db, 'tables');

        foreach ((array) $tables as $table) {
            $table->reset();
        }

        $this->db = null;
    }
}
