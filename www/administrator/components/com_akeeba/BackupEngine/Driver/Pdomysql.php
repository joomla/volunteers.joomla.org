<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Driver;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Driver\Query\Pdomysql as QueryPdomysql;
use Akeeba\Engine\FixMySQLHostname;
use Exception;
use PDO;
use PDOException;
use PDOStatement;
use ReflectionClass;
use RuntimeException;

/**
 * PDO MySQL database driver for Akeeba Engine
 *
 * Based on Joomla! Platform 12.1
 */
class Pdomysql extends Mysql
{
	use FixMySQLHostname;

	/**
	 * The default cipher suite for TLS connections.
	 *
	 * @var    array
	 */
	protected static $defaultCipherSuite = [
		'AES128-GCM-SHA256',
		'AES256-GCM-SHA384',
		'AES128-CBC-SHA256',
		'AES256-CBC-SHA384',
		'DES-CBC3-SHA',
	];

	/**
	 * The name of the database driver.
	 *
	 * @var    string
	 */
	public $name = 'pdomysql';

	/** @var string Connection character set */
	protected $charset = 'utf8mb4';

	/** @var PDO The db connection resource */
	protected $connection = null;

	/** @var PDOStatement The database connection cursor from the last query. */
	protected $cursor;

	/** @var array Driver options for PDO */
	protected $driverOptions = [];

	protected $ssl = [];

	/** @var bool Are we in the process of reconnecting to the database server? */
	private $isReconnecting = false;

	/**
	 * Database object constructor
	 *
	 * @param   array  $options  List of options used to configure the connection
	 */
	public function __construct($options)
	{
		$this->driverType = 'mysql';

		// Init
		$this->nameQuote = '`';

		$options['ssl'] = $options['ssl'] ?? [];
		$options['ssl'] = is_array($options['ssl']) ? $options['ssl'] : [];

		$options['ssl']['enable']             = ($options['ssl']['enable'] ?? $options['dbencryption'] ?? false) ?: false;
		$options['ssl']['cipher']             = ($options['ssl']['cipher'] ?? $options['dbsslcipher'] ?? null) ?: null;
		$options['ssl']['ca']                 = ($options['ssl']['ca'] ?? $options['dbsslca'] ?? null) ?: null;
		$options['ssl']['capath']             = ($options['ssl']['capath'] ?? $options['dbsslcapath'] ?? null) ?: null;
		$options['ssl']['key']                = ($options['ssl']['key'] ?? $options['dbsslkey'] ?? null) ?: null;
		$options['ssl']['cert']               = ($options['ssl']['cert'] ?? $options['dbsslcert'] ?? null) ?: null;
		$options['ssl']['verify_server_cert'] = ($options['ssl']['verify_server_cert'] ?? $options['dbsslverifyservercert'] ?? false) ?: false;

		// Figure out if a port is included in the host name
		$this->fixHostnamePortSocket($options['host'], $options['port'], $options['socket']);

		// Open the connection
		$this->host           = $options['host'] ?? 'localhost';
		$this->user           = $options['user'] ?? '';
		$this->password       = $options['password'] ?? '';
		$this->port           = $options['port'] ?? '';
		$this->socket         = $options['socket'] ?? '';
		$this->_database      = $options['database'] ?? '';
		$this->selectDatabase = $options['select'] ?? true;
		$this->ssl            = $options['ssl'] ?? [];

		$this->charset       = $options['charset'] ?? 'utf8mb4';
		$this->driverOptions = $options['driverOptions'] ?? [];
		$this->tablePrefix   = $options['prefix'] ?? '';
		$this->connection    = $options['connection'] ?? null;
		$this->errorNum      = 0;
		$this->count         = 0;
		$this->log           = [];
		$this->options       = $options;

		if (!is_object($this->connection))
		{
			$this->open();
		}
	}

	/**
	 * Test to see if the MySQL connector is available.
	 *
	 * @return  boolean  True on success, false otherwise.
	 */
	public static function isSupported()
	{
		if (!defined('\PDO::ATTR_DRIVER_NAME'))
		{
			return false;
		}

		return in_array('mysql', PDO::getAvailableDrivers());
	}

	/**
	 * PDO does not support serialize
	 *
	 * @return  array
	 */
	public function __sleep()
	{
		$serializedProperties = [];

		$reflect = new ReflectionClass($this);

		// Get properties of the current class
		$properties = $reflect->getProperties();

		foreach ($properties as $property)
		{
			// Do not serialize properties that are \PDO
			if ($property->isStatic() == false && !($this->{$property->name} instanceof PDO))
			{
				array_push($serializedProperties, $property->name);
			}
		}

		return $serializedProperties;
	}

