<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2008 - 2014 Asikart.com. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Database\Driver;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Windwalker\Database\Command\AbstractDatabase;
use Windwalker\Database\Command\AbstractReader;
use Windwalker\Database\Command\AbstractTable;
use Windwalker\Database\Command\AbstractTransaction;
use Windwalker\Database\Command\AbstractWriter;
use Windwalker\Database\Iterator\DataIterator;
use Windwalker\Query\Query;

/**
 * Class DatabaseDriver
 *
 * @since {DEPLOY_VERSION}
 */
abstract class DatabaseDriver implements LoggerAwareInterface
{
	/**
	 * The name of the database driver.
	 *
	 * @var    string
	 * @since  {DEPLOY_VERSION}
	 */
	protected $name;

	/**
	 * The name of the database.
	 *
	 * @var    string
	 * @since  {DEPLOY_VERSION}
	 */
	protected $database;

	/**
	 * The database connection resource.
	 *
	 * @var    resource|object
	 * @since  {DEPLOY_VERSION}
	 */
	protected $connection;

	/**
	 * The number of SQL statements executed by the database driver.
	 *
	 * @var    integer
	 * @since  {DEPLOY_VERSION}
	 */
	protected $count = 0;

	/**
	 * The database connection cursor from the last query.
	 *
	 * @var    resource|object
	 * @since  {DEPLOY_VERSION}
	 */
	protected $cursor;

	/**
	 * The database driver debugging state.
	 *
	 * @var    boolean
	 * @since  {DEPLOY_VERSION}
	 */
	protected $debug = false;

	/**
	 * Passed in upon instantiation and saved.
	 *
	 * @var    array
	 * @since  {DEPLOY_VERSION}
	 */
	protected $options;

	/**
	 * The current SQL statement to execute.
	 *
	 * @var    mixed
	 * @since  {DEPLOY_VERSION}
	 */
	protected $query;

	/**
	 * The common database table prefix.
	 *
	 * @var    string
	 * @since  {DEPLOY_VERSION}
	 */
	protected $tablePrefix;

	/**
	 * A logger.
	 *
	 * @var    LoggerInterface
	 * @since  {DEPLOY_VERSION}
	 */
	protected $logger;

	/**
	 * Property reader.
	 *
	 * @var  AbstractReader
	 */
	protected $reader = null;

	/**
	 * Property writer.
	 *
	 * @var AbstractWriter
	 */
	protected $writer;

	/**
	 * Property table.
	 *
	 * @var AbstractTable[]
	 */
	protected $tables = array();

	/**
	 * Property databases.
	 *
	 * @var  AbstractDatabase[]
	 */
	protected $databases = array();

	/**
	 * Property transaction.
	 *
	 * @var AbstractTransaction
	 */
	protected $transaction;

	/**
	 * Constructor.
	 *
	 * @param   null  $connection The database connection instance.
	 * @param   array $options    List of options used to configure the connection
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function __construct($connection = null, $options = array())
	{
		// Initialise object variables.
		$this->connection = $connection;

		$this->database = (isset($options['database'])) ? $options['database'] : '';
		$this->tablePrefix = (isset($options['prefix'])) ? $options['prefix'] : 'wind_';

		// Set class options.
		$this->options = $options;
	}

	/**
	 * getConnection
	 *
	 * @return  resource|object
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 * setConnection
	 *
	 * @param   resource $connection
	 *
	 * @return  DatabaseDriver  Return self to support chaining.
	 */
	public function setConnection($connection)
	{
		$this->connection = $connection;

		return $this;
	}

	/**
	 * Execute the SQL statement.
	 *
	 * @return  mixed  A database cursor resource on success, boolean false on failure.
	 *
	 * @since   {DEPLOY_VERSION}
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		$this->connect();

		if (!is_object($this->connection))
		{
			throw new \RuntimeException('Database disconnected.');
		}

		// Take a local copy so that we don't modify the original query and cause issues later
		$sql = $this->replacePrefix((string) $this->query);

		// Increment the query counter.
		$this->count++;

		// If debugging is enabled then let's log the query.
		if ($this->debug)
		{
			// Add the query to the object queue.
			$this->log(LogLevel::DEBUG, 'Executed: {sql}', array('sql' => $sql));
		}

		try
		{
			$this->doExecute();
		}
		catch (\RuntimeException $e)
		{
			// Throw the normal query exception.
			$this->log(LogLevel::ERROR, 'Database query failed (error #{code}): {message}', array('code' => $e->getCode(), 'message' => $e->getMessage()));

			throw $e;
		}

		return $this;
	}

	/**
	 * connect
	 *
	 * @return  static
	 */
	abstract public function connect();

