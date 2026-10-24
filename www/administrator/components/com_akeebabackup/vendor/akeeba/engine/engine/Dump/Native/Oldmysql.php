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

namespace Akeeba\Engine\Dump\Native;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Driver\QueryException;
use Akeeba\Engine\Dump\Base;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Exception;
use RuntimeException;

/**
 * A generic MySQL database dump class.
 * Now supports views; merge, in-memory, federated, blackhole, etc tables
 * Configuration parameters:
 * host            <string>    MySQL database server host name or IP address
 * port            <string>    MySQL database server port (optional)
 * username        <string>    MySQL user name, for authentication
 * password        <string>    MySQL password, for authentication
 * database        <string>    MySQL database
 * dumpFile        <string>    Absolute path to dump file; must be writable (optional; if left blank it is
 * automatically calculated)
 */
#[\AllowDynamicProperties]
class Oldmysql extends Base
{
	/**
	 * The primary key structure of the currently backed up table. The keys contained are:
	 * - table        The name of the table being backed up
	 * - field        The name of the primary key field
	 * - value        The last value of the PK field
	 *
	 * @var array
	 */
	protected $table_autoincrement = [
		'table' => null,
		'field' => null,
		'value' => null,
	];

	private $columnListColumnType = [];

	private $columnListSelectColumn = '*';

	private $lastTableColumnType = null;

	private $lastTableSelectColumn = null;

	/**
	 * Implements the constructor of the class
	 *
	 * @return  void
	 */
	public function __construct()
	{
		parent::__construct();

		Factory::getLog()->debug(__CLASS__ . " :: New instance");
	}

	/**
	 * Replaces the table names in the CREATE query with their abstract form. Optionally updates dependencies.
	 *
	 * @param   string  $tableName        The table name the CREATE query is for
	 * @param   string  $tableSql         The CREATE query itself
	 * @param   bool    $withDependecies  Should I update dependencies?
	 *
	 * @return  array [$dependencies, $modifiedSQLQuery] - Dependency information for the table (if $withDependencies)
	 *                and the new CREATE query with all table names replaced with abstract versions.
	 *
	 * @throws  Exception  When we cannot get the DB object
	 */
	public function replaceTableNamesWithAbstracts($tableName, $tableSql, $withDependecies = false)
	{
		// Initialization
		$dependencies = [];
		$tableNameMap = $this->table_name_map;
		$db           = $this->getDB();

		if (!array_key_exists($tableName, $tableNameMap))
		{
			$tableNameMap[$tableName] = $this->getAbstract($tableName);
		}

		foreach ($tableNameMap as $fullName => $abstractName)
		{
			$quotedFullName     = $db->quoteName($fullName);
			$quotedAbstractName = $db->quoteName($abstractName);
			$pos                = strpos($tableSql, $quotedFullName);
			$numReplacements    = 0;

			if ($pos !== false)
			{
				$numReplacements = 1;

				// Do the replacement
				$tableSql = str_replace($quotedFullName, $quotedAbstractName, $tableSql);
			}
			elseif (!is_numeric($fullName))
			{
				$offset                   = 0;
				$fullNameLength           = strlen($fullName);
				$quotedAbstractNameLength = strlen($quotedAbstractName);

				/**
				 * We need to detect the edges of table names. If they are enclosed in backticks it's pretty clear. If they are
				 * not, e.g. in the definitions of TRIGGERs, we need to base our detection on the valid characters for
				 * unquoted MySQL table names per https://dev.mysql.com/doc/refman/5.7/en/identifiers.html
				 */
				[$bareCharRegex, $regexFlags] = $this->getMySQLIdentifierCharacterRegEx();
				$fullCharRegex = "/$bareCharRegex/$regexFlags";

				while (true)
				{
					$pos = strpos($tableSql, $fullName, $offset);

					if ($pos === false)
					{
						break;
					}

					// Skip over table-name-like strings in strings enclosed by single quotes
					$quotePos = strpos($tableSql, "'", $offset);

					if ($quotePos !== false && $quotePos < $pos)
					{
						// The table-like token is inside a string. Find its end.
						while (true)
						{
							$nextPos = strpos($tableSql, "'", $quotePos + 1);

							if ($nextPos === false)
							{
								break;
							}

							$prevChar = $nextPos > 0 ? $tableSql[$nextPos - 1] : null;
							$nextChar = strlen($tableSql) > $nextPos + 1 ? $tableSql[$nextPos + 1] : null;

							// Catch quote escaped as \'
							if ($prevChar === '\\')
							{
								$quotePos = $nextPos;

								continue;
							}

							// Catch quote escaped as ''
							if ($nextChar === "'")
							{
								$quotePos = $nextPos + 1;

								continue;
							}

							$offset = $nextPos + 1;

							continue 2;
						}
					}

					// Skip over table-name-like strings in strings enclosed by double quotes
					$quotePos = strpos($tableSql, '"', $offset);

					if ($quotePos !== false && $quotePos < $pos)
					{
						// The table-like token is inside a string. Find its end.
						while (true)
						{
							$nextPos = strpos($tableSql, '"', $quotePos + 1);

							if ($nextPos === false)
							{
								break;
							}

							$prevChar = $nextPos > 0 ? $tableSql[$nextPos - 1] : null;
							$nextChar = strlen($tableSql) > $nextPos + 1 ? $tableSql[$nextPos + 1] : null;

							// Catch quote escaped as \"
							if ($prevChar === '\\')
							{
								$quotePos = $nextPos;

								continue;
							}

							// Catch quote escaped as ""
							if ($nextChar === '"')
							{
								$quotePos = $nextPos + 1;

								continue;
							}

							$offset = $nextPos + 1;

							continue 2;
						}
					}

					// Catch table-name-like substring inside another table's name
					$previousChar    = ($pos > 0) ? substr($tableSql, $pos - 1, 1) : '';
					$nextChar        = ($pos < (strlen($tableSql) - $fullNameLength)) ? substr($tableSql, $pos + $fullNameLength, 1) : '';
					$prevIsTableChar = $previousChar === '' ? false : preg_match($fullCharRegex, $previousChar);
					$nextIsTableChar = $nextChar === '' ? false : preg_match($fullCharRegex, $nextChar);

					if ($prevIsTableChar || $nextIsTableChar)
					{
						$offset = $pos + 1;

						continue;
					}

					$before = ($pos > 0) ? substr($tableSql, 0, $pos) : '';
					$after  = ($pos < (strlen($tableSql) - $fullNameLength)) ? substr($tableSql, $pos + $fullNameLength) : '';

					$numReplacements++;
					$tableSql = $before . $quotedAbstractName . $after;

					$offset = $pos + $quotedAbstractNameLength;
				}
			}

			if ($withDependecies && $numReplacements && ($fullName != $tableName))
			{
				// Add a reference hit
				$this->dependencies[$fullName][] = $tableName;
				// Add the dependency to this table's metadata
				$dependencies[] = $fullName;
			}
		}

		return [$dependencies, $tableSql];
	}

	/**
	 * Creates a drop query from a CREATE query
	 *
	 * @param   string  $query  The CREATE query to process
	 *
	 * @return  string  The DROP statement
	 */
	protected function createDrop($query)
	{
		$type = $this->getTypeFromCreateQuery($query);

		if (empty($type))
		{
			return '';
		}

		$marker         = ' ' . strtoupper(trim($type)) . ' ';
		$markerPosition = strpos($query, $marker);
		// Rest of query, after entity key string
		$restOfQuery = trim(substr($query, $markerPosition + strlen($marker)));

		// Is there a backtick?
		if (substr($restOfQuery, 0, 1) == '`')
		{
			// There is a backtick. Iterate character-by-character to find the ending backtick.
			$pos = 0;

			while (true)
			{
				$pos++;

				// We need visibility in both of the next characters to find escaped backticks.
				$thisChar = substr($restOfQuery, $pos, 1);
				$nextChar = substr($restOfQuery, $pos + 1, 1);

				// Did we reach the end of the string?
				if ($thisChar === false || $thisChar === '')
				{
					break;
				}

				// Two backticks side-by-side is an escaped backtick; skip over it
				if ($thisChar === '`' && $nextChar === '`')
				{
					$pos++;
					continue;
				}

				// Current char is a backtick, the next one is not, we found the ending backtick.
				if ($thisChar === '`' && $nextChar !== '`')
				{
					break;
				}
			}

			$entityName = substr($restOfQuery, 1, $pos - 1);
		}
		else
		{
			// Nope, let's assume the entity name ends in the next blank character.
			$pos        = strpos($restOfQuery, ' ', 1);
			$entityName = substr($restOfQuery, 0, $pos);
		}

		return sprintf(
			"DROP %s IF EXISTS %s;",
			strtoupper(trim($type)),
			$this->getDB()->nameQuote($entityName)
		);
	}

