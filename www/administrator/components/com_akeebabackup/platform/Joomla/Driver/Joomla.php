<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Driver;

// Protection against direct access
defined('_JEXEC') || die();

use Exception;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\Mysql\MysqlDriver;
use Joomla\Database\Mysqli\MysqliDriver;
use Joomla\Database\Pdo\PdoDriver;
use Joomla\Database\Pgsql\PgsqlDriver;
use Joomla\Database\Sqlazure\SqlazureDriver;
use Joomla\Database\Sqlite\SqliteDriver;
use Joomla\Database\Sqlsrv\SqlsrvDriver;
use ReflectionObject;
use RuntimeException;

class Joomla
{
	/** @var Base The real database connection object */
	private $dbo;

	/**
	 * Database object constructor
	 *
	 * @param   array  $options  List of options used to configure the connection
	 */
	public function __construct($options = [])
	{
		// Get the database driver *AND* make sure it's connected.
		/** @var DatabaseInterface|null $db */
		$db = \Akeeba\Engine\Platform\Joomla::getDbDriver();

		if (empty($db))
		{
			throw new RuntimeException("Joomla does not return a database driver.");
		}

		$db->connect();

		$options['connection'] = $db->getConnection();

		$driver = $this->getDriverType($db);

		if (empty($driver))
		{
			throw new RuntimeException("Unsupported database driver {$db->getName()}");
		}

		$driver    = '\\Akeeba\\Engine\\Driver\\' . ucfirst($driver);
		$this->dbo = new $driver($options);
	}

	public function close()
	{
		/**
		 * We should not, in fact, try to close the connection by calling the parent method.
		 *
		 * If you close the connection, we ask PHP's mysql / mysqli / pdomysql driver to disconnect the MySQL connection
		 * resource from the database server inside our instance of Akeeba Engine's database driver. However, this
		 * identical resource is also present in Joomla's database driver. Joomla will also try to close the connection
		 * to a now invalid resource, causing a PHP notice to be recorded.
		 *
		 * By setting the connection resource to null in our own driver object we prevent closing the resource,
		 * delegating that responsibility to Joomla. It will gladly do so at the very least automatically, through its
		 * db driver's __destruct.
		 */
		$this->dbo->setConnection(null);
	}

	public function open()
	{
		if (method_exists($this->dbo, 'open'))
		{
			$this->dbo->open();

			return;
		}

		if (method_exists($this->dbo, 'connect'))
		{
			$this->dbo->connect();
		}
	}

	/**
	 * DO NOT REMOVE. THIS METHOD NEEDS TO BE CONCRETE.
	 *
	 * Our workaround for Joomla! 5.1+ is to check if the driver object has a createQuery method. Therefore,
	 * this shim method needs to be concrete instead of falling back to the generic __call() magic method.
	 *
	 * @return Query\Base
	 */
	public function createQuery()
	{
		if (method_exists($this->dbo, 'createQuery'))
		{
			return $this->dbo->createQuery();
		}

		return $this->dbo->getQuery(true);
	}

	/**
	 * Magic method to proxy all calls to the loaded database driver object
	 *
	 * @throws  Exception
	 */
	public function __call($name, array $arguments)
	{
		if (is_null($this->dbo))
		{
			throw new Exception('Akeeba Engine database driver is not loaded');
		}

		if (method_exists($this->dbo, $name) || in_array($name, ['q', 'nq', 'qn']))
		{
			return $this->dbo->{$name}(...$arguments);
		}

		throw new Exception('Method ' . $name . ' not found in Akeeba Platform');
	}

	public function __get($name)
	{
		if (isset($this->dbo->$name) || property_exists($this->dbo, $name))
		{
			return $this->dbo->$name;
		}

		$this->dbo->$name = null;

		user_error('Database driver does not support property ' . $name);

		return null;
	}

	public function __set($name, $value)
	{
		if (isset($this->dbo->name) || property_exists($this->dbo, $name))
		{
			$this->dbo->$name = $value;

			return;
		}

		$this->dbo->$name = null;
		user_error('Database driver not support property ' . $name);
	}