	/**
	 * Wake up after serialization
	 *
	 * @return  array
	 */
	public function __wakeup()
	{
		// Get connection back
		$this->__construct($this->options);
	}

	public function close()
	{
		$return = false;

		if (is_object($this->cursor))
		{
			$this->cursor->closeCursor();
		}

		$this->connection = null;

		return $return;
	}

	/**
	 * Determines if the connection to the server is active.
	 *
	 * @return  boolean  True if connected to the database engine.
	 */
	public function connected()
	{
		if (!is_object($this->connection))
		{
			return false;
		}

		try
		{
			/** @var PDOStatement $statement */
			$statement = $this->connection->prepare('SELECT 1');
			$executed  = $statement->execute();
			$ret       = 0;

			if ($executed)
			{
				$row = [0];

				if (!empty($statement) && $statement instanceof PDOStatement)
				{
					$row = $statement->fetch(PDO::FETCH_NUM);
				}

				$ret = $row[0];
			}

			$status = $ret == 1;

			$statement->closeCursor();
			$statement = null;
		}
			// If we catch an exception here, we must not be connected.
		catch (Exception $e)
		{
			$status = false;
		}

		return $status;
	}

	/**
	 * Method to escape a string for usage in an SQL statement.
	 *
	 * @param   string   $text   The string to be escaped.
	 * @param   boolean  $extra  Optional parameter to provide extra escaping.
	 *
	 * @return  string  The escaped string.
	 */
	public function escape($text, $extra = false)
	{
		if (is_int($text) || is_float($text))
		{
			return $text;
		}

		if (is_null($text))
		{
			return 'NULL';
		}

		$result = substr($this->connection->quote($text), 1, -1);

		if ($extra)
		{
			$result = addcslashes($result, '%_');
		}

		return $result;
	}

	/**
	 * Method to fetch a row from the result set cursor as an associative array.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  mixed  Either the next row from the result set or false if there are no more rows.
	 */
	public function fetchAssoc($cursor = null)
	{
		$ret = null;

		if (!empty($cursor) && $cursor instanceof PDOStatement)
		{
			$ret = $cursor->fetch(PDO::FETCH_ASSOC);
		}
		elseif ($this->cursor instanceof PDOStatement)
		{
			$ret = $this->cursor->fetch(PDO::FETCH_ASSOC);
		}

		return $ret;
	}

	/**
	 * Method to free up the memory used for the result set.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  void
	 */
	public function freeResult($cursor = null)
	{
		if ($cursor instanceof PDOStatement)
		{
			$cursor->closeCursor();
			$cursor = null;
		}

		if ($this->cursor instanceof PDOStatement)
		{
			$this->cursor->closeCursor();
			$this->cursor = null;
		}
	}

	/**
	 * Get the number of affected rows for the previous executed SQL statement.
	 *
	 * @return  integer  The number of affected rows.
	 */
	public function getAffectedRows()
	{
		if ($this->cursor instanceof PDOStatement)
		{
			return $this->cursor->rowCount();
		}

		return 0;
	}

	/**
	 * Get the number of returned rows for the previous executed SQL statement.
	 *
	 * @param   resource  $cursor  An optional database cursor resource to extract the row count from.
	 *
	 * @return  integer   The number of returned rows.
	 */
	public function getNumRows($cursor = null)
	{
		if ($cursor instanceof PDOStatement)
		{
			return $cursor->rowCount();
		}

		if ($this->cursor instanceof PDOStatement)
		{
			return $this->cursor->rowCount();
		}

		return 0;
	}

	/**
	 * Get the current or query, or new JDatabaseQuery object.
	 *
	 * @param   boolean  $new  False to return the last query set, True to return a new JDatabaseQuery object.
	 *
	 * @return  mixed  The current value of the internal SQL variable or a new JDatabaseQuery object.
	 */
	public function getQuery($new = false)
	{
		if ($new)
		{
			return new QueryPdomysql($this);
		}
		else
		{
			return $this->sql;
		}
	}

	/**
	 * Get the version of the database connector.
	 *
	 * @return  string  The database connector version.
	 */
	public function getVersion()
	{
		$version = $this->connection->getAttribute(\PDO::ATTR_SERVER_VERSION);

		if (stripos($version, 'mariadb') !== false)
		{
			// MariaDB: Strip off any leading '5.5.5-', if present
			return preg_replace('/^5\.5\.5-/', '', $version);
		}

		return $version;
	}

