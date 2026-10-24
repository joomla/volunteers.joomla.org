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

use Akeeba\Engine\Driver\Query\Postgresql as QueryPostgresql;
use PDO;
use PDOException;
use PDOStatement;
use ReflectionClass;
use RuntimeException;
use Throwable;

/**
 * PDO PostgreSQL database driver for Akeeba Engine
 */
#[\AllowDynamicProperties]
class Postgresql extends Base
{
	/**
	 * The name of the database driver.
	 *
	 * @var    string
	 */
	public $name = 'postgresql';

	/** @var string Connection character set */
	protected $charset = 'UTF8';

	/** @var PDO The db connection resource */
	protected $connection = null;

	/** @var PDOStatement The database connection cursor from the last query. */
	protected $cursor;

	/** @var array Driver options for PDO */
	protected $driverOptions = [];

	/**
	 * Database object constructor
	 *
	 * @param   array  $options  List of options used to configure the connection
	 */
	public function __construct($options)
	{
		$this->driverType = 'postgresql';

		// Init
		$this->nameQuote = '"';

		// Open the connection
		$this->host           = $options['host'] ?? 'localhost';
		$this->user           = $options['user'] ?? '';
		$this->password       = $options['password'] ?? '';
		$this->port           = ($options['port'] ?? 5432) ?: 5432;
		$this->_database      = $options['database'] ?? '';
		$this->selectDatabase = $options['select'] ?? true;

		$this->charset       = $options['charset'] ?? 'UTF8';
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
	 * Test to see if the PostgreSQL connector is available.
	 *
	 * @return  boolean  True on success, false otherwise.
	 */
	public static function isSupported()
	{
		if (!defined('\PDO::ATTR_DRIVER_NAME'))
		{
			return false;
		}

		return in_array('pgsql', PDO::getAvailableDrivers());
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
			if (!$property->isStatic() && !($this->{$property->name} instanceof PDO))
			{
				$serializedProperties[] = $property->name;
			}
		}

		return $serializedProperties;
	}

	/**
	 * Wake up after serialization
	 *
	 * @return  void
	 */
	public function __wakeup()
	{
		// Get connection back
		$this->__construct($this->options);
	}

	/**
	 * Quote a value as a hex string.
	 *
	 * @param   string  $text  The value to quote as hex.
	 *
	 * @return  string  The hex-quoted value.
	 * @since   10.3
	 */
	public function quoteHex(string $text): string
	{
		return "'\\x" . bin2hex($text) . "'";
	}