	/**
	 * Disconnects the database.
	 *
	 * @return  void
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	abstract public function disconnect();

	/**
	 * Execute the SQL statement.
	 *
	 * @return  mixed  A database cursor resource on success, boolean false on failure.
	 *
	 * @since   {DEPLOY_VERSION}
	 * @throws  \RuntimeException
	 */
	abstract protected function doExecute();

	/**
	 * Select a database for use.
	 *
	 * @param   string  $database  The name of the database to select for use.
	 *
	 * @return  boolean  True if the database was successfully selected.
	 *
	 * @since   {DEPLOY_VERSION}
	 * @throws  \RuntimeException
	 */
	abstract public function select($database);

	/**
	 * Get the version of the database connector
	 *
	 * @return  string  The database connector version.
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	abstract public function getVersion();

	/**
	 * Method to free up the memory used for the result set.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  static
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	abstract public function freeResult($cursor = null);

	/**
	 * getDatabaseList
	 *
	 * @return  mixed
	 */
	abstract public function listDatabases();

	/**
	 * getCursor
	 *
	 * @return  resource
	 */
	public function getCursor()
	{
		return $this->cursor;
	}

	/**
	 * Get the current query object or a new Query object.
	 *
	 * @param   boolean  $new  False to return the current query object, True to return a new Query object.
	 *
	 * @return  Query  The current query object or a new object extending the Query class.
	 *
	 * @since   {DEPLOY_VERSION}
	 * @throws  \RuntimeException
	 */
	public function getQuery($new = false)
	{
		if ($new)
		{
			// Derive the class name from the driver.
			$class = '\\Windwalker\\Query\\' . ucfirst($this->name) . '\\' . ucfirst($this->name) . 'Query';

			// Make sure we have a query class for this driver.
			if (!class_exists($class))
			{
				// If it doesn't exist we are at an impasse so throw an exception.
				throw new \RuntimeException('Database Query Class not found.');
			}

			return new $class($this->getConnection());
		}
		else
		{
			return $this->query;
		}
	}

	/**
	 * getTable
	 *
	 * @param string $name
	 *
	 * @return  AbstractTable
	 */
	public function getTable($name)
	{
		if (empty($this->tables[$name]))
		{
			$class = sprintf('Windwalker\\Database\\Driver\\%s\\%sTable', ucfirst($this->name), ucfirst($this->name));

			$this->tables[$name] = new $class($name, $this);
		}

		return $this->tables[$name];
	}

	/**
	 * getTable
	 *
	 * @param string $name
	 *
	 * @return  AbstractDatabase
	 */
	public function getDatabase($name = null)
	{
		$name = $name ? : $this->database;

		if (empty($this->databases[$name]))
		{
			$class = sprintf('Windwalker\\Database\\Driver\\%s\\%sDatabase', ucfirst($this->name), ucfirst($this->name));

			$this->databases[$name] = new $class($name, $this);
		}

		return $this->databases[$name];
	}

	/**
	 * getReader
	 *
	 * @param Query $query
	 *
	 * @return  AbstractReader
	 */
	public function getReader($query = null)
	{
		if ($query)
		{
			$this->setQuery($query);
		}

		if (!$this->reader)
		{
			$class = sprintf('Windwalker\\Database\\Driver\\%s\\%sReader', ucfirst($this->name), ucfirst($this->name));

			$this->reader = new $class($this);
		}

		return $this->reader;
	}

	/**
	 * getWriter
	 *
	 * @return  AbstractWriter
	 */
	public function getWriter()
	{
		if (!$this->writer)
		{
			$class = sprintf('Windwalker\\Database\\Driver\\%s\\%sWriter', ucfirst($this->name), ucfirst($this->name));

			$this->writer = new $class($this);
		}

		return $this->writer;
	}