	/**
	 * Determines if the database engine supports UTF-8 character encoding.
	 *
	 * @return  boolean  True if supported.
	 */
	public function hasUTF()
	{
		$serverVersion = $this->getVersion();
		$mariadb       = stripos($serverVersion, 'mariadb') !== false;

		// At this point we know the client supports utf8mb4.  Now we must check if the server supports utf8mb4 as well.
		$utf8mb4 = version_compare($serverVersion, '5.5.3', '>=');

		if ($mariadb && version_compare($serverVersion, '10.0.0', '<'))
		{
			$utf8mb4 = false;
		}

		return $utf8mb4;
	}

	/**
	 * Method to get the auto-incremented value from the last INSERT statement.
	 *
	 * @return  integer  The value of the auto-increment field from the last inserted row.
	 */
	public function insertid()
	{
		// Error suppress this to prevent PDO warning us that the driver doesn't support this operation.
		return @$this->connection->lastInsertId();
	}

	/**
	 * Method to get the next row in the result set from the database query as an object.
	 *
	 * @param   string  $class  The class name to use for the returned row object.
	 *
	 * @return  mixed   The result of the query as an array, false if there are no more rows.
	 */
	public function loadNextObject($class = 'stdClass')
	{
		// Execute the query and get the result set cursor.
		if (!$this->cursor)
		{
			if (!($this->execute()))
			{
				return $this->errorNum ? null : false;
			}
		}

		// Get the next row from the result set as an object of type $class.
		if ($row = $this->fetchObject(null, $class))
		{
			return $row;
		}

		// Free up system resources and return.
		$this->freeResult();

		return false;
	}

	/**
	 * Method to get the next row in the result set from the database query as an array.
	 *
	 * @return  mixed  The result of the query as an array, false if there are no more rows.
	 */
	public function loadNextRow()
	{
		// Execute the query and get the result set cursor.
		if (!$this->cursor)
		{
			if (!($this->execute()))
			{
				return $this->errorNum ? null : false;
			}
		}

		// Get the next row from the result set as an object of type $class.
		if ($row = $this->fetchArray())
		{
			return $row;
		}

		// Free up system resources and return.
		$this->freeResult();

		return false;
	}