	/**
	 * Applies the SQL compatibility setting
	 *
	 * @return  void
	 */
	protected function enforceSQLCompatibility()
	{
		$db = $this->getDB();

		// Try to enforce SQL_BIG_SELECTS option
		try
		{
			$db->setQuery('SET sql_big_selects=1');
			$db->query();
		}
		catch (Exception $e)
		{
			// Do nothing; some versions of MySQL don't allow you to use the BIG_SELECTS option.
		}

		$db->resetErrors();
	}

	/**
	 * Return a list of columns and their data types.
	 *
	 * @param   string  $tableAbstract
	 *
	 * @return  array  An array of table columns and their data types.
	 */
	protected function getColumnTypes($tableAbstract)
	{
		if ($this->lastTableColumnType == $tableAbstract)
		{
			return $this->columnListColumnType;
		}

		$this->lastTableColumnType = $tableAbstract;

		try
		{
			$db = $this->getDB();

			$db->setQuery('SHOW COLUMNS FROM ' . $db->qn($tableAbstract));

			$tableCols = $db->loadAssocList();
		}
		catch (Exception $e)
		{
			return $this->columnListColumnType;
		}

		foreach ($tableCols as $col)
		{
			$typeParts                                 = explode('(', $col['Type'], 2);
			$this->columnListColumnType[$col['Field']] = strtoupper($typeParts[0]);
		}

		return $this->columnListColumnType;
	}

	/**
	 * Return the current database name by querying the database connection object (e.g. SELECT DATABASE() in MySQL)
	 *
	 * @return  string
	 */
	protected function getDatabaseNameFromConnection(): string
	{
		$db = $this->getDB();

		try
		{
			$ret = $db->setQuery('SELECT DATABASE()')->loadResult();
		}
		catch (Exception $e)
		{
			return '';
		}

		return empty($ret) ? '' : $ret;
	}

	/**
	 * Get the default database dump batch size from the configuration
	 *
	 * @return  int
	 */
	protected function getDefaultBatchSize()
	{
		static $batchSize = null;

		if (is_null($batchSize))
		{
			$configuration = Factory::getConfiguration();
			$batchSize     = intval($configuration->get('engine.dump.common.batchsize', 1000));

			if ($batchSize <= 0)
			{
				$batchSize = 1000;
			}
		}

		return $batchSize;
	}

	/**
	 * Get a regular expression and its options for valid characters of an unquoted MySQL identifier.
	 *
	 * This is used wherever we need to detect an arbitrary, unquoted MySQL identifier per
	 * https://dev.mysql.com/doc/refman/5.7/en/identifiers.html
	 *
	 * Also what if Unicode support is not compiled in PCRE? In this case we will fall back to a much simpler regex
	 * which only supports the ASCII subset of the allowed characters. In this case your database dump will be wrong
	 * if you use table names with non-ASCII characters.
	 *
	 * Since the detection is horribly slow we cache its results in an internal static variable.
	 *
	 * @return  array  In the format [$regex, $flags]
	 * @since   7.0.0
	 */
	protected function getMySQLIdentifierCharacterRegEx()
	{
		static $validCharRegEx = null;
		static $unicodeFlag = null;

		if (is_null($validCharRegEx) || is_null($unicodeFlag))
		{
			$noUnicode      = @preg_match('/\p{L}/u', 'σ') !== 1;
			$unicodeFlag    = $noUnicode ? '' : 'u';
			$validCharRegEx = $noUnicode ? '[0-9a-zA-Z$_]' : '[0-9a-zA-Z$_]|[\x{0080}-\x{FFFF}]';
		}

		return [$validCharRegEx, $unicodeFlag];
	}

	/**
	 * Get the optimal row batch size for a given table based on the available memory
	 *
	 * @param   string  $tableAbstract     The abstract table name, e.g. #__foobar
	 * @param   int     $defaultBatchSize  The default row batch size in the application configuration
	 *
	 * @return  int
	 */
	protected function getOptimalBatchSize($tableAbstract, $defaultBatchSize)
	{
		$db = $this->getDB();

		try
		{
			$info = $db->setQuery('SHOW TABLE STATUS LIKE ' . $db->q($tableAbstract))->loadAssoc();
		}
		catch (Exception $e)
		{
			return $defaultBatchSize;
		}

		if (!isset($info['Avg_row_length']) || empty($info['Avg_row_length']))
		{
			return $defaultBatchSize;
		}

		// That's the average row size as reported by MySQL.
		$avgRow = str_replace([',', '.'], ['', ''], $info['Avg_row_length']);
		// The memory available for manipulating data is less than the free memory
		$memoryLimit = $this->getMemoryLimit();
		$memoryLimit = empty($memoryLimit) ? 33554432 : $memoryLimit;
		$usedMemory  = memory_get_usage();
		$memoryLeft  = 0.75 * ($memoryLimit - $usedMemory);
		// The 3.25 factor is empirical and leans on the safe side.
		$maxRows = (int) ($memoryLeft / (3.25 * $avgRow));

		return max(1, min($maxRows, $defaultBatchSize));
	}

	/**
	 * Gets the row count for table $tableAbstract. Also updates the $this->maxRange variable.
	 *
	 * @param   string  $tableAbstract  The abstract name of the table (works with canonical names too, though)
	 *
	 * @return  void
	 *
	 * @throws  QueryException
	 */
	protected function getRowCount($tableAbstract)
	{
		$db = $this->getDB();

		$sql = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select('COUNT(*)')
			->from($db->nameQuote($tableAbstract));

		$errno = 0;
		$error = '';

		try
		{
			$db->setQuery($sql);
			$this->maxRange = $db->loadResult();

			if (is_null($this->maxRange))
			{
				$errno = $db->getErrorNum();
				$error = $db->getErrorMsg(false);
			}
		}
		catch (Exception $e)
		{
			$this->maxRange = null;
			$errno          = $e->getCode();
			$error          = $e->getMessage();
		}

		if (is_null($this->maxRange))
		{
			Factory::getLog()->warning("Cannot get number of rows of $tableAbstract. MySQL error $errno: $error");

			return;
		}

		Factory::getLog()->debug("Rows on " . $tableAbstract . " : " . $this->maxRange);
	}

	/**
	 * Return a list of columns to use in the SELECT query for dumping table data.
	 *
	 * This is used to filter out all generated rows.
	 *
	 * @param   string  $tableAbstract
	 *
	 * @return  string|array  An array of table columns or the string literal '*' to quickly select all columns.
	 *
	 * @see  https://dev.mysql.com/doc/refman/5.7/en/create-table-generated-columns.html
	 */
	protected function getSelectColumns($tableAbstract)
	{
		if ($this->lastTableSelectColumn == $tableAbstract)
		{
			return $this->columnListSelectColumn;
		}

		$this->lastTableSelectColumn = $tableAbstract;

		try
		{
			$db = $this->getDB();

			$db->setQuery('SHOW COLUMNS FROM ' . $db->qn($tableAbstract));

			$tableCols = $db->loadAssocList();
		}
		catch (Exception $e)
		{
			return $this->columnListSelectColumn;
		}

		$totalColumns                 = is_array($tableCols) || $tableCols instanceof \Countable ? count($tableCols) : 0;
		$this->columnListSelectColumn = [];

		$hasInvisibleColumns = false;

		foreach ($tableCols as $col)
		{
			// Skip over generated columns
			$attribs = array_map('strtoupper', empty($col['Extra']) ? [] : explode(' ', $col['Extra']));

			if (in_array('GENERATED', $attribs))
			{
				continue;
			}

			if (in_array('INVISIBLE', $attribs))
			{
				$hasInvisibleColumns = true;
			}

			$this->columnListSelectColumn[] = $col['Field'];
		}

		if (!$hasInvisibleColumns && ($totalColumns == count($this->columnListSelectColumn)))
		{
			$this->columnListSelectColumn = '*';
		}

		return $this->columnListSelectColumn;
	}

	/**
	 * Scans the database for tables to be backed up and sorts them according to
	 * their dependencies on one another. Updates $this->dependencies.
	 *
	 * @return  void
	 */
	protected function getTablesToBackup(): void
	{
		// Makes the MySQL connection compatible with our class
		$this->enforceSQLCompatibility();

		$configuration = Factory::getConfiguration();
		$notracking    = $configuration->get('engine.dump.native.nodependencies', 0);

		// First, get a map of table names <--> abstract names
		$this->populateTableNameMap();

		if ($notracking)
		{
			// Do not process table & view dependencies
			$this->populateTablesDataWithoutDependencies();
		}
		// Process table & view dependencies (default)
		else
		{
			// Find the type and CREATE command of each table/view in the database
			$this->populateTablesData();

			// Process dependencies and rearrange tables respecting them
			$this->processDependencies();

			// Remove dependencies array
			$this->dependencies = [];
		}
	}