	/**
	 * Replaces the table prefix in identifiers, excluding string literals and comments.
	 *
	 * @param   string  $sql     The SQL statement to prepare.
	 * @param   string  $prefix  The common table prefix.
	 *
	 * @return  string  The processed SQL statement.
	 * @since   10.3
	 */
	public function replacePrefix($sql, $prefix = '#__')
	{
		$sql = trim((string) $sql);
		$n   = strlen($sql);
		$out = '';
		$i   = 0;
		$pl  = strlen($prefix);

		while ($i < $n)
		{
			$ch = $sql[$i];

			// Skip single-quoted literals
			if ($ch === "'")
			{
				$out .= $ch;
				$i++;

				while ($i < $n)
				{
					$ch = $sql[$i];
					$out .= $ch;
					$i++;

					if ($ch === "'")
					{
						if ($i < $n && $sql[$i] === "'")
						{
							$out .= $sql[$i];
							$i++;
							continue;
						}

						break;
					}
				}

				continue;
			}

			// Skip dollar-quoted literals
			if ($ch === '$')
			{
				$j = $i + 1;

				while ($j < $n)
				{
					$c = $sql[$j];
					if (($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || ($c >= '0' && $c <= '9') || $c === '_')
					{
						$j++;
						continue;
					}
					break;
				}

				if ($j < $n && $sql[$j] === '$')
				{
					$tag = substr($sql, $i, $j - $i + 1);
					$k   = strpos($sql, $tag, $j + 1);

					if ($k !== false)
					{
						$out .= substr($sql, $i, $k - $i + strlen($tag));
						$i = $k + strlen($tag);
						continue;
					}
				}
			}

			// Skip line comments
			if ($ch === '-' && ($i + 1 < $n) && $sql[$i + 1] === '-')
			{
				$k = strpos($sql, "\n", $i + 2);
				if ($k === false)
				{
					$out .= substr($sql, $i);
					break;
				}

				$out .= substr($sql, $i, $k - $i + 1);
				$i = $k + 1;
				continue;
			}

			// Skip block comments
			if ($ch === '/' && ($i + 1 < $n) && $sql[$i + 1] === '*')
			{
				$k = strpos($sql, '*/', $i + 2);
				if ($k === false)
				{
					$out .= substr($sql, $i);
					break;
				}

				$out .= substr($sql, $i, $k - $i + 2);
				$i = $k + 2;
				continue;
			}

			// Replace prefix in identifiers and other SQL tokens (not in literals/comments)
			if ($pl > 0 && substr($sql, $i, $pl) === $prefix)
			{
				$out .= $this->tablePrefix;
				$i   += $pl;
				continue;
			}

			$out .= $ch;
			$i++;
		}

		return $out;
	}

	public function close()
	{
		$return = false;

		if (is_object($this->cursor))
		{
			try
			{
				$this->cursor->closeCursor();
			}
			catch (Throwable $e)
			{
			}

			$this->cursor = null;
		}

		if (is_object($this->connection))
		{
			$this->connection = null;
			$return           = true;
		}

		return $return;
	}

	public function connected()
	{
		if (is_object($this->connection))
		{
			try
			{
				$this->connection->query('SELECT 1');

				return true;
			}
			catch (PDOException $e)
			{
			}
		}

		return false;
	}

	public function escape($text, $extra = false)
	{
		// PDO hands back native ints and floats. Render them ourselves; PDO::quote() would cast the float lossily.
		if (is_int($text))
		{
			return (string) $text;
		}

		if (is_float($text))
		{
			return $this->floatToSqlString($text);
		}

		if (is_object($this->connection))
		{
			$result = substr($this->connection->quote($text), 1, -1);
		}
		else
		{
			$result = str_replace("'", "''", $text);
		}

		if ($extra)
		{
			$result = addcslashes($result, '%_');
		}

		return $result;
	}

	public function fetchAssoc($cursor = null)
	{
		$cursor = $cursor ?: $this->cursor;

		if (is_object($cursor))
		{
			return $cursor->fetch(PDO::FETCH_ASSOC);
		}

		return null;
	}

	public function freeResult($cursor = null)
	{
		$cursor = $cursor ?: $this->cursor;

		if (is_object($cursor))
		{
			$cursor->closeCursor();
		}
	}

	public function getAffectedRows()
	{
		if (is_object($this->cursor))
		{
			return $this->cursor->rowCount();
		}

		return 0;
	}

	public function getNumRows($cursor = null)
	{
		$cursor = $cursor ?: $this->cursor;

		if (is_object($cursor))
		{
			return $cursor->rowCount();
		}

		return 0;
	}

	public function getQuery($new = false)
	{
		if ($new || empty($this->sql))
		{
			return new QueryPostgresql($this);
		}

		return $this->sql;
	}

	public function createQuery()
	{
		return new QueryPostgresql($this);
	}

	public function getVersion()
	{
		return $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
	}

	public function hasUTF()
	{
		return true;
	}

	public function insertid()
	{
		return $this->connection->lastInsertId();
	}

	public function loadNextObject($class = 'stdClass')
	{
		if ($row = $this->fetchObject($this->cursor, $class))
		{
			return $row;
		}

		$this->freeResult();

		return false;
	}

	public function loadNextRow()
	{
		if ($row = $this->fetchArray($this->cursor))
		{
			return $row;
		}

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

		$dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->_database}";

		if (!empty($this->options['schema']))
		{
			$dsn .= ";options='--search_path=" . addslashes((string)$this->options['schema']) . "'";
		}

		try
		{
			$this->connection = new PDO(
				$dsn,
				$this->user,
				$this->password,
				$this->driverOptions
			);
		}
		catch (PDOException $e)
		{
			$this->errorNum = 2;
			$this->errorMsg = 'Could not connect to PostgreSQL via PDO: ' . $e->getMessage();

			return;
		}

		$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

		if (!empty($this->charset))
		{
			$this->connection->exec("SET client_encoding TO " . $this->connection->quote($this->charset));
		}

		$this->freeResult();
	}

