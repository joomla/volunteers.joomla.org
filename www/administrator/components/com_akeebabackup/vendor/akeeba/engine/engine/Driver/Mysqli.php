<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Driver;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Driver\Query\Mysqli as QueryMysqli;
use Akeeba\Engine\Factory;
use Akeeba\Engine\FixMySQLHostname;
use Exception;
use mysqli_result;
use RuntimeException;

/**
 * MySQL Improved (mysqli) database driver for Akeeba Engine
 *
 * Based on Joomla! Platform 11.2
 */
#[\AllowDynamicProperties]
class Mysqli extends Base
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

	/** @var string Hostname */
	protected $host;

	/**
	 * The character(s) used to quote SQL statement names such as table names or field names,
	 * etc.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $nameQuote = '`';

	/**
	 * The null or zero representation of a timestamp for the database driver.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $nullDate = '0000-00-00 00:00:00';

	/** @var string Password */
	protected $password;

	protected $port;

	/** @var bool Should I select a database? */
	protected $selectDatabase;

	protected $socket;

	protected $ssl = [];

	/** @var string Username */
	protected $user;

	/** @var bool Are we in the process of reconnecting to the database server? */
	private $isReconnecting = false;

	/** @var array|null A cache of the tables contained in the currently connected database */
	public $tablesCache = null;

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

		// Finalize initialization.
		parent::__construct($options);

		// Open the connection.
		$this->open();
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
		if (!is_object($this->connection))
		{
			return false;
		}

		// mysqli_ping is deprecated since PHP 8.4.
		if (version_compare(PHP_VERSION, '8.4.0', '<'))
		{
			try
			{
				return @mysqli_ping($this->connection);
			}
			catch (\Throwable $e)
			{
				return false;
			}
		}

		try
		{
			$cursor = @mysqli_query($this->connection, 'SELECT 1');

			if (!$cursor)
			{
				return false;
			}

			mysqli_free_result($cursor);

			return true;
		}
		catch (\Throwable $e)
		{
			return false;
		}
	}

	/**
	 * Drops a table from the database.
	 *
	 * @param   string   $tableName  The name of the database table to drop.
	 * @param   boolean  $ifExists   Optionally specify that the table must exist before it is dropped.
	 *
	 * @return  Mysqli  Returns this object to support chaining.
	 */
	public function dropTable($tableName, $ifExists = true)
	{
		$query = $this->getQuery(true);

		$this->setQuery('DROP TABLE ' . ($ifExists ? 'IF EXISTS ' : '') . $query->quoteName($tableName));

		$this->query();

		return $this;
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

		if (is_int($text))
		{
			return (string) $text;
		}

		if (is_float($text))
		{
			return $this->floatToSqlString($text);
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
	 * Method to get the database collation in use by sampling a text field of a table in the database.
	 *
	 * @return  mixed  The collation in use by the database (string) or boolean false if not supported.
	 */
	public function getCollation()
	{
		$this->setQuery('SHOW FULL COLUMNS FROM #__ak_stats');
		$array = $this->loadAssocList();

		return $array['2']['Collation'];
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

	public function createQuery()
	{
		return new QueryMysqli($this);
	}

	/**
	 * Retrieves field information about a given table.
	 *
	 * @param   string   $table     The name of the database table.
	 * @param   boolean  $typeOnly  True to only return field types.
	 *
	 * @return  array  An array of fields for the database table.
	 */
	public function getTableColumns($table, $typeOnly = true)
	{
		$result = [];

		// Set the query to get the table fields statement.
		$this->setQuery('SHOW FULL COLUMNS FROM ' . $this->quoteName($this->escape($table)));
		$fields = $this->loadObjectList();

		// If we only want the type as the value add just that to the list.
		if ($typeOnly)
		{
			foreach ($fields as $field)
			{
				$result[$field->Field] = preg_replace("/[(0-9)]/", '', $field->Type);
			}
		}
		// If we want the whole field data object add that to the list.
		else
		{
			foreach ($fields as $field)
			{
				$result[$field->Field] = $field;
			}
		}

		return $result;
	}

	/**
	 * Shows the table CREATE statement that creates the given tables.
	 *
	 * @param   mixed  $tables  A table name or a list of table names.
	 *
	 * @return  array  A list of the create SQL for the tables.
	 */
	public function getTableCreate($tables)
	{
		// Initialise variables.
		$result = [];

		// Sanitize input to an array and iterate over the list.
		$tables = (array) $tables;
		foreach ($tables as $table)
		{
			// Set the query to get the table CREATE statement.
			$this->setQuery('SHOW CREATE table ' . $this->quoteName($this->escape($table)));
			$row = $this->loadRow();

			// Populate the result array based on the create statements.
			$result[$table] = $row[1];
		}

		return $result;
	}

	/**
	 * Get the details list of keys for a table.
	 *
	 * @param   string  $table  The name of the table.
	 *
	 * @return  array  An array of the column specification for the table.
	 */
	public function getTableKeys($table)
	{
		// Get the details columns information.
		$this->setQuery('SHOW KEYS FROM ' . $this->quoteName($table));
		$keys = $this->loadObjectList();

		return $keys;
	}

	/**
	 * Method to get an array of all tables in the database.
	 *
	 * @return  array  An array of all the tables in the database.
	 */
	public function getTableList()
	{
		// Set the query to get the tables statement.
		$this->setQuery('SHOW TABLES');
		$tables = $this->loadColumn();

		return $tables;
	}

	/**
	 * Returns an array with the names of tables, views, procedures, functions and triggers
	 * in the database. The table names are the keys of the tables, whereas the value is
	 * the type of each element: table, view, merge, temp, procedure, function or trigger.
	 * Note that merge are MRG_MYISAM tables and temp is non-permanent data table, usually
	 * set up as temporary, black hole or federated tables. These two types should never,
	 * ever, have their data dumped in the SQL dump file.
	 *
	 * @param   bool  $abstract  Return abstract or normal names? Defaults to true (abstract names)
	 *
	 * @return array
	 */
	public function getTables($abstract = true)
	{
		if (!empty($this->tablesCache[$this->_database]))
		{
			return $this->tablesCache[$this->_database];
		}

		$sql = "SHOW TABLES";
		$this->setQuery($sql);
		$all_tables = $this->loadColumn();

		if (!empty($all_tables))
		{
			// Start by adding tables and views to the list
			foreach ($all_tables as $table_name)
			{
				if ($abstract)
				{
					$table_name = $this->getAbstract($table_name);
				}
				$this->tablesCache[$this->_database][$table_name] = 'table';
			}

			// Loop all metadatas
			foreach ($all_tables as $table_metadata)
			{
				$table_name     = $table_metadata;
				$table_abstract = $this->getAbstract($table_metadata);
				$type           = 'table';

				if ($abstract)
				{
					$table_metadata = $table_abstract;
				}

				$create = $this->get_create($table_abstract, $table_name, $type);
				// Scan for the table engine.
				$engine = null; // So that we detect VIEWs correctly

				if ($type == 'table')
				{
					$engine      = 'MyISAM'; // So that even with MySQL 4 hosts we don't screw this up
					$engine_keys = ['ENGINE=', 'TYPE='];
					foreach ($engine_keys as $engine_key)
					{
						$start_pos = strrpos($create, $engine_key);
						if ($start_pos !== false)
						{
							// Advance the start position just after the position of the ENGINE keyword
							$start_pos += strlen($engine_key);
							// Try to locate the space after the engine type
							$end_pos = stripos($create, ' ', $start_pos);
							if ($end_pos === false)
							{
								// Uh... maybe it ends with ENGINE=EngineType;
								$end_pos = stripos($create, ';');
							}
							if ($end_pos !== '')
							{
								// Grab the string
								$engine = substr($create, $start_pos, $end_pos - $start_pos);
							}
						}
					}
					$engine = strtoupper($engine);
				}

				switch ($engine)
				{
					// Views -- FIX: They are detected based on their CREATE STATEMENT
					case null:
						$this->tablesCache[$this->_database][$table_metadata] = 'view';
						break;

					// Merge tables
					case 'MRG_MYISAM':
						$this->tablesCache[$this->_database][$table_metadata] = 'merge';
						break;

					// Tables whose data we do not back up (memory, federated and can-have-no-data tables)
					case 'MEMORY':
					case 'EXAMPLE':
					case 'BLACKHOLE':
					case 'FEDERATED':
						$this->tablesCache[$this->_database][$table_metadata] = 'temp';
						break;

					// Normal tables
					default:
						break;
				} // switch
			} // foreach
		} // if !empty

		// If we have MySQL > 5.0 add the list of stored procedures, stored functions
		// and triggers
		$registry        = Factory::getConfiguration();
		$enable_entities = $registry->get('engine.dump.native.advanced_entitites', true);
		if ($enable_entities)
		{
			// 1. Stored procedures
			$sql = "SHOW PROCEDURE STATUS WHERE " . $this->quoteName('Db') . "=" . $this->quote($this->_database);
			$this->setQuery($sql);

			try
			{
				$all_entries = $this->loadAssocList();
			}
			catch (Exception $e)
			{
				$all_entries = [];
			}

			if (is_array($all_entries) || $all_entries instanceof \Countable ? count($all_entries) : 0)
			{
				foreach ($all_entries as $entry)
				{
					$table_name = $entry['Name'];
					if ($abstract)
					{
						$table_name = $this->getAbstract($table_name);
					}
					$this->tablesCache[$this->_database][$table_name] = 'procedure';
				}
			}

			// 2. Stored functions
			$sql = "SHOW FUNCTION STATUS WHERE " . $this->quoteName('Db') . "=" . $this->quote($this->_database);
			$this->setQuery($sql);

			try
			{
				$all_entries = $this->loadColumn(1);
			}
			catch (Exception $e)
			{
				$all_entries = [];
			}

			// If we have filters, make sure the tables pass the filtering
			if (is_array($all_entries))
			{
				if (count($all_entries))
				{
					foreach ($all_entries as $table_name)
					{
						if ($abstract)
						{
							$table_name = $this->getAbstract($table_name);
						}
						$this->tablesCache[$this->_database][$table_name] = 'function';
					}
				}
			}

			// 3. Triggers
			$sql = "SHOW TRIGGERS";
			$this->setQuery($sql);

			try
			{
				$all_entries = $this->loadColumn();
			}
			catch (Exception $e)
			{
				$all_entries = [];
			}

			// If we have filters, make sure the tables pass the filtering
			if (is_array($all_entries))
			{
				if (count($all_entries))
				{
					foreach ($all_entries as $table_name)
					{
						if ($abstract)
						{
							$table_name = $this->getAbstract($table_name);
						}
						$this->tablesCache[$this->_database][$table_name] = 'trigger';
					}
				}
			}

		}

		return $this->tablesCache[$this->_database];
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

	/**
	 * Locks a table in the database.
	 *
	 * @param   string  $table  The name of the table to unlock.
	 *
	 * @return  Mysqli  Returns this object to support chaining.
	 */
	public function lockTable($table)
	{
		$this->setQuery('LOCK TABLES ' . $this->quoteName($table) . ' WRITE')->query();

		return $this;
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
	 * Renames a table in the database.
	 *
	 * @param   string  $oldTable  The name of the table to be renamed
	 * @param   string  $newTable  The new name for the table.
	 * @param   string  $backup    Not used by MySQL.
	 * @param   string  $prefix    Not used by MySQL.
	 *
	 * @return  Mysqli  Returns this object to support chaining.
	 */
	public function renameTable($oldTable, $newTable, $backup = null, $prefix = null)
	{
		$this->setQuery('RENAME TABLE ' . $oldTable . ' TO ' . $newTable)->query();

		return $this;
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
	 * Method to commit a transaction.
	 *
	 * @return  void
	 */
	public function transactionCommit()
	{
		$this->setQuery('COMMIT');
		$this->execute();
	}

	/**
	 * Method to roll back a transaction.
	 *
	 * @return  void
	 */
	public function transactionRollback()
	{
		$this->setQuery('ROLLBACK');
		$this->execute();
	}

	/**
	 * Method to initialize a transaction.
	 *
	 * @return  void
	 */
	public function transactionStart()
	{
		$this->setQuery('START TRANSACTION');
		$this->execute();
	}

	/**
	 * Unlocks tables in the database.
	 *
	 * @return  Mysqli  Returns this object to support chaining.
	 *
	 * @throws  Exception
	 * @since   11.4
	 */
	public function unlockTables()
	{
		$this->setQuery('UNLOCK TABLES')->execute();

		return $this;
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

	/**
	 * Gets the CREATE TABLE command for a given table/view
	 *
	 * @param   string  $table_abstract  The abstracted name of the entity
	 * @param   string  $table_name      The name of the table
	 * @param   string  $type            The type of the entity to scan. If it's found to differ, the correct type is
	 *                                   returned.
	 *
	 * @return string The CREATE command, w/out newlines
	 */
	protected function get_create($table_abstract, $table_name, &$type)
	{
		$sql = "SHOW CREATE TABLE " . $this->quoteName($table_name);
		$this->setQuery($sql);
		$temp      = $this->loadRowList();
		$table_sql = $temp[0][1];
		unset($temp);

		// Smart table type detection
		if (in_array($type, ['table', 'merge', 'view']))
		{
			// Check for CREATE VIEW
			$pattern = '/^CREATE(.*) VIEW (.*)/i';
			$result  = preg_match($pattern, $table_sql);
			if ($result === 1)
			{
				// This is a view.
				$type = 'view';
			}
			else
			{
				// This is a table.
				$type = 'table';
			}

			// Is it a VIEW but we don't have SHOW VIEW privileges?
			if (empty($table_sql))
			{
				$type = 'view';
			}
		}

		$table_sql = str_replace($table_name, $table_abstract, $table_sql);

		// Replace newlines with spaces
		$table_sql = str_replace("\n", " ", $table_sql) . ";\n";
		$table_sql = str_replace("\r", " ", $table_sql);
		$table_sql = str_replace("\t", " ", $table_sql);

		// Post-process CREATE VIEW
		if ($type == 'view')
		{
			$pos_view = strpos($table_sql, ' VIEW ');

			if ($pos_view > 7)
			{
				// Only post process if there are view properties between the CREATE and VIEW keywords
				$propstring = substr($table_sql, 7, $pos_view - 7); // Properties string
				// Fetch the ALGORITHM={UNDEFINED | MERGE | TEMPTABLE} keyword
				$algostring = '';
				$algo_start = strpos($propstring, 'ALGORITHM=');
				if ($algo_start !== false)
				{
					$algo_end   = strpos($propstring, ' ', $algo_start);
					$algostring = substr($propstring, $algo_start, $algo_end - $algo_start + 1);
				}
				// Create our modified create statement
				$table_sql = 'CREATE OR REPLACE ' . $algostring . substr($table_sql, $pos_view);
			}
		}

		return $table_sql;
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

	protected function unsafe_escape($string)
	{
		if (function_exists('mb_ereg_replace'))
		{
			return mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x27\x5C]', '\\\0', $string);
		}

		return preg_replace('~[\x00\x0A\x0D\x1A\x22\x27\x5C]~u', '\\\$0', $string);
	}
}
