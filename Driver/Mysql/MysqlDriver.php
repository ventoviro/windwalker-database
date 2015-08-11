<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Database\Driver\Mysql;

use Windwalker\Database\Driver\Pdo\PdoDriver;

/**
 * Class MysqlDriver
 *
 * @since 2.0
 */
class MysqlDriver extends PdoDriver
{
	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'mysql';

	/**
	 * Is this driver supported.
	 *
	 * @return  boolean
	 */
	public static function isSupported()
	{
		return in_array('mysql', \PDO::getAvailableDrivers());
	}

	/**
	 * Constructor.
	 *
	 * @param   \PDO  $connection The pdo connection object.
	 * @param   array $options    List of options used to configure the connection
	 *
	 * @since   2.0
	 */
	public function __construct(\PDO $connection = null, $options = array())
	{
		$options['driver'] = 'mysql';
		$options['charset'] = (isset($options['charset'])) ? $options['charset'] : 'utf8';

		parent::__construct($connection, $options);
	}
}