	/**
	 * Gets the CREATE TABLE command for a given table, view, procedure, event, function, or trigger.
	 *
	 * @param   string  $abstractName  The abstracted name of the entity.
	 * @param   string  $concreteName  The concrete (database) name of the entity.
	 * @param   string  $type          The type of the entity to scan. May be updated.
	 * @param   array   $dependencies  The dependencies of this entity.
	 *
	 * @return  string|null  The CREATE statement
	 */
	protected function getCreateStatement(
		string $abstractName, string $concreteName, string &$type, array &$dependencies
	): ?string
	{
		$db  = $this->getDB();
		$sql = sprintf("SHOW CREATE %s %s", strtoupper($type), $db->quoteName($abstractName));
		$db->setQuery($sql);

		try
		{
			$temp = $db->loadAssocList();
		}
		catch (Exception $e)
		{
			// If the query failed we don't have the necessary SHOW privilege. Log the error and fake an empty reply.
			$entityType = ($type == 'merge') ? 'table' : $type;
			$msg        = $e->getMessage();
			Factory::getLog()->warning(
				"Cannot get the structure of $entityType $abstractName. Database returned error $msg running $sql  Please check your database privileges. Your database backup may be incomplete."
			);

			$db->resetErrors();

			$temp = [
				['', '', ''],
			];
		}

		$columnKey = 'Create ' . ucfirst(strtolower($type));
		$table_sql = $temp[0][$columnKey] ?? null;

		if ($table_sql === null)
		{
			$columnId  = $type === 'event' ? 3 : 2;
			$table_sql = array_values($temp[0])[$columnId] ?? null;
		}

		unset($temp);

		if (empty($table_sql))
		{
			Factory::getLog()->warning(
				"Cannot get the structure of $type $abstractName. The database refused to return the CREATE command for this $type. Please check your database privileges. Your database backup may be incomplete."
			);

			return null;
		}

		Factory::getLog()->debug(
			sprintf(
				'Got create for %s %s (internal name %s)',
				$type,
				$concreteName,
				$abstractName
			)
		);

		$isEntity = in_array($type, ['procedure', 'event', 'function', 'trigger']);
		$type     = (!$isEntity && $this->isCreateView($table_sql)) ? 'view' : $type;

		switch ($type)
		{
			case 'procedure':
			case 'event':
			case 'function':
			case 'trigger':
				$table_sql = $this->preProcessCreateSQLForEntity($table_sql, $type);
				break;

			case 'view':
				$table_sql = $this->preProcessCreateSQLForView($table_sql);
				break;

			case 'table':
			case 'merge':
			default:
				$table_sql = $this->preProcessCreateSQLForTable($table_sql);
		}

		$configuration = Factory::getConfiguration();
		$noTracking    = $configuration->get('engine.dump.native.nodependencies', 0);
		// On DB only backup we don't want to replace table / view / entity names with abstracts.
		$mustAbstractTableNames = Factory::getEngineParamsProvider()->getScriptingParameter('db.abstractnames', 1);

		/**
		 * Replace table name and names of referenced tables with their abstracted forms and populate dependency tables
		 * at the same time.
		 */
		if (!$mustAbstractTableNames)
		{
			$old_table_sql = $table_sql;
		}

		/**
		 * Abstract table names AND update dependency tracking information.
		 *
		 * Because this updates the tracking information we can not skip it when $mustAbstractTableNames is false. This
		 * is why we restore $table_sql in this case, and this case only.
		 *
		 * We have to quote the table name. If we don't we'll get wrong results. Imagine that you have a column whose
		 * name starts with the string literal of the table name itself.
		 *
		 * Example: table `poll`, column `poll_id` would become #__poll, #__poll_id
		 *
		 * By quoting before we make sure this won't happen.
		 */
		[$dependencies, $table_sql] = $this->replaceTableNamesWithAbstracts($concreteName, $table_sql, !$noTracking);

		if (!$mustAbstractTableNames)
		{
			$table_sql = $old_table_sql;
		}

		return $this->postProcessCreateSQL($table_sql, $type);
	}

	/**
	 * Populate the table and entities metadata.
	 *
	 * This method updates $this->tables_data with the metadata of MySQL tables, views, procedures, events, functions,
	 * and triggers. The metadata include the CREATE SQL statement, and whether to dump data.
	 *
	 * Moreover, the dependency information is generated as part of this process.
	 *
	 * @return  void
	 */
	protected function populateTablesData()
	{
		Factory::getLog()->debug(__CLASS__ . " :: Starting CREATE TABLE and dependency scanning");

		// Get a database connection
		$db = $this->getDB();

		Factory::getLog()->debug(__CLASS__ . " :: Got database connection");

		// Reset internal tables
		$this->tables_data  = [];
		$this->dependencies = [];

		$registry = Factory::getConfiguration();

		// Get a list of tables where their engine type is shown
		$this->generateMetadataForTables();

		// If we have MySQL > 5.0 add stored procedures, events, functions and triggers
		$enable_entities = $registry->get('engine.dump.native.advanced_entitites', true);

		if ($enable_entities)
		{
			Factory::getLog()->debug(__CLASS__ . " :: Listing MySQL entities");

			$this->generateMetadataForEntity('procedure');
			$this->generateMetadataForEntity('event');
			$this->generateMetadataForEntity('function');
			$this->generateMetadataForEntity('trigger');

			Factory::getLog()->debug(__CLASS__ . " :: Got MySQL entities list");
		}
	}

	/**
	 * Populates the _tables array with the metadata of each table.
	 * Updates $this->tables_data and $this->tables.
	 *
	 * @return  void
	 */
	protected function populateTablesDataWithoutDependencies()
	{
		Factory::getLog()->debug(__CLASS__ . " :: Pushing table data (without dependency tracking)");

		// Reset internal tables
		$this->tables_data  = [];
		$this->dependencies = [];

		// Get filters and filter root
		$registry = Factory::getConfiguration();
		$root     = $registry->get('volatile.database.root', '[SITEDB]');
		$filters  = Factory::getFilters();

		foreach ($this->table_name_map as $table_name => $table_abstract)
		{
			$new_entry = [
				'type'         => 'table',
				'dump_records' => true,
			];

			// Table Data Filter: skip dumping table contents of filtered out tables
			if ($filters->isFiltered($table_abstract, $root, 'dbobject', 'content'))
			{
				$new_entry['dump_records'] = false;
			}

			$this->tables_data[$table_name] = $new_entry;
			$this->tables[]                 = $table_name;
		}

		Factory::getLog()->debug(__CLASS__ . " :: Got table list");
	}

	/**
	 * Generate a map of table/entity names to their abstract versions.
	 *
	 * This method updates $this->table_name_map
	 *
	 * @return  void
	 */
	protected function populateTableNameMap(): void
	{
		// Get a database connection
		Factory::getLog()->debug(__CLASS__ . " :: Finding tables to include in the backup set");

		// Reset internal tables
		$this->table_name_map = [];

		$registry       = Factory::getConfiguration();
		$enableEntities = $registry->get('engine.dump.native.advanced_entitites', true);
		$noTracking     = $registry->get('engine.dump.native.nodependencies', 0);

		// Generate mapping for tables and views
		$this->generateMappingForEntities('table');

		/**
		 * If configured, we will process MySQL procedures, events, functions, and triggers.
		 *
		 * However, if dependency tracking is disabled we cannot do that, as we cannot guarantee that the result will
		 * be possible to be restored.
		 */
		if (!$enableEntities)
		{
			Factory::getLog()->debug(__CLASS__ . " :: NOT listing stored PROCEDUREs, FUNCTIONs and TRIGGERs (you told me not to)");
		}
		elseif ($noTracking != 0)
		{
			Factory::getLog()->debug(__CLASS__ . " :: NOT listing stored PROCEDUREs, FUNCTIONs and TRIGGERs (you have disabled dependency tracking, therefore I can't handle advanced entities)");
		}
		else
		{
			Factory::getLog()->debug(__CLASS__ . " :: Finding stored PROCEDUREs, EVENTs, FUNCTIONs and TRIGGERs to include in the backup set");

			$this->generateMappingForEntities('procedure');
			$this->generateMappingForEntities('event');
			$this->generateMappingForEntities('function');
			$this->generateMappingForEntities('trigger');
		}

		/**
		 * Store all abstract entity names (tables, views, triggers etc) into a volatile variable, so we can fetch
		 * it later when creating the databases.json file
		 */
		ksort($this->table_name_map);
		$registry->set('volatile.database.table_names', array_values($this->table_name_map));

		/**
		 * IMPORTANT -- DO NOT REMOVE
		 *
		 * We now need to reverse sort the table_name_map. This is of paramount importance in how the
		 * replaceTableNamesWithAbstracts method works. Consider the following case:
		 * foo_test_2 => #__test_2
		 * foo_test_20 => #__test_20
		 * If foo_test_2 comes before foo_test_2 (alpha sort) the CREATE command of foo_test_20 will end up as
		 * CREATE TABLE ``#__test_2`0` (...)
		 * instead of the correct
		 * CREATE TABLE `#__test_20` (...)
		 * That's because the first table replacement done there will be foo_test_2 => `#__test_2`. Ouch.
		 *
		 * By doing a reverse alpha sort on the keys we ENSURE that the longer table names which may be a superset of
		 * another table's name will always end up first on the list.
		 *
		 * In our example the first replacement made is foo_test_20 => `#__test_20`. When we reach the next possible
		 * replacement (foo_test_2) we no longer have the concrete table name foo_test_2 therefore we won't accidentally
		 * break the CREATE command.
		 *
		 * Of course the same replacement problem exists within VIEWs, TRIGGERs, PROCEDUREs and FUNCTIONs. Again, the
		 * reverse alpha sort by concrete table name solves this issue elegantly.
		 */
		krsort($this->table_name_map);
	}

