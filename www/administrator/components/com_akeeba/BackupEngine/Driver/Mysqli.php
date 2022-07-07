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

use Akeeba\Engine\Driver\Query\Mysqli as QueryMysqli;
use Akeeba\Engine\FixMySQLHostname;
use mysqli_result;
use RuntimeException;

/**
 * MySQL Improved (mysqli) database driver for Akeeba Engine
 *
 * Based on Joomla! Platform 11.2
 */
class Mysqli extends Mysql
{
	use FixMySQLHostname;

	/**
	 * The name of the database driver.
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $name = 'mysqli';

	/** @var \mysqli|null The db connection resource */
	protected $connection = '';

	/** @var mysqli_result|null The database connection cursor from the last query. */
	protected $cursor;

	protected $port;

	protected $socket;

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

		// Set the information
		$this->host           = $options['host'] ?? 'localhost';
		$this->user           = $options['user'] ?? '';
		$this->password       = $options['password'] ?? '';
		$this->port           = $options['port'] ?? '';
		$this->socket         = $options['socket'] ?? '';
		$this->_database      = $options['database'] ?? '';
		$this->selectDatabase = $options['select'] ?? true;
		$this->ssl            = $options['ssl'] ?? [];

		// Finalize initialization. Also opens the connection.
		parent::__construct($options);
	}

	/**
	 * Test to see if the MySQL connector is available.
	 *
	 * @return  boolean  True on success, false otherwise.
	 */
	public static function isSupported()
	{
		return (function_exists('mysqli_connect'));
	}

	public function close()
	{
		$return = false;

		if (is_object($this->cursor) && ($this->cursor instanceof mysqli_result))
		{
			try
			{
				@$this->cursor->free();
			}
			catch (\Throwable $e)
			{
			}

			$this->cursor = null;
		}

		if (is_object($this->connection) && ($this->connection instanceof \mysqli))
		{
			try
			{
				$return = @$this->connection->close();
			}
			catch (\Throwable $e)
			{
				$return = false;
			}
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
		if (is_object($this->connection))
		{
			return @mysqli_ping($this->connection);
		}

		return false;
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
		if (is_null($text))
		{
			return 'NULL';
		}

		$result = @mysqli_real_escape_string($this->getConnection(), $text);

		if ($result === false)
		{
			// Attempt to reconnect.
			try
			{
				$this->connection = null;
				$this->open();

				$result = @mysqli_real_escape_string($this->getConnection(), $text);;
			}
			catch (RuntimeException $e)
			{
				$result = $this->unsafe_escape($text);
			}
		}

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
		return mysqli_fetch_assoc($cursor ?: $this->cursor);
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
		mysqli_free_result($cursor ?: $this->cursor);
	}

	/**
	 * Get the number of affected rows for the previous executed SQL statement.
	 *
	 * @return  integer  The number of affected rows.
	 */
	public function getAffectedRows()
	{
		return mysqli_affected_rows($this->connection);
	}

	/**
	 * Get the number of returned rows for the previous executed SQL statement.
	 *
	 * @param   mysqli_result  $cursor  An optional database cursor resource to extract the row count from.
	 *
	 * @return  integer   The number of returned rows.
	 */
	public function getNumRows($cursor = null)
	{
		return mysqli_num_rows($cursor ?: $this->cursor);
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
			return new QueryMysqli($this);
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
		return mysqli_get_server_info($this->connection);
	}

	/**
	 * Determines if the database engine supports UTF-8 character encoding.
	 *
	 * @return  boolean  True if supported.
	 */
	public function hasUTF()
	{
		$mariadb = stripos($this->connection->server_info, 'mariadb') !== false;
		$client_version = mysqli_get_client_info();
		$server_version = $this->getVersion();

		if (version_compare($server_version, '5.5.3', '<'))
		{
			return false;
		}

		if ($mariadb && version_compare($server_version, '10.0.0', '<'))
		{
			return false;
		}

		if (strpos($client_version, 'mysqlnd') !== false)
		{
			$client_version = preg_replace('/^\D+([\d.]+).*/', '$1', $client_version);

			return version_compare($client_version, '5.0.9', '>=');
		}

		return version_compare($client_version, '5.5.3', '>=');
	}

	/**
	 * Method to get the auto-incremented value from the last INSERT statement.
	 *
	 * @return  integer  The value of the auto-increment field from the last inserted row.
	 */
	public function insertid()
	{
		return mysqli_insert_id($this->connection);
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

		// perform a number of fatality checks, then return gracefully
		if (!function_exists('mysqli_connect'))
		{
			$this->errorNum = 1;
			$this->errorMsg = 'The MySQL adapter "mysqli" is not available.';

			return;
		}

		// Let's prepare a connection
		$this->connection = mysqli_init();

		$connectionFlags = 0;

		// For SSL/TLS connection encryption.
		if ($this->ssl !== [] && $this->ssl['enable'] === true)
		{
			$connectionFlags = $connectionFlags | MYSQLI_CLIENT_SSL;

			// Verify server certificate is only available in PHP 5.6.16+. See https://www.php.net/ChangeLog-5.php#5.6.16
			if (isset($this->ssl['verify_server_cert']))
			{
				// New constants in PHP 5.6.16+. See https://www.php.net/ChangeLog-5.php#5.6.16
				if ($this->ssl['verify_server_cert'] === true && defined('MYSQLI_CLIENT_SSL_VERIFY_SERVER_CERT'))
				{
					$connectionFlags = $connectionFlags | MYSQLI_CLIENT_SSL_VERIFY_SERVER_CERT;
				}
				elseif ($this->ssl['verify_server_cert'] === false && defined('MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT'))
				{
					$connectionFlags = $connectionFlags | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
				}
				elseif (defined('MYSQLI_OPT_SSL_VERIFY_SERVER_CERT'))
				{
					$this->connection->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, $this->ssl['verify_server_cert']);
				}
			}

			// Add SSL/TLS options only if changed.
			$this->connection->ssl_set(
				($this->ssl['key'] ?? null) ?: null,
				($this->ssl['cert'] ?? null) ?: null,
				($this->ssl['ca'] ?? null) ?: null,
				($this->ssl['capath'] ?? null) ?: null,
				($this->ssl['cipher'] ?? null) ?: null
			);
		}

		// Attempt to connect to the server, use error suppression to silence warnings and allow us to throw an Exception separately.
		try
		{
			$connected = @$this->connection->real_connect(
				$this->host,
				$this->user,
				$this->password ?: null,
				null,
				$this->port ?: 3306,
				$this->socket ?: null,
				$connectionFlags
			);
		}
		catch (\Throwable $e)
		{
			$connected = false;
		}

		// connect to the server
		if (!$connected)
		{
			$this->errorNum = 2;
			$this->errorMsg = 'Could not connect to MySQL';

			return;
		}

		// Set sql_mode to non_strict mode
		mysqli_query($this->connection, "SET @@SESSION.sql_mode = '';");

		if ($this->selectDatabase && !empty($this->_database))
		{
			if (!$this->select($this->_database))
			{
				$this->errorNum = 3;
				$this->errorMsg = "Cannot select database {$this->_database}";

				return;
			}
		}

		$this->setUTF();
	}

	/**
	 * Execute the SQL statement.
	 *
	 * @return  mixed  A database cursor resource on success, boolean false on failure.
	 */
	public function query()
	{
		$this->open();

		if (!is_object($this->connection))
		{
			throw new RuntimeException($this->errorMsg, $this->errorNum);
		}

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
		$this->cursor = @mysqli_query($this->connection, $query);

		// If an error occurred handle it.
		if (!$this->cursor)
		{
			$this->errorNum = 0;
			$this->errorMsg = '';

			if ($this->connection)
			{
				$this->errorNum = (int) @mysqli_errno($this->connection);
				$this->errorMsg = (string) @mysqli_error($this->connection) . ' SQL=' . $query;
			}

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
			elseif ($this->errorNum != 0)
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
		if (!$database)
		{
			return false;
		}

		if (!mysqli_select_db($this->connection, $database))
		{
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
		$result = false;

		if ($this->supportsUtf8mb4())
		{
			$result = @mysqli_set_charset($this->connection, 'utf8mb4');
		}

		if (!$result)
		{
			$result = @mysqli_set_charset($this->connection, 'utf8');
		}

		return $result;

	}

	/**
	 * Does this database server support UTF-8 four byte (utf8mb4) collation?
	 *
	 * libmysql supports utf8mb4 since 5.5.3 (same version as the MySQL server). mysqlnd supports utf8mb4 since 5.0.9.
	 *
	 * This method's code is based on WordPress' wpdb::has_cap() method
	 *
	 * @return  bool
	 */
	public function supportsUtf8mb4()
	{
		$client_version = mysqli_get_client_info();

		if (strpos($client_version, 'mysqlnd') !== false)
		{
			$client_version = preg_replace('/^\D+([\d.]+).*/', '$1', $client_version);

			return version_compare($client_version, '5.0.9', '>=');
		}
		else
		{
			return version_compare($client_version, '5.5.3', '>=');
		}
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
		return mysqli_fetch_row($cursor ?: $this->cursor);
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
		return mysqli_fetch_object($cursor ?: $this->cursor, $class);
	}
}