	/**
	 * getWriter
	 *
	 * @param boolean $nested
	 *
	 * @return  AbstractTransaction
	 */
	public function getTransaction($nested = true)
	{
		if (!$this->transaction)
		{
			$class = sprintf('Windwalker\\Database\\Driver\\%s\\%sTransaction', ucfirst($this->name), ucfirst($this->name));

			$this->transaction = new $class($this, $nested);
		}

		return $this->transaction;
	}

	/**
	 * getIterator
	 *
	 * @param string $class
	 *
	 * @return  DataIterator
	 */
	public function getIterator($class = '\\stdClass')
	{
		return $this->getReader()->getIterator($class);
	}

	/**
	 * Gets the name of the database used by this conneciton.
	 *
	 * @return  string
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function getCurrentDatabase()
	{
		return $this->database;
	}

	/**
	 * Get the common table prefix for the database driver.
	 *
	 * @return  string  The common database table prefix.
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function getPrefix()
	{
		return $this->tablePrefix;
	}

	/**
	 * Logs a message.
	 *
	 * @param   string  $level    The level for the log. Use constants belonging to Psr\Log\LogLevel.
	 * @param   string  $message  The message.
	 * @param   array   $context  Additional context.
	 *
	 * @return  DatabaseDriver  Returns itself to allow chaining.
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function log($level, $message, array $context = array())
	{
		if ($this->logger)
		{
			$this->logger->log($level, $message, $context);
		}

		return $this;
	}

	/**
	 * Sets a logger instance on the object
	 *
	 * @param   LoggerInterface  $logger  A PSR-3 compliant logger.
	 *
	 * @return  static
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;

		return $this;
	}

	/**
	 * This function replaces a string identifier <var>$prefix</var> with the string held is the
	 * <var>tablePrefix</var> class variable.
	 *
	 * @param   string  $sql     The SQL statement to prepare.
	 * @param   string  $prefix  The common table prefix.
	 *
	 * @return  string  The processed SQL statement.
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function replacePrefix($sql, $prefix = '#__')
	{
		$startPos = 0;
		$literal = '';

		$sql = trim($sql);
		$n = strlen($sql);

		while ($startPos < $n)
		{
			$ip = strpos($sql, $prefix, $startPos);

			if ($ip === false)
			{
				break;
			}

			$j = strpos($sql, "'", $startPos);
			$k = strpos($sql, '"', $startPos);

			if (($k !== false) && (($k < $j) || ($j === false)))
			{
				$quoteChar = '"';
				$j = $k;
			}
			else
			{
				$quoteChar = "'";
			}

			if ($j === false)
			{
				$j = $n;
			}

			$literal .= str_replace($prefix, $this->tablePrefix, substr($sql, $startPos, $j - $startPos));
			$startPos = $j;

			$j = $startPos + 1;

			if ($j >= $n)
			{
				break;
			}

			// Quote comes first, find end of quote
			while (true)
			{
				$k = strpos($sql, $quoteChar, $j);
				$escaped = false;

				if ($k === false)
				{
					break;
				}

				$l = $k - 1;

				while ($l >= 0 && $sql{$l} == '\\')
				{
					$l--;
					$escaped = !$escaped;
				}

				if ($escaped)
				{
					$j = $k + 1;
					continue;
				}

				break;
			}

			if ($k === false)
			{
				// Error in the query - no end quote; ignore it
				break;
			}

			$literal .= substr($sql, $startPos, $k - $startPos + 1);
			$startPos = $k + 1;
		}

		if ($startPos < $n)
		{
			$literal .= substr($sql, $startPos, $n - $startPos);
		}

		return $literal;
	}

	/**
	 * Splits a string of multiple queries into an array of individual queries.
	 *
	 * @param   string  $sql  Input SQL string with which to split into individual queries.
	 *
	 * @return  array  The queries from the input string separated into an array.
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public static function splitSql($sql)
	{
		$start = 0;
		$open = false;
		$char = '';
		$end = strlen($sql);
		$queries = array();

		for ($i = 0; $i < $end; $i++)
		{
			$current = substr($sql, $i, 1);

			if (($current == '"' || $current == '\''))
			{
				$n = 2;

				while (substr($sql, $i - $n + 1, 1) == '\\' && $n < $i)
				{
					$n++;
				}

				if ($n % 2 == 0)
				{
					if ($open)
					{
						if ($current == $char)
						{
							$open = false;
							$char = '';
						}
					}
					else
					{
						$open = true;
						$char = $current;
					}
				}
			}

			if (($current == ';' && !$open) || $i == $end - 1)
			{
				$queries[] = substr($sql, $start, ($i - $start + 1));
				$start = $i + 1;
			}
		}

		return $queries;
	}

	/**
	 * Sets the database debugging state for the driver.
	 *
	 * @param   boolean  $level  True to enable debugging.
	 *
	 * @return  static
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function setDebug($level)
	{
		$this->debug = (bool) $level;

		return $this;
	}

	/**
	 * Sets the SQL statement string for later execution.
	 *
	 * @param   mixed  $query The SQL statement to set either as a Query object or a string.
	 *
	 * @return  DatabaseDriver  This object to support method chaining.
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function setQuery($query)
	{
		$this->query = $query;

		return $this;
	}

	/**
	 * loadAll
	 *
	 * @param string $key
	 * @param string $class
	 *
	 * @return  mixed
	 */
	public function loadAll($key = null, $class = '\\stdClass')
	{
		if (strtolower($class) == 'array')
		{
			return $this->getReader()->loadArrayList($key);
		}

		if (strtolower($class) == 'assoc')
		{
			return $this->getReader()->loadAssocList($key);
		}

		return $this->getReader()->loadObjectList($key, $class);
	}

