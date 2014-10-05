<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2008 - 2014 Asikart.com. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Database\Query;

use Windwalker\Compare\Compare;
use Windwalker\Database\DatabaseFactory;
use Windwalker\Database\Driver\DatabaseDriver;
use Windwalker\Query\Query;
use Windwalker\Query\QueryElement;

/**
 * Class QueryHelper
 */
class QueryHelper
{
	/**
	 * Property db.
	 *
	 * @var  DatabaseDriver
	 */
	protected $db = null;

	/**
	 * Property tables.
	 *
	 * @var  array
	 */
	protected $tables = array();

	/**
	 * Constructor.
	 *
	 * @param DatabaseDriver $db
	 */
	public function __construct(DatabaseDriver $db = null)
	{
		$this->db = $db ? : $this->getDb();
	}

	/**
	 * addTable
	 *
	 * @param string  $alias
	 * @param string  $table
	 * @param mixed   $condition
	 * @param string  $joinType
	 * @param boolean $prefix
	 *
	 * @return  QueryHelper
	 */
	public function addTable($alias, $table, $condition = null, $joinType = 'LEFT', $prefix = null)
	{
		$tableStorage = array();

		$tableStorage['name'] = $table;
		$tableStorage['join']  = strtoupper($joinType);

		if (is_array($condition))
		{
			$condition = array($condition);
		}

		if ($condition)
		{
			$condition = (string) new QueryElement('ON', $condition, ' AND ');
		}
		else
		{
			$tableStorage['join'] = 'FROM';
		}

		// Remove too many spaces
		$condition = preg_replace('/\s(?=\s)/', '', $condition);

		$tableStorage['condition'] = trim($condition);
		$tableStorage['prefix'] = $prefix;

		$this->tables[$alias] = $tableStorage;

		return $this;
	}

	/**
	 * removeTable
	 *
	 * @param string $alias
	 *
	 * @return  $this
	 */
	public function removeTable($alias)
	{
		if (!empty($this->tables[$alias]))
		{
			unset($this->tables[$alias]);
		}

		return $this;
	}

	/**
	 * getFilterFields
	 *
	 * @return  array
	 */
	public function getSelectFields()
	{
		$fields = array();

		$i = 0;

		foreach ($this->tables as $alias => $table)
		{
			$columns = $this->db->getTable($table['name'])->getColumns();

			foreach ($columns as $column)
			{
				$prefix = $table['prefix'];

				if ($i === 0)
				{
					$prefix = $prefix === null ? false : true;
				}
				else
				{
					$prefix = $prefix === null ? true : false;
				}

				if ($prefix === true)
				{
					$fields[] = $this->db->quoteName("{$alias}.{$column} AS {$alias}_{$column}");
				}
				else
				{
					$fields[] = $this->db->quoteName("{$alias}.{$column}", $column);
				}
			}

			$i++;
		}

		return $fields;
	}

	/**
	 * registerQueryTables
	 *
	 * @param Query $query
	 *
	 * @return  Query
	 */
	public function registerQueryTables(Query $query)
	{
		foreach ($this->tables as $alias => $table)
		{
			if ($table['join'] == 'FROM')
			{
				$query->from($query->quoteName($table['name']) . ' AS ' . $query->quoteName($alias));
			}
			else
			{
				$query->join(
					$table['join'],
					$query->quoteName($table['name']) . ' AS ' . $query->quoteName($alias) . ' ' . $table['condition']
				);
			}
		}

		return $query;
	}

	/**
	 * buildConditions
	 *
	 * @param Query $query
	 * @param array         $conditions
	 *
	 * @return  Query
	 */
	public static function buildWheres(Query $query, array $conditions)
	{
		foreach ($conditions as $key => $value)
		{
			if (empty($value))
			{
				continue;
			}

			// If using Compare class, we convert it to string.
			if ($value instanceof Compare)
			{
				$query->where((string) static::buildCompare($key, $value, $query));
			}

			// If key is numeric, just send value to query where.
			elseif (is_numeric($key))
			{
				$query->where((string) $value);
			}

			// If is array or object, we use "IN" condition.
			elseif (is_array($value) || is_object($value))
			{
				$value = array_map(array($query, 'quote'), (array) $value);

				$query->where($query->quoteName($key) . new QueryElement('IN ()', $value, ','));
			}

			// Otherwise, we use equal condition.
			else
			{
				$query->where($query->format('%n = %q', $key, $value));
			}
		}

		return $query;
	}

	/**
	 * buildCompare
	 *
	 * @param string|int  $key
	 * @param Compare     $value
	 * @param Query       $query
	 *
	 * @return  string
	 */
	public static function buildCompare($key, Compare $value, $query = null)
	{
		$query = $query ? : DatabaseFactory::getDbo()->getQuery(true);

		if (!is_numeric($key))
		{
			$value->setCompare1($key);
		}

		$value->setHandler(
			function($compare1, $compare2, $operator) use ($query)
			{
				return $query->format('%n ' . $operator . ' %q', $compare1, $compare2);
			}
		);

		return (string) $value;
	}

	/**
	 * getDb
	 *
	 * @return  DatabaseDriver
	 */
	public function getDb()
	{
		if (!$this->db)
		{
			$this->db = DatabaseFactory::getDbo();
		}

		return $this->db;
	}

	/**
	 * setDb
	 *
	 * @param   DatabaseDriver $db
	 *
	 * @return  QueryHelper  Return self to support chaining.
	 */
	public function setDb($db)
	{
		$this->db = $db;

		return $this;
	}
}