	/**
	 * Process all table dependencies
	 *
	 * @return  void
	 */
	protected function processDependencies()
	{
		if (!is_countable($this->table_name_map) || !count($this->table_name_map))
		{
			Factory::getLog()->debug(__CLASS__ . " :: No dependencies to process");

			return;
		}

		foreach ($this->table_name_map as $table_name => $table_abstract)
		{
			$this->push_table($table_name);
		}

		Factory::getLog()->debug(__CLASS__ . " :: Processed dependencies");
	}

	/**
	 * Push an entity into the stack, taking dependencies into account.
	 *
	 * This method pushes an entity into the $this->tables stack, making sure it will appear after its dependencies.
	 * Other entities depending on it will eventually appear after it.
	 *
	 * WARNING! Circular dependencies WILL fail on restoration, unless they were only caused by foreign keys (checking
	 * foreign keys consistency is disabled during restoration). Anything else cannot be handled as it would require
	 * knowing the previous state(s) of the schema, replaying them during restoration. For what it's worth, this kind of
	 * circular dependencies won't work with phpMyAdmin, mysqldump etc. Circular references  are evil, insane, and
	 * should always be avoided!
	 *
	 * @param   string  $tableName   The canonical name of the table to push.
	 * @param   array   $stack       When called recursive, other views/tables previously processed to detect
	 *                               *ahem* dependency loops...
	 *
	 * @return  void
	 */
	protected function push_table($tableName, $stack = [], $currentRecursionDepth = 0)
	{
		if (!isset($this->tables_data[$tableName]))
		{
			return;
		}

		// Load information
		$table_data = $this->tables_data[$tableName];

		if (array_key_exists('dependencies', $table_data))
		{
			$referenced = $table_data['dependencies'];
		}
		else
		{
			$referenced = [];
		}

		unset($table_data);

		// Try to find the minimum insert position, so as to appear after the last referenced table
		$insertpos = false;

		if (is_array($referenced) || $referenced instanceof \Countable ? count($referenced) : 0)
		{
			foreach ($referenced as $referenced_table)
			{
				if (is_array($this->tables) || $this->tables instanceof \Countable ? count($this->tables) : 0)
				{
					$newpos = array_search($referenced_table, $this->tables);

					if ($newpos !== false)
					{
						if ($insertpos === false)
						{
							$insertpos = $newpos;
						}
						else
						{
							$insertpos = max($insertpos, $newpos);
						}
					}
				}
			}
		}

		// Add to the tables array
		if ((is_array($this->tables) || $this->tables instanceof \Countable ? count($this->tables) : 0) && ($insertpos !== false))
		{
			array_splice($this->tables, $insertpos + 1, 0, $tableName);
		}
		else
		{
			$this->tables[] = $tableName;
		}

		// Here's what... Some other table/view might depend on us, so we must appear
		// before it (actually, it must appear after us). So, we scan for such
		// tables/views and relocate them
		if (is_array($this->dependencies) || $this->dependencies instanceof \Countable ? count($this->dependencies) : 0)
		{
			if (array_key_exists($tableName, $this->dependencies))
			{
				foreach ($this->dependencies[$tableName] as $depended_table)
				{
					// First, make sure that either there is no stack, or the
					// depended table doesn't belong it. In any other case, we
					// were fooled to follow an endless dependency loop and we
					// will simply bail out and let the user sort things out.
					if (count($stack) > 0)
					{
						if (in_array($depended_table, $stack))
						{
							continue;
						}
					}

					$my_position     = array_search($tableName, $this->tables);
					$remove_position = array_search($depended_table, $this->tables);

					if (($remove_position !== false) && ($remove_position < $my_position))
					{
						$stack[] = $tableName;
						array_splice($this->tables, $remove_position, 1);

						// Where should I put the other table/view now? Don't tell me.
						// I have to recurse...
						if ($currentRecursionDepth < 19)
						{
							$this->push_table($depended_table, $stack, ++$currentRecursionDepth);
						}
						else
						{
							// We're hitting a circular dependency. We'll add the removed $depended_table
							// in the penultimate position of the table and cross our virtual fingers...
							array_splice($this->tables, (is_array($this->tables) || $this->tables instanceof \Countable ? count($this->tables) : 0) - 1, 0, $depended_table);
						}
					}
				}
			}
		}
	}

	/**
	 * Detect the auto_increment field of the table being currently backed up.
	 *
	 * This method populates the $this->table_autoincrement array.
	 *
	 * @return  void
	 */
	protected function setAutoIncrementInfo()
	{
		$this->table_autoincrement = [
			'table' => $this->nextTable,
			'field' => null,
			'value' => null,
		];

		$db = $this->getDB();

		$query   = 'SHOW COLUMNS FROM ' . $db->qn($this->nextTable) . ' WHERE ' . $db->qn('Extra') . ' = ' .
		           $db->q('auto_increment') . ' AND ' . $db->qn('Null') . ' = ' . $db->q('NO');
		$keyInfo = $db->setQuery($query)->loadAssocList();

		if (!empty($keyInfo))
		{
			$row                                = array_shift($keyInfo);
			$this->table_autoincrement['field'] = $row['Field'];
		}
	}