	/**
	 * Get the Akeeba Engine database driver type for the Joomla database object.
	 *
	 * Weak typing of the argument is deliberate. The class hierarchy of the database driver classes may change even
	 * within the same major version of Joomla, as happened in the past with Joomla 3. Having weak typing we can amend
	 * this method to straddle the change, i.e. make it compatible with Joomla versions before and after the change. In
	 * simple terms, it's future–proofing.
	 *
	 * @param   DatabaseInterface|DatabaseDriver  $db
	 *
	 * @return  string|null  The driver type; null if unsupported
	 */
	private function getDriverType($db): ?string
	{
		// Make sure we got an object
		if (!is_object($db))
		{
			return null;
		}

		// Get the Joomla database driver name — assuming the object passed is a DatabaseInterface instance
		if (method_exists($db, 'getName'))
		{
			$jDriverName = $db->getName();
		}
		else
		{
			// On Joomla 4 this is supposed to raise an E_USER_DEPRECATED notice
			$jDriverName = $db->name ?? '';
		}

		// Quick shortcuts to known core Joomla database drivers
		if (in_array($jDriverName, ['mysql', 'pdomysql']))
		{
			return 'pdomysql';
		}
		elseif ($jDriverName === 'mysqli')
		{
			return 'mysqli';
		}
		elseif ($jDriverName === 'pgsql' || $jDriverName === 'postgresql')
		{
			return 'postgresql';
		}
		elseif (
			(stristr($jDriverName, 'postgre') !== false)
			|| (stristr($jDriverName, 'pgsql') !== false)
			|| (stristr($jDriverName, 'oracle') !== false)
			|| (stristr($jDriverName, 'sqlite') !== false)
			|| (stristr($jDriverName, 'sqlsrv') !== false)
			|| (stristr($jDriverName, 'sqlazure') !== false)
			|| (stristr($jDriverName, 'mssql') !== false)
		)
		{
			return null;
		}

		/**
		 * We do not have a driver name known to the core. This is a custom database driver, implemented by a Joomla
		 * extension. This is typically used in two use cases:
		 * - Transparent content translation (JoomFish, Falang, jDiction, ...)
		 * - Support for primary / secondary database servers (primary is read only, secondary is write only)
		 * The custom database drier will be extending one of the core drivers. We will use defensive code to detect
		 * that, making no assumption that the core driver class exists because these classes are an implementation
		 * detail in Joomla which may change over time, even though they are explicitly included in its SemVer promise.
		 * We have been around long enough to know better than believing Joomla won't break SemVer by accident...
		 */
		if (
			(class_exists(MysqlDriver::class) && ($db instanceof MysqlDriver))
			|| (class_exists(Pdomysql::class) && ($db instanceof Pdomysql))
		)
		{
			return 'pdomysql';
		}
		elseif (class_exists(MysqliDriver::class) && ($db instanceof MysqliDriver))
		{
			return 'mysqli';
		}
		elseif (class_exists(PgsqlDriver::class) && ($db instanceof PgsqlDriver))
		{
			return 'postgresql';
		}
		elseif (
			(class_exists(SqliteDriver::class) && ($db instanceof SqliteDriver))
			|| (class_exists(SqlsrvDriver::class) && ($db instanceof SqlsrvDriver))
			|| (class_exists(SqlazureDriver::class) && ($db instanceof SqlazureDriver))
		)
		{
			return null;
		}

		// We still have no idea. We will need to use reflection. If it's unavailable we give up.
		if (!class_exists(ReflectionObject::class))
		{
			return null;
		}

		$refDriver = new ReflectionObject($db);

		// Is this a generic PDO driver instance?
		if ((class_exists(PdoDriver::class) && ($db instanceof PdoDriver)) && $refDriver->hasProperty('options'))
		{
			$refOptions = $refDriver->getProperty('options');

			if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
			{
				$refOptions->setAccessible(true);
			}

			$options = $refOptions->getValue($db);
			$options = is_array($options) ? $options : [];

			$pdoDriver = $options['driver'] ?? 'odbc';

			switch ($pdoDriver)
			{
				// PDO MySQL. We support this!
				case 'mysql':
					return 'pdomysql';

				// PDO PostgreSQL. We support this!
				case 'pgsql':
				case 'postgresql':
					return 'postgresql';

				// ODBC: I need to inspect the DSN
				case 'obdc':
					$dsn = $options['dsn'] ?? '';

					// No DSN? No joy.
					if (empty($dsn))
					{
						return null;
					}

					// That's MySQL over ODBC over PDO. OK, rather strained but we can do that.
					if (stripos($dsn, 'mysql:') === 0)
					{
						return 'pdomysql';
					}

					// That's MySQL over ODBC over PDO. OK, rather strained but we can do that.
					if (stripos($dsn, 'pgsql:') === 0 || stripos($dsn, 'postgresql:') === 0)
					{
						return 'postgresql';
					}

					// Anything else: tough luck.
					return null;

				// Anything else: tough luck.
				default:
					return null;
			}
		}

		// Let's get the class hierarchy and see if we have anything that looks like MySQL / PostgreSQL in its name.
		$classNames = class_parents($db);
		array_unshift($classNames, get_class($db));

		$isMySQLi = array_reduce(
			$classNames, function (bool $carry, string $className) {
			return $carry || (stripos($className, 'mysqli') !== false);
		}, false
		);

		if ($isMySQLi)
		{
			return 'mysqli';
		}

		$isPdoMySQL = array_reduce(
			$classNames, function (bool $carry, string $className) {
			return $carry || (stripos($className, 'pdomysql') !== false);
		}, false
		);

		if ($isPdoMySQL)
		{
			return 'pdomysql';
		}

		$isPdoPostgreSQL = array_reduce(
			$classNames,
			function (bool $carry, string $className) {
				return $carry
				       || (stripos($className, 'postgresql') !== false)
				       || (stripos($className, 'pgsql') !== false);
			},
			false
		);

		if ($isPdoPostgreSQL)
		{
			return 'postgresql';
		}

		// All possible checks failed. I have no idea what you're doing here, mate.
		return null;
	}
}