	public function query()
	{
		if (!is_object($this->connection))
		{
			$this->open();
		}

		$this->freeResult();

		// Take a local copy so that we don't modify the original query and cause issues later
		$query = $this->replacePrefix((string)$this->sql);

		// Apply query limit and offset, if specified
		if ($this->limit > 0 || $this->offset > 0)
		{
			if ($this->limit > 0)
			{
				$query .= ' LIMIT ' . $this->limit;
			}

			if ($this->offset > 0)
			{
				$query .= ' OFFSET ' . $this->offset;
			}
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
		catch (Throwable $e)
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

	public function select($database)
	{
		$this->_database = $database;
		$this->open();

		return true;
	}

	public function setUTF()
	{
		$this->connection->exec("SET client_encoding TO 'UTF8'");
	}

	public function transactionCommit()
	{
		$this->connection->commit();
	}

	public function transactionRollback()
	{
		$this->connection->rollBack();
	}

	public function transactionStart()
	{
		$this->connection->beginTransaction();
	}

	public function fetchArray($cursor = null)
	{
		$cursor = $cursor ?: $this->cursor;

		if (is_object($cursor))
		{
			return $cursor->fetch(PDO::FETCH_NUM);
		}

		return null;
	}

	public function fetchObject($cursor = null, $class = 'stdClass')
	{
		$cursor = $cursor ?: $this->cursor;

		if (is_object($cursor))
		{
			return $cursor->fetchObject($class);
		}

		return null;
	}

	public function getTableList()
	{
		$schema = $this->options['schema'] ?? 'public';
		$this->setQuery('SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = ' . $this->quote($schema));

		return $this->loadColumn();
	}

	public function getTableColumns($table, $typeOnly = true)
	{
		$table  = $this->replacePrefix($table);
		$schema = $this->options['schema'] ?? 'public';
		$sql    = "SELECT column_name, data_type 
				FROM information_schema.columns 
				WHERE table_schema = " . $this->quote($schema) . " 
				AND table_name = " . $this->quote($table);
		$this->setQuery($sql);
		$fields = $this->loadObjectList();

		if ($typeOnly)
		{
			$result = [];
			foreach ($fields as $field)
			{
				$result[$field->column_name] = $field->data_type;
			}

			return $result;
		}

		return $fields;
	}

	public function dropTable($table, $ifExists = true)
	{
		$query = 'DROP TABLE ' . ($ifExists ? 'IF EXISTS ' : '') . $this->quoteName($table);
		$this->setQuery($query)->execute();
	}

	public function getCollation()
	{
		$this->setQuery("SELECT datcollate FROM pg_database WHERE datname = " . $this->quote($this->_database));

		return $this->loadResult();
	}

	public function getTableCreate($tables)
	{
		return [];
	}

	public function getTableKeys($tables)
	{
		return [];
	}

	public function getTables($abstract = true)
	{
		$tables = $this->getTableList();
		$result = [];
		foreach ($tables as $table)
		{
			$name          = $abstract ? $this->getAbstract($table) : $table;
			$result[$name] = 'table';
		}

		return $result;
	}

	public function lockTable($tableName)
	{
		$this->setQuery('LOCK TABLE ' . $this->quoteName($tableName) . ' IN SHARE MODE')->execute();
	}

	public function renameTable($oldTable, $newTable, $backup = null, $prefix = null)
	{
		$query = 'ALTER TABLE ' . $this->quoteName($oldTable) . ' RENAME TO ' . $this->quoteName($newTable);
		$this->setQuery($query)->execute();
	}

	public function unlockTables()
	{
		return $this;
	}
}