	/**
	 * Performs one more step of dumping database data
	 *
	 * @return  void
	 *
	 * @throws  QueryException
	 * @throws  Exception
	 */
	protected function stepDatabaseDump(): void
	{
		// Initialize local variables
		$db = $this->getDB();

		if (!is_object($db) || ($db === false))
		{
			throw new RuntimeException(__CLASS__ . '::_run() Could not connect to database?!');
		}

		$outData = ''; // Used for outputting INSERT INTO commands

		$this->enforceSQLCompatibility(); // Apply MySQL compatibility option

		// Touch SQL dump file
		$nada = "";
		$this->writeline($nada);

		// Get this table's information
		$tableName = $this->nextTable;
		$this->setStep($tableName);
		$this->setSubstep('');
		$tableAbstract = trim($this->table_name_map[$tableName]);
		$dump_records  = $this->tables_data[$tableName]['dump_records'];

		// Restore any previously information about the largest query we had to run
		$this->largest_query = Factory::getConfiguration()->get('volatile.database.largest_query', 0);

		// If it is the first run, find number of rows and get the CREATE TABLE command
		if ($this->nextRange == 0)
		{
			$outCreate = '';

			if (is_array($this->tables_data[$tableName]))
			{
				if (array_key_exists('create', $this->tables_data[$tableName]))
				{
					$outCreate = $this->tables_data[$tableName]['create'];
				}
			}

			if (empty($outCreate) && !empty($tableName))
			{
				// The CREATE command wasn't cached. Time to create it. The $type and $dependencies
				// variables will be thrown away.
				$type         = $this->tables_data[$tableName]['type'] ?? 'table';
				$dependencies = [];
				$outCreate    = $this->getCreateStatement($tableAbstract, $tableName, $type, $dependencies);
			}

			// Create drop statements if required (the key is defined by the scripting engine)
			if (!empty($outCreate) && Factory::getEngineParamsProvider()->getScriptingParameter('db.dropstatements', 0))
			{
				if (array_key_exists('create', $this->tables_data[$tableName]))
				{
					$dropStatement = $this->createDrop($this->tables_data[$tableName]['create']);
				}
				else
				{
					$type            = 'table';
					$createStatement = $this->getCreateStatement($tableAbstract, $tableName, $type, $dependencies);
					$dropStatement   = $this->createDrop($createStatement);
				}

				if (!empty($dropStatement))
				{
					$dropStatement .= "\n";

					if (!$this->writeDump($dropStatement, true))
					{
						return;
					}
				}
			}

			/**
			 * If we have a PROCEDURE, FUNCTION or TRIGGER and we are doing a SQL export meant to be run directly by
			 * MySQL (the scripting db.delimiterstatements flag is set to 1) we need to surround the CREATE statement
			 * with DELIMITER $$ commands.
			 */
			if (
				!empty($outCreate) &&
				(Factory::getEngineParamsProvider()->getScriptingParameter('db.delimiterstatements', 0) == 1)
				&& in_array($this->tables_data[$tableName]['type'], ['trigger', 'function', 'event', 'procedure'])
			)
			{
				$outCreate = rtrim($outCreate, ";\n");
				$outCreate = "DELIMITER $$\n$outCreate$$\nDELIMITER ;\n";
			}

			// Write the CREATE command after any DROP command which might be necessary.
			if (!empty($outCreate) && !$this->writeDump($outCreate, true))
			{
				return;
			}

			if (!empty($outCreate) && $dump_records)
			{
				// We are dumping data from a table, get the row count
				$this->getRowCount($tableAbstract);

				// If we can't get the row count we cannot back up this table's data
				if (is_null($this->maxRange))
				{
					$dump_records = false;
				}
			}
			elseif (!$dump_records)
			{
				/**
				 * Do NOT move this line to the if-block below. We need to only log this message on tables which are
				 * filtered, not on tables we simply cannot get the row count information for!
				 */
				Factory::getLog()->info("Skipping dumping data of " . $tableAbstract);
			}

			// The table is either filtered or we cannot get the row count. Either way we should not dump any data.
			if (!$dump_records || empty($outCreate))
			{
				$this->maxRange  = 0;
				$this->nextRange = 1;
				$outData         = '';
				$numRows         = 0;
				$dump_records    = false;
			}

			// Output any data preamble commands, e.g. SET IDENTITY_INSERT for SQL Server
			if ($dump_records && Factory::getEngineParamsProvider()->getScriptingParameter('db.dropstatements', 0))
			{
				Factory::getLog()->debug("Writing data dump preamble for " . $tableAbstract);
				$preamble = $this->getDataDumpPreamble($tableAbstract, $tableName, $this->maxRange);

				if (!empty($preamble))
				{
					if (!$this->writeDump($preamble, true))
					{
						return;
					}
				}
			}

			// Get the table's auto increment information
			if ($dump_records)
			{
				$this->setAutoIncrementInfo();
			}
		}

		// Load the active database root
		$configuration = Factory::getConfiguration();
		$dbRoot        = $configuration->get('volatile.database.root', '[SITEDB]');

		// Get the default and the current (optimal) batch size
		$defaultBatchSize = $this->getDefaultBatchSize();
		$batchSize        = $configuration->get('volatile.database.batchsize', $defaultBatchSize);

		// Check if we have more work to do on this table
		if (($this->nextRange < $this->maxRange))
		{
			$timer = Factory::getTimer();

			// Get the number of rows left to dump from the current table
			$columns         = $this->getSelectColumns($tableAbstract);
			$columnTypes     = $this->getColumnTypes($tableAbstract);
			$columnsForQuery = is_array($columns) ? array_map([$db, 'qn'], $columns) : $columns;
			$sql             = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->select($columnsForQuery)
				->from($db->nameQuote($tableAbstract));

			if (!is_null($this->table_autoincrement['field']))
			{
				$sql->order($db->qn($this->table_autoincrement['field']) . ' ASC');
			}

			if ($this->nextRange == 0)
			{
				// Get the optimal batch size for this table and save it to the volatile data
				$batchSize = $this->getOptimalBatchSize($tableAbstract, $defaultBatchSize);
				$configuration->set('volatile.database.batchsize', $batchSize);

				// First run, get a cursor to all records
				$db->setQuery($sql, 0, $batchSize);
				Factory::getLog()->info("Beginning dump of " . $tableAbstract);
				Factory::getLog()->debug("Up to $batchSize records will be read at once.");
			}
			else
			{
				// Subsequent runs, get a cursor to the rest of the records
				$this->setSubstep($this->nextRange . ' / ' . $this->maxRange);

				// If we have an auto_increment value and the table has over $batchsize records use the indexed select instead of a plain limit
				if (!is_null($this->table_autoincrement['field']) && !is_null($this->table_autoincrement['value']))
				{
					Factory::getLog()
						->info("Continuing dump of " . $tableAbstract . " from record #{$this->nextRange} using auto_increment column {$this->table_autoincrement['field']} and value {$this->table_autoincrement['value']}");
					$sql->where($db->qn($this->table_autoincrement['field']) . ' > ' . $db->q($this->table_autoincrement['value']));
					$db->setQuery($sql, 0, $batchSize);
				}
				else
				{
					Factory::getLog()
						->info("Continuing dump of " . $tableAbstract . " from record #{$this->nextRange}");
					$db->setQuery($sql, $this->nextRange, $batchSize);
				}
			}

			$this->query  = '';
			$numRows      = 0;
			$use_abstract = Factory::getEngineParamsProvider()->getScriptingParameter('db.abstractnames', 1);

			$filters            = Factory::getFilters();
			$mustFilterRows     = $filters->hasFilterType('dbobject', 'children');
			$mustFilterContents = $filters->canFilterDatabaseRowContent();

			try
			{
				$cursor = $db->query();
			}
			catch (Exception $exc)
			{
				// Issue a warning about the failure to dump data
				$errno = $exc->getCode();
				$error = $exc->getMessage();
				Factory::getLog()->warning("Failed dumping $tableAbstract from record #{$this->nextRange}. MySQL error $errno: $error");

				// Reset the database driver's state (we will try to dump other tables anyway)
				$db->resetErrors();
				$cursor = null;

				// Mark this table as done since we are unable to dump it.
				$this->nextRange = $this->maxRange;
			}

			$statsTableAbstract = Platform::getInstance()->tableNameStats;

			while (is_array($myRow = $db->fetchAssoc()) && ($numRows < ($this->maxRange - $this->nextRange)))
			{
				if ($this->createNewPartIfRequired() == false)
				{
					/**
					 * When createNewPartIfRequired returns false it means that we have began adding a SQL part to the
					 * backup archive but it hasn't finished. If we don't return here, the code below will keep adding
					 * data to that dump file. Yes, despite being closed. When you call writeDump the file is reopened.
					 * As a result of writing data of length Y, the file that had a size X now has a size of X + Y. This
					 * means that the loop in BaseArchiver which tries to add it to the archive will never see its End
					 * Of File since we are trying to resume the backup from *beyond* the file position that was
					 * recorded as the file size. The archive can detect a file shrinking but not a file growing!
					 * Therefore we hit an infinite loop a.k.a. runaway backup.
					 */
					return;
				}

				$numRows++;
				$numOfFields = is_array($myRow) || $myRow instanceof \Countable ? count($myRow) : 0;

				// On MS SQL Server there's always a RowNumber pseudocolumn added at the end, screwing up the backup (GRRRR!)
				if ($db->getDriverType() == 'mssql')
				{
					$numOfFields--;
				}

				// If row-level filtering is enabled, please run the filtering
				if ($mustFilterRows)
				{
					$isFiltered = $filters->isFiltered(
						[
							'table' => $tableAbstract,
							'row'   => $myRow,
						],
						$dbRoot,
						'dbobject',
						'children'
					);

					if ($isFiltered)
					{
						// Update the auto_increment value to avoid edge cases when the batch size is one
						if (!is_null($this->table_autoincrement['field']) && isset($myRow[$this->table_autoincrement['field']]))
						{
							$this->table_autoincrement['value'] = $myRow[$this->table_autoincrement['field']];
						}

						continue;
					}
				}

				if ($mustFilterContents)
				{
					$filters->filterDatabaseRowContent($dbRoot, $tableAbstract, $myRow);
				}

				if (
					(!$this->extendedInserts) || // Add header on simple INSERTs, or...
					($this->extendedInserts && empty($this->query)) //...on extended INSERTs if there are no other data, yet
				)
				{
					$newQuery  = true;
					$fieldList = $this->getFieldListSQL($columns);

					if ($numOfFields > 0)
					{
						$this->query = "INSERT INTO " . $db->nameQuote((!$use_abstract ? $tableName : $tableAbstract)) . " {$fieldList} VALUES \n";
					}
				}
				else
				{
					// On other cases, just mark that we should add a comma and start a new VALUES entry
					$newQuery = false;
				}

				$outData = '(';

				// Step through each of the row's values
				$fieldID = 0;

				// Used in running backup fix
				$isCurrentBackupEntry = false;

				// Fix 1.2a - NULL values were being skipped
				if ($numOfFields > 0)
				{
					foreach ($myRow as $fieldName => $value)
					{
						// The ID of the field, used to determine placement of commas
						$fieldID++;

						if ($fieldID > $numOfFields)
						{
							// This is required for SQL Server backups, do NOT remove!
							continue;
						}

						// Fix 2.0: Mark currently running backup as successful in the DB snapshot
						if ($tableAbstract == $statsTableAbstract)
						{
							if ($fieldID == 1)
							{
								// Compare the ID to the currently running
								$statistics           = Factory::getStatistics();
								$isCurrentBackupEntry = ($value == $statistics->getId());
							}
							elseif ($fieldID == 6)
							{
								// Treat the status field
								$value = $isCurrentBackupEntry ? 'complete' : $value;
							}
						}

						// Post-process the value
						if (is_null($value))
						{
							$outData .= "NULL"; // Cope with null values
						}
						else
						{
							// Accommodate for runtime magic quotes
							if (function_exists('get_magic_quotes_runtime'))
							{
								$value = @get_magic_quotes_runtime() ? stripslashes($value) : $value;
							}

							switch ($columnTypes[$fieldName] ?? '')
							{
								// Hex encode spatial data
								case 'GEOMETRY':
								case 'POINT':
								case 'LINESTRING':
								case 'POLYGON':
								case 'MULTIPOINT':
								case 'MULTILINESTRING':
								case 'MULTIPOLYGON':
								case 'GEOMETRYCOLLECTION':
									$hexEncoded = bin2hex($value);
									$value      = "x'$hexEncoded'";
									break;

								default:
									$value = $db->quote($value);
									break;
							}

							if ($this->postProcessValues)
							{
								$value = $this->postProcessQuotedValue($value);
							}

							$outData .= $value;
						}

						if ($fieldID < $numOfFields)
						{
							$outData .= ', ';
						}
					}
				}

				$outData .= ')';

				if ($numOfFields)
				{
					// If it's an existing query and we have extended inserts
					if ($this->extendedInserts && !$newQuery)
					{
						// Check the existing query size
						$query_length = strlen($this->query);
						$data_length  = strlen($outData);

						if (($query_length + $data_length) > $this->packetSize)
						{
							// We are about to exceed the packet size. Write the data so far.
							$this->query .= ";\n";

							if (!$this->writeDump($this->query, true))
							{
								return;
							}

							// Then, start a new query
							$fieldList = $this->getFieldListSQL($columns);

							$this->query = '';
							$this->query = "INSERT INTO " . $db->nameQuote((!$use_abstract ? $tableName : $tableAbstract)) . " {$fieldList} VALUES \n";
							$this->query .= $outData;
						}
						else
						{
							// We have room for more data. Append $outData to the query.
							$this->query .= ",\n";
							$this->query .= $outData;
						}
					}
					// If it's a brand new insert statement in an extended INSERTs set
					elseif ($this->extendedInserts && $newQuery)
					{
						// Append the data to the INSERT statement
						$this->query .= $outData;
						// Let's see the size of the dumped data...
						$query_length = strlen($this->query);

						if ($query_length >= $this->packetSize)
						{
							// This was a BIG query. Write the data to disk.
							$this->query .= ";\n";

							if (!$this->writeDump($this->query, true))
							{
								return;
							}

							// Then, start a new query
							$this->query = '';
						}
					}
					// It's a normal (not extended) INSERT statement
					else
					{
						// Append the data to the INSERT statement
						$this->query .= $outData;
						// Write the data to disk.
						$this->query .= ";\n";

						if (!$this->writeDump($this->query, true))
						{
							return;
						}

						// Then, start a new query
						$this->query = '';
					}
				}

				$outData = '';

				// Update the auto_increment value to avoid edge cases when the batch size is one
				if (!is_null($this->table_autoincrement['field']))
				{
					$this->table_autoincrement['value'] = $myRow[$this->table_autoincrement['field']];
				}

				unset($myRow);

				// Check for imminent timeout
				if ($timer->getTimeLeft() <= 0)
				{
					Factory::getLog()
						->debug("Breaking dump of $tableAbstract after $numRows rows; will continue on next step");

					break;
				}
			}

			$db->freeResult($cursor);

			// Advance the _nextRange pointer
			$this->nextRange += ($numRows != 0) ? $numRows : 1;

			$this->setStep($tableName);
			$this->setSubstep($this->nextRange . ' / ' . $this->maxRange);
		}

		// Finalize any pending query
		// WARNING! If we do not do that now, the query will be emptied in the next operation and all
		// accumulated data will go away...
		if (!empty($this->query))
		{
			$this->query .= ";\n";

			if (!$this->writeDump($this->query, true))
			{
				return;
			}

			$this->query = '';
		}

		// Check for end of table dump (so that it happens inside the same operation)
		if ($this->nextRange >= $this->maxRange)
		{
			// Tell the user we are done with the table
			Factory::getLog()->debug("Done dumping " . $tableAbstract);

			// Output any data preamble commands, e.g. SET IDENTITY_INSERT for SQL Server
			if ($dump_records && Factory::getEngineParamsProvider()->getScriptingParameter('db.dropstatements', 0))
			{
				Factory::getLog()->debug("Writing data dump epilogue for " . $tableAbstract);
				$epilogue = $this->getDataDumpEpilogue($tableAbstract, $tableName, $this->maxRange);

				if (!empty($epilogue))
				{
					if (!$this->writeDump($epilogue, true))
					{
						return;
					}
				}
			}

			if ((is_array($this->tables) || $this->tables instanceof \Countable ? count($this->tables) : 0) == 0)
			{
				// We have finished dumping the database!
				Factory::getLog()->info("End of database detected; flushing the dump buffers...");
				$this->writeDump(null);
				Factory::getLog()->info("Database has been successfully dumped to SQL file(s)");
				$this->setState(self::STATE_POSTRUN);
				$this->setStep('');
				$this->setSubstep('');
				$this->nextTable = '';
				$this->nextRange = 0;

				/**
				 * At the end of the database dump, if any query was longer than 1Mb, let's put a warning file in the
				 * installation folder, but ONLY if the backup is not a SQL-only backup (which has no backup archive).
				 */
				$isSQLOnly = $configuration->get('akeeba.basic.backup_type') == 'dbonly';

				if (!$isSQLOnly && ($this->largest_query >= 1024 * 1024))
				{
					$archive = Factory::getArchiverEngine();
					$archive->addFileVirtual('large_tables_detected', $this->installerSettings->installerroot, $this->largest_query);
				}
			}
			elseif ((is_array($this->tables) || $this->tables instanceof \Countable ? count($this->tables) : 0) != 0)
			{
				// Switch tables
				$this->nextTable = array_shift($this->tables);
				$this->nextRange = 0;
				$this->setStep($this->nextTable);
				$this->setSubstep('');
			}
		}
	}