	public function open()
	{
		if ($this->connected())
		{
			return;
		}
		else
		{
			$this->close();
		}

		if (!isset($this->charset))
		{
			$this->charset = 'utf8mb4';
		}

		$this->port = $this->port ?: 3306;

		$format = 'mysql:host=#HOST#;port=#PORT#;dbname=#DBNAME#;charset=#CHARSET#';

		if ($this->socket)
		{
			$format = 'mysql:socket=#SOCKET#;dbname=#DBNAME#;charset=#CHARSET#';
		}

		$replace = ['#HOST#', '#PORT#', '#SOCKET#', '#DBNAME#', '#CHARSET#'];
		$with    = [$this->host, $this->port, $this->socket, $this->_database, $this->charset];

		// Create the connection string:
		$connectionString = str_replace($replace, $with, $format);

		// For SSL/TLS connection encryption.
		if ($this->ssl !== [] && $this->ssl['enable'] === true)
		{
			$sslContextIsNull = true;

			// If customised, add cipher suite, ca file path, ca path, private key file path and certificate file path to PDO driver options.
			foreach (['cipher', 'ca', 'capath', 'key', 'cert'] as $key => $value)
			{
				if ($this->ssl[$value] !== null)
				{
					$this->driverOptions[constant('\PDO::MYSQL_ATTR_SSL_' . strtoupper($value))] = $this->ssl[$value];

					$sslContextIsNull = false;
				}
			}

			// PDO, if no cipher, ca, capath, cert and key are set, can't start TLS one-way connection, set a common ciphers suite to force it.
			if ($sslContextIsNull === true)
			{
				$this->driverOptions[\PDO::MYSQL_ATTR_SSL_CIPHER] = implode(':', static::$defaultCipherSuite);
			}

			// If customised, for capable systems (PHP 7.0.14+ and 7.1.4+) verify certificate chain and Common Name to driver options.
			if ($this->ssl['verify_server_cert'] !== null && defined('\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT'))
			{
				$this->driverOptions[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = $this->ssl['verify_server_cert'];
			}
		}

		// connect to the server
		try
		{
			$this->connection = new PDO(
				$connectionString,
				$this->user,
				$this->password,
				$this->driverOptions
			);
		}
		catch (PDOException $e)
		{
			// If we tried connecting through utf8mb4 and we failed let's retry with regular utf8
			if ($this->charset == 'utf8mb4')
			{
				$this->charset = 'UTF8';
				$this->open();

				return;
			}

			$this->errorNum = 2;
			$this->errorMsg = 'Could not connect to MySQL via PDO: ' . $e->getMessage();

			return;
		}

		// Reset the SQL mode of the connection
		try
		{
			$this->connection->exec("SET @@SESSION.sql_mode = '';");
		}
			// Ignore any exceptions (incompatible MySQL versions)
		catch (Exception $e)
		{
		}

		$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

		if ($this->selectDatabase && !empty($this->_database))
		{
			$this->select($this->_database);
		}

		$this->freeResult();
	}

	/**
	 * Execute the SQL statement.
	 *
	 * @return  mixed  A database cursor resource on success, boolean false on failure.
	 */
	public function query()
	{
		if (!is_object($this->connection))
		{
			$this->open();
		}

		$this->freeResult();

		// Take a local copy so that we don't modify the original query and cause issues later
		$query = $this->replacePrefix((string) $this->sql);

		if ($this->limit > 0 || $this->offset > 0)
		{
			$query .= ' LIMIT ' . $this->offset . ', ' . $this->limit;
		}

		// Increment the query counter.
		$this->count++;

		// If debugging is enabled then let's log the query.
		if ($this->debug)
		{
			// Add the query to the object queue.
			$this->log[] = $query;
		}

		// Reset the error values.
		$this->errorNum = 0;
		$this->errorMsg = '';

		// Execute the query. Error suppression is used here to prevent warnings/notices that the connection has been lost.
		try
		{
			$this->cursor = $this->connection->query($query);
		}
		catch (Exception $e)
		{
		}

		// If an error occurred handle it.
		if (!$this->cursor)
		{
			$errorInfo      = $this->connection->errorInfo();
			$this->errorNum = $errorInfo[1];
			$this->errorMsg = $errorInfo[2] . ' SQL=' . $query;

			// Check if the server was disconnected.
			if (!$this->connected() && !$this->isReconnecting)
			{
				$this->isReconnecting = true;

				try
				{
					// Attempt to reconnect.
					$this->connection = null;
					$this->open();
				}
					// If connect fails, ignore that exception and throw the normal exception.
				catch (RuntimeException $e)
				{
					throw new RuntimeException($this->errorMsg, $this->errorNum);
				}

				// Since we were able to reconnect, run the query again.
				$result               = $this->query();
				$this->isReconnecting = false;

				return $result;
			}
			// The server was not disconnected.
			else
			{
				throw new RuntimeException($this->errorMsg, $this->errorNum);
			}
		}

		return $this->cursor;
	}

	/**
	 * Select a database for use.
	 *
	 * @param   string  $database  The name of the database to select for use.
	 *
	 * @return  boolean  True if the database was successfully selected.
	 */
	public function select($database)
	{
		try
		{
			$this->connection->exec('USE ' . $this->quoteName($database));
		}
		catch (Exception $e)
		{
			$errorInfo      = $this->connection->errorInfo();
			$this->errorNum = $errorInfo[1];
			$this->errorMsg = $errorInfo[2];

			return false;
		}

		return true;
	}

	/**
	 * Set the connection to use UTF-8 character encoding.
	 *
	 * @return  boolean  True on success.
	 */
	public function setUTF()
	{
		return true;
	}

	/**
	 * Method to commit a transaction.
	 *
	 * @return  void
	 */
	public function transactionCommit()
	{
		$this->connection->commit();
	}

	/**
	 * Method to roll back a transaction.
	 *
	 * @return  void
	 */
	public function transactionRollback()
	{
		$this->connection->rollBack();
	}

	/**
	 * Method to initialize a transaction.
	 *
	 * @return  void
	 */
	public function transactionStart()
	{
		$this->connection->beginTransaction();
	}

	/**
	 * Method to fetch a row from the result set cursor as an array.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  mixed  Either the next row from the result set or false if there are no more rows.
	 */
	protected function fetchArray($cursor = null)
	{
		$ret = null;

		if (!empty($cursor) && $cursor instanceof PDOStatement)
		{
			$ret = $cursor->fetch(PDO::FETCH_NUM);
		}
		elseif ($this->cursor instanceof PDOStatement)
		{
			$ret = $this->cursor->fetch(PDO::FETCH_NUM);
		}

		return $ret;
	}

	/**
	 * Method to fetch a row from the result set cursor as an object.
	 *
	 * @param   mixed   $cursor  The optional result set cursor from which to fetch the row.
	 * @param   string  $class   The class name to use for the returned row object.
	 *
	 * @return  mixed   Either the next row from the result set or false if there are no more rows.
	 */
	protected function fetchObject($cursor = null, $class = 'stdClass')
	{
		$ret = null;

		if (!empty($cursor) && $cursor instanceof PDOStatement)
		{
			$ret = $cursor->fetchObject($class);
		}
		elseif ($this->cursor instanceof PDOStatement)
		{
			$ret = $this->cursor->fetchObject($class);
		}

		return $ret;
	}
}