	/**
	 * loadOne
	 *
	 * @param string $class
	 *
	 * @return  mixed
	 */
	public function loadOne($class = '\\stdClass')
	{
		if (strtolower($class) == 'array')
		{
			return $this->getReader()->loadArray();
		}

		if (strtolower($class) == 'assoc')
		{
			return $this->getReader()->loadAssoc();
		}

		return $this->getReader()->loadObject($class);
	}

	/**
	 * loadResult
	 *
	 * @return  mixed
	 */
	public function loadResult()
	{
		return $this->getReader()->loadResult();
	}

	/**
	 * loadColumn
	 *
	 * @return  mixed
	 */
	public function loadColumn()
	{
		return $this->getReader()->loadColumn();
	}

	/**
	 * getIndependentQuery
	 *
	 * @return  Query
	 */
	private function getIndependentQuery()
	{
		static $query;

		if (!$query)
		{
			$query = $this->getQuery(true);
		}

		return $query;
	}

	/**
	 * quoteName
	 *
	 * @param string $text
	 *
	 * @return  mixed
	 */
	public function quoteName($text)
	{
		return $this->getIndependentQuery()->quoteName($text);
	}

	/**
	 * qn
	 *
	 * @param string $text
	 *
	 * @return  mixed
	 */
	public function qn($text)
	{
		return $this->quoteName($text);
	}

	/**
	 * quote
	 *
	 * @param string $text
	 * @param bool   $escape
	 *
	 * @return  string
	 */
	public function quote($text, $escape = true)
	{
		return $this->getIndependentQuery()->quote($text, $escape);
	}

	/**
	 * q
	 *
	 * @param string $text
	 * @param bool   $escape
	 *
	 * @return  string
	 */
	public function q($text, $escape = true)
	{
		return $this->quote($text, $escape);
	}

	/**
	 * escape
	 *
	 * @param string $text
	 * @param bool   $extra
	 *
	 * @return  string
	 */
	public function escape($text, $extra = true)
	{
		return $this->getIndependentQuery()->escape($text, $extra);
	}

	/**
	 * e
	 *
	 * @param string $text
	 * @param bool   $extra
	 *
	 * @return  string
	 */
	public function e($text, $extra = true)
	{
		return $this->escape($text, $extra);
	}

	/**
	 * Method to get property Name
	 *
	 * @return  string
	 */
	public function getName()
	{
		return $this->name;
	}
}