	/** @inheritDoc */
	protected function getAllTables(): array
	{
		// Get a database connection
		$db = $this->getDB();

		$this->enforceSQLCompatibility();

		$sql = 'SHOW TABLES';
		$db->setQuery($sql);

		return $db->loadColumn() ?: [];
	}

	/**
	 * Get the MySQL entity type given a `CREATE <something>` SQL query.
	 *
	 * @param   string  $query
	 *
	 * @return  string|null
	 */
	private function getTypeFromCreateQuery(string $query): ?string
	{
		if (substr($query, 0, 7) !== 'CREATE ')
		{
			return null;
		}

		foreach (['TABLE', 'VIEW', 'PROCEDURE', 'EVENT', 'FUNCTION', 'TRIGGER'] as $entity)
		{
			if (strpos($query, ' ' . $entity . ' ', 7))
			{
				return strtolower($entity);
			}
		}

		return null;
	}

	/**
	 * Generate metadata for TABLEs and VIEWs
	 *
	 * @return  void
	 * @throws  Exception
	 */
	private function generateMetadataForTables(): void
	{
		$db = $this->getDB();

		$sql = 'SHOW TABLES';
		$db->setQuery($sql);
		$allTableNames = $db->loadResultArray(0);

		Factory::getLog()->debug(__CLASS__ . " :: Got SHOW TABLES");

		// Get filters and filter root
		$registry = Factory::getConfiguration();
		$root     = $registry->get('volatile.database.root', '[SITEDB]');
		$filters  = Factory::getFilters();

		foreach ($allTableNames as $tableName)
		{
			// Skip over tables not included in the backup set
			if (!array_key_exists($tableName, $this->table_name_map))
			{
				continue;
			}

			// Basic information
			$abstractName = $this->table_name_map[$tableName];
			$newEntry     = [
				'type'         => 'table',
				'dump_records' => true,
			];

			// Get the CREATE command
			$dependencies       = [];
			$newEntry['create'] = $this->getCreateStatement($abstractName, $tableName, $newEntry['type'], $dependencies);

			if ($newEntry['create'] === null)
			{
				continue;
			}

			$newEntry['dependencies'] = $dependencies;

			if ($newEntry['type'] === 'view')
			{
				$newEntry['dump_records'] = false;
			}

			// Scan for the table engine.
			if ($newEntry['type'] === 'table')
			{
				switch ($this->getTableEngineFromCreateStatement($newEntry['create']))
				{
					// Merge tables
					case 'MRG_MYISAM':
						$newEntry['type']         = 'merge';
						$newEntry['dump_records'] = false;

						break;

					// Tables whose data we do not back up (memory, federated and can-have-no-data tables)
					case 'MEMORY':
					case 'EXAMPLE':
					case 'BLACKHOLE':
					case 'FEDERATED':
						$newEntry['dump_records'] = false;

						break;

					// Normal tables
					default:
						break;
				}
			}

			// Table Data Filter: skip dumping table contents of filtered out tables.
			if ($filters->isFiltered($abstractName, $root, 'dbobject', 'content'))
			{
				$newEntry['dump_records'] = false;
			}

			$this->tables_data[$tableName] = $newEntry;
		}

		Factory::getLog()->debug(__CLASS__ . " :: Got table list");
	}

