<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2008 - 2014 Asikart.com. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Database\Driver\Mysql;

use Windwalker\Database\Driver\Pdo\PdoTransaction;

/**
 * Class MysqlTransaction
 *
 * @since {DEPLOY_VERSION}
 */
class MysqlTransaction extends PdoTransaction
{
	/**
	 * start
	 *
	 * @return  static
	 */
	public function start()
	{
		if (!$this->nested || !$this->depth)
		{
			parent::start();
		}
		else
		{
			$savepoint = 'SP_' . $this->depth;
			$this->db->setQuery('SAVEPOINT ' . $this->db->quoteName($savepoint));

			if ($this->db->execute())
			{
				$this->depth++;
			}
		}

		return $this;
	}

	/**
	 * commit
	 *
	 * @return  static
	 */
	public function commit()
	{
		if (!$this->nested || $this->depth <= 1)
		{
			parent::commit();
		}
		else
		{
			$this->depth--;
		}

		return $this;
	}

	/**
	 * rollback
	 *
	 * @return  static
	 */
	public function rollback()
	{
		if (!$this->nested || $this->depth <= 1)
		{
			parent::rollback();
		}
		else
		{
			$savepoint = 'SP_' . ($this->depth - 1);
			$this->db->setQuery('ROLLBACK TO SAVEPOINT ' . $this->db->quoteName($savepoint));

			if ($this->db->execute())
			{
				$this->depth--;
			}
		}

		return $this;
	}
}