	/**
	 * Generate metadata for PROCEDUREs, EVENTs, FUNCTIONs, or TRIGGERs
	 *
	 * @param   string  $type  The entity type to generate metadata for
	 *
	 * @return  void
	 * @throws  Exception
	 */
	private function generateMetadataForEntity(string $type): void
	{
		$db = $this->getDB();

		switch ($type)
		{
			case 'event':
				$sql    = 'SHOW EVENTS WHERE `Db`=' . $db->quote($this->database);
				$offset = 1;
				break;

			case 'trigger':
				$sql    = 'SHOW TRIGGERS IN ' . $db->quoteName($this->database);
				$offset = 0;
				break;

			default:
				$sql    = 'SHOW ' . $type . ' STATUS WHERE `Db`=' . $db->quote($this->database);
				$offset = 1;
				break;
		}

		$db->setQuery($sql);

		try
		{
			$metadata_list = $db->loadRowList();
		}
		catch (Exception $e)
		{
			return;
		}

		if (!is_countable($metadata_list) || !count($metadata_list))
		{
			return;
		}

		foreach ($metadata_list as $entity_metadata)
		{
			$entity_name     = $entity_metadata[$offset];

			// Skip over entities not included in the backup set
			if (!array_key_exists($entity_name, $this->table_name_map))
			{
				continue;
			}

			// Basic information
			$entity_abstract = $this->table_name_map[$entity_name];
			$new_entry       = [
				'type'         => $type,
				'dump_records' => false,
			];

			$dependencies        = [];
			$new_entry['create'] = $this->getCreateStatement(
				$entity_abstract, $entity_name, $new_entry['type'], $dependencies
			);

			if ($new_entry['create'] === null)
			{
				continue;
			}

			$new_entry['dependencies']       = $dependencies;

			$this->tables_data[$entity_name] = $new_entry;
		}
	}

	/**
	 * Figure out the table's data storage engine given its CREATE statement.
	 *
	 * @param   string  $createSql  The CREATE statement for the table.
	 *
	 * @return  string
	 */
	private function getTableEngineFromCreateStatement(string $createSql): string
	{
		$engine      = 'MyISAM'; // So that even with MySQL 4 hosts we don't screw this up
		$engine_keys = ['ENGINE=', 'TYPE='];

		foreach ($engine_keys as $engine_key)
		{
			$start_pos = strrpos($createSql, $engine_key);

			if ($start_pos === false)
			{
				continue;
			}

			// Advance the start position just after the position of the ENGINE keyword
			$start_pos += strlen($engine_key);
			// Try to locate the space after the engine type
			$end_pos = stripos($createSql, ' ', $start_pos);

			if ($end_pos === false)
			{
				// Uh... maybe it ends with ENGINE=EngineType;
				$end_pos = stripos($createSql, ';', $start_pos);
			}

			if ($end_pos !== false)
			{
				// Grab the string
				$engine = substr($createSql, $start_pos, $end_pos - $start_pos);
			}
		}

		return strtoupper($engine);
	}

	/**
	 * Does the entity name include a newline or carriage return?
	 *
	 * These entity names are rarely, if ever, legitimate. They are likely to trigger the MySQL vulnerability
	 * CVE-2017-3600 (“Bad Dump”). As a result, they will be excluded from the database dump.
	 *
	 * @param   string  $type  The entity type: table, merge, view, procedure, event, function, or trigger.
	 * @param   string  $name  The name of the entity to process.
	 *
	 * @return  bool
	 */
	private function isCVEBadDump(string $type, string $name): bool
	{
		if ((strpos($name, "\r") === false) && (strpos($name, "\n") === false))
		{
			return false;
		}

		$name = str_replace(["\r", "\n"], ['\\r', '\\n'], $name);

		Factory::getLog()->warning(
			sprintf(
				"%s :: [SECURITY] %s %s includes newline characters. Skipping table to protect you against possible MySQL vulnerability CVE-2017-3600 (“Bad Dump”).",
				__CLASS__,
				ucfirst($type),
				$name
			)
		);

		return true;
	}

	/**
	 * Does the entity name start with the literal prefix `bak_`?
	 *
	 * These are backup copies of tables, views, etc created during a previous restoration. They will be automatically
	 * skipped from the backup to avoid restoration woes.
	 *
	 * @param   string  $type  The entity type: table, merge, view, procedure, event, function, or trigger.
	 * @param   string  $name  The name of the entity to process.
	 *
	 * @return  bool
	 */
	private function isBackupPrefix(string $type, string $name): bool
	{
		if (substr($name, 0, 4) != 'bak_')
		{
			return false;
		}

		Factory::getLog()->info(
			sprintf(
				"%s :: Backup %s %s automatically skipped.",
				__CLASS__,
				ucfirst($type),
				$name
			)
		);

		return true;
	}

	/**
	 * Does the entity name in the database start with the literal prefix `#__`?
	 *
	 * If this is the case, the entity will be automatically skipped from the backup as the literal prefix `#__` will
	 * clash with our abstract naming pattern during restoration.
	 *
	 * @param   string  $type  The entity type: table, merge, view, procedure, event, function, or trigger.
	 * @param   string  $name  The name of the entity to process.
	 *
	 * @return  bool
	 */
	private function isAbstractNamedInDatabase(string $type, string $name): bool
	{
		if (substr($name, 0, 3) !== '#__')
		{
			return false;
		}

		Factory::getLog()->warning(
			sprintf(
				"%s :: %s %s has a prefix of #__. This would cause restoration errors; table skipped.", __CLASS__,
				ucfirst($type),
				$name
			)
		);

		return true;
	}

	/**
	 * Generate the concrete to abstract map for TABLEs / VIEWs, PROCEDUREs, EVENTs, FUNCTIONs, or TRIGGERs.
	 *
	 * @param   string  $type  The entity type: table, procedire, event, function, trigger
	 *
	 * @return  void
	 * @throws  Exception
	 */
	private function generateMappingForEntities(string $type = 'procedure'): void
	{
		$db       = $this->getDB();
		$registry = Factory::getConfiguration();
		$filters  = Factory::getFilters();
		$root     = $registry->get('volatile.database.root', '[SITEDB]');

		Factory::getLog()->debug(
			sprintf("%s :: Listing stored %ss", __CLASS__, strtoupper($type))
		);

		switch ($type)
		{
			case 'table':
				$sql = 'SHOW TABLES';
				$offset = 0;
				break;

			case 'event':
				$sql = "SHOW EVENTS WHERE `Db`=" . $db->quote($this->database);
				$offset = 1;
				break;

			case 'trigger':
				$sql    = 'SHOW TRIGGERS IN ' . $db->quoteName($this->database);
				$offset = 0;
				break;

			default:
				$sql = "SHOW " . $type . " STATUS WHERE `Db`=" . $db->quote($this->database);
				$offset = 1;
				break;
		}

		$db->setQuery($sql);

		try
		{
			$allNames = $db->loadResultArray($offset);
		}
		catch (Exception $e)
		{
			return;
		}

		if (!is_countable($allNames) || !count($allNames))
		{
			return;
		}

		// If we have filters, make sure the tables pass the filtering.
		foreach ($allNames as $name)
		{
			if (
				$this->isAbstractNamedInDatabase($type, $name)
				|| $this->isCVEBadDump($type, $name)
				|| $this->isBackupPrefix($type, $name)
			)
			{
				continue;
			}

			$abstract = $this->getAbstract($name);

			if ($filters->isFiltered($abstract, $root, 'dbobject', 'all'))
			{
				Factory::getLog()->info(__CLASS__ . " :: Skipping $type $name (internal name $abstract)");

				continue;
			}

			Factory::getLog()->info(__CLASS__ . " :: Adding $type $name (internal name $abstract)");

			$this->table_name_map[$name] = $abstract;
		}
	}

	/**
	 * Is the CREATE statement one which creates a VIEW?
	 *
	 * @param   string  $table_sql  The CREATE statement
	 *
	 * @return  bool
	 */
	private function isCreateView(string $table_sql): bool
	{
		return (bool) preg_match('/^CREATE(.*?) VIEW\s/i', $table_sql);
	}

	/**
	 * Pre-process the CREATE SQL command of a procedure, event, function, or trigger.
	 *
	 * Remove the definer from the CREATE PROCEDURE/EVENT/TRIGGER/FUNCTION. For example, MySQL returns this:
	 * CREATE DEFINER=`myuser`@`localhost` PROCEDURE `abc_myProcedure`() ...
	 * If you're restoring on a different machine the definer will probably be invalid, therefore we need to
	 * remove it from the (portable) output.
	 *
	 * @param   string  $table_sql  The CREATE SQL statement
	 * @param   string  $type       The entity type: procedure, event, function, or trigger
	 *
	 * @return  string
	 * @throws  Exception
	 */
	private function preProcessCreateSQLForEntity(string $table_sql, string $type): string
	{
		$db = $this->getDB();

		// MySQL adds the database name into everything. We have to remove it.
		$dbName    = $db->qn($this->database) . '.`';
		$table_sql = str_replace($dbName, '`', $table_sql);

		// These can contain comment lines, starting with a double dash. Remove them.
		$table_sql = trim($table_sql);

		/**
		 * Remember, $table_sql may be multiline.
		 *
		 * Therefore, we need to process only the first line and append any further lines to the CREATE statement.
		 */
		$table_sql = trim($table_sql);
		$lines     = explode("\n", $table_sql);
		$firstLine = array_shift($lines);
		$pattern   = '/^CREATE(.*?) ' . strtoupper($type) . ' (.*)/i';
		$result    = preg_match($pattern, $firstLine, $matches);
		$table_sql = 'CREATE ' . strtoupper($type) . ' ' . $matches[2] . "\n" . implode("\n", $lines);
		$table_sql = trim($table_sql);

		return $table_sql;
	}

	/**
	 * Pre-process the CREATE VIEW command.
	 *
	 * Newer MySQL versions add the definer and other information in the CREATE VIEW output, e.g.
	 * CREATE ALGORITHM=UNDEFINED DEFINER=`muyser`@`localhost` SQL SECURITY DEFINER VIEW `abc_myview` AS ...
	 * We need to remove that to prevent restoration troubles.
	 *
	 * @param   string  $table_sql  The CREATE VIEW SQL statement
	 *
	 * @return  string
	 */
	private function preProcessCreateSQLForView(string $table_sql): string
	{
		preg_match('/^CREATE(.*?) VIEW (.*)/i', $table_sql, $matches);

		return 'CREATE VIEW ' . $matches[2];
	}

	/**
	 * Pre-process the CREATE TABLE command.
	 *
	 * This method addresses a lot of cross-server issues which may arise by using several not-so-important MYSQL and
	 * MariaDB features in tables such as:
	 * - Using BTREE / HASH statements for indices.
	 * - Tablespaces.
	 * - DATA/INDEX DIRECTORY.
	 * - ROW_FORMAT in InnoDB tables.
	 * - PAGE_CHECKSUM in MariaDB's MyISAM.
	 * - References to foreign tables in constraints and indices.
	 *
	 * @param   string  $table_sql  The CREATE VIEW SQL statement
	 *
	 * @return  string
	 */
	private function preProcessCreateSQLForTable(string $table_sql): string
	{
		// USING BTREE / USING HASH in indices causes issues migrating from MySQL 5.1+ hosts to MySQL 5.0 hosts
		if (Factory::getConfiguration()->get('engine.dump.native.nobtree', 1))
		{
			$table_sql = str_replace(' USING BTREE', ' ', $table_sql);
			$table_sql = str_replace(' USING HASH', ' ', $table_sql);
		}

		// Translate TYPE= to ENGINE=
		$table_sql = str_replace('TYPE=', 'ENGINE=', $table_sql);

		/**
		 * Remove the TABLESPACE option.
		 *
		 * The format of the TABLESPACE table option is:
		 * TABLESPACE tablespace_name [STORAGE {DISK|MEMORY}]
		 * where tablespace_name can be a quoted or unquoted identifier.
		 */
		[$validCharRegEx, $unicodeFlag] = $this->getMySQLIdentifierCharacterRegEx();
		$tablespaceName = "((($validCharRegEx){1,})|(`.*`))";
		$suffix         = 'STORAGE\s{1,}(DISK|MEMORY)';
		$regex          = "#TABLESPACE\s{1,}$tablespaceName\s{0,}($suffix){0,1}#i" . $unicodeFlag;
		$table_sql      = preg_replace($regex, '', $table_sql);

		// Remove table options {DATA|INDEX} DIRECTORY
		$regex     = "#(DATA|INDEX)\s{1,}DIRECTORY\s*=?\s*'.*'#i";
		$table_sql = preg_replace($regex, '', $table_sql);

		// Remove table options ROW_FORMAT=whatever
		$regex     = "#ROW_FORMAT\s*=\s*[A-Z]{1,}#i";
		$table_sql = preg_replace($regex, '', $table_sql);

		// Remove MariaDB MyISAM option PAGE_CHECKSUM
		$regex     = "#PAGE_CHECKSUM\s*=\s*[\d]{1,}#i";
		$table_sql = preg_replace($regex, '', $table_sql);

		// Abstract the names of table constraints and indices
		$regex     = "#(CONSTRAINT|KEY|INDEX)\s{1,}`{$this->prefix}#i";
		$table_sql = preg_replace($regex, '$1 `#__', $table_sql);

		return $table_sql;
	}

	/**
	 * Post-process the CREATE command for a view, table, procedure, event, function, or trigger.
	 *
	 * @param   string  $table_sql
	 * @param   string  $type
	 *
	 * @return  string
	 * @throws  Exception
	 */
	private function postProcessCreateSQL(string $table_sql, string $type): string
	{
		$db  = $this->getDB();

		// Add a final semicolon and newline character
		$table_sql = rtrim($table_sql);
		$table_sql = rtrim($table_sql, ';');
		$table_sql .= ";\n";

		/**
		 * Views, procedures, functions and triggers may contain the database name followed by the table name, always
		 * quoted e.g. `db`.`table_name`  We need to replace all these instances with just the table name. The only
		 * reliable way to do that is to look for "`db`.`" and replace it with "`"
		 */
		if (in_array($type, ['view', 'procedure', 'function', 'trigger', 'event']))
		{
			$dbName      = $db->qn($this->getDatabaseName());
			$dummyQuote  = $db->qn('foo');
			$findWhat    = $dbName . '.' . substr($dummyQuote, 0, 1);
			$replaceWith = substr($dummyQuote, 0, 1);
			$table_sql   = str_replace($findWhat, $replaceWith, $table_sql);
		}

		// Post-process CREATE VIEW
		if ($type == 'view')
		{
			$pos_view = strpos($table_sql, ' VIEW ');

			if ($pos_view > 7)
			{
				// Only post process if there are view properties between the CREATE and VIEW keywords
				// -- Properties string
				$propstring = substr($table_sql, 7, $pos_view - 7);
				// -- Fetch the ALGORITHM={UNDEFINED | MERGE | TEMPTABLE} keyword
				$algostring = '';
				$algo_start = strpos($propstring, 'ALGORITHM=');

				if ($algo_start !== false)
				{
					$algo_end   = strpos($propstring, ' ', $algo_start);
					$algostring = substr($propstring, $algo_start, $algo_end - $algo_start + 1);
				}

				// Create our modified create statement
				return 'CREATE OR REPLACE ' . $algostring . substr($table_sql, $pos_view);
			}
		}

		$pos_entity = stripos($table_sql, sprintf(" %s ", strtoupper($type)));

		if ($pos_entity !== false)
		{
			$table_sql = 'CREATE' . substr($table_sql, $pos_entity);
		}

		return $table_sql;
	}
}