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

use Akeeba\Engine\Dump\Base;
use Akeeba\Engine\Dump\Dependencies\Entity;
use Akeeba\Engine\Dump\Dependencies\Resolver;
use Akeeba\Engine\Dump\Native\MySQL\BadEntityNamesTrait;
use Akeeba\Engine\Dump\Native\MySQL\DropStatementTrait;
use Akeeba\Engine\Dump\Native\Pgsql\Adapter\AdapterInterface;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Util\Collection;
use Akeeba\Engine\Util\Phin;
use Countable;
use Exception;
use RuntimeException;
use Throwable;

/**
 * PostgreSQL native dump engine adapter.
 */
class Postgresql extends Base
{
	use BadEntityNamesTrait;
	use DropStatementTrait;

	/**
	 * The schema to use. Defaults to 'public'.
	 *
	 * @var   string
	 * @since 10.3
	 */
	protected $schema = 'public';

	/**
	 * The largest query encountered so far.
	 *
	 * @var   string
	 * @since 10.3
	 */
	protected $largest_query = 0;

	/**
	 * The database root filter path
	 * @var   string|null
	 * @since 10.3
	 */
	private ?string $dbRoot;

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

	/** @var Entity|null The next table or DB entity to back up */
	protected $nextTable;

	private $columnListColumnType = [];

	private $columnListSelectColumn = '*';

	private $lastTableColumnType = null;

	private $lastTableSelectColumn = null;

	private Collection $entities;

	private bool $useAbstractNames;

	private bool $mustFilterRows;

	private bool $mustFilterContents;

	private int $defaultBatchSize;

	private bool $createDropStatements;

	/**
	 * Constructor for the class.
	 *
	 * Initialises the database root directory based on configuration settings or defaults to '[SITEDB]'.
	 *
	 * @return  void
	 * @since   10.3
	 */
	public function __construct()
	{
		parent::__construct();

		$engineParams                 = Factory::getEngineParamsProvider();
		$this->useAbstractNames       = $engineParams->getScriptingParameter('db.abstractnames', 1) == 1;
		$this->createDropStatements   = $engineParams->getScriptingParameter('db.dropstatements', 0) == 1;

		$this->dbRoot = Factory::getConfiguration()->get('volatile.database.root', '[SITEDB]');

		$filters                  = Factory::getFilters();
		$this->mustFilterRows     = $filters->hasFilterType('dbobject', 'children');
		$this->mustFilterContents = $filters->canFilterDatabaseRowContent();

		$this->defaultBatchSize = $this->getDefaultBatchSize();
	}

	/**
	 * Get the current DB name, as reported by the DB server.
	 *
	 * @return  string
	 */
	protected function getDatabaseNameFromConnection(): string
	{
		try
		{
			return $this->getDB()->setQuery('SELECT current_database()')->loadResult() ?: '';
		}
		catch (Throwable $e)
		{
			return '';
		}
	}

	protected function getSchemaName(): string
	{
		if (empty($this->schema) && $this->schema !== '0')
		{
			$this->schema = $this->getSchemaNameFromConnection();
		}

		return $this->schema;

	}

	/**
	 * Get the current schema name, as reported by the DB server.
	 *
	 * @return  string
	 */
	protected function getSchemaNameFromConnection(): string
	{
		try
		{
			return $this->getDB()->setQuery('SELECT current_schema()')->loadResult() ?: '';
		}
		catch (Throwable $e)
		{
			return 'public';
		}
	}

	/**
	 * Return a list of columns to use in the SELECT query for dumping table data.
	 *
	 * This is used to filter out all generated rows.
	 *
	 * @param   string  $tableAbstract
	 *
	 * @return  string|array  An array of table columns, or the string literal '*' to quickly select all columns.
	 *
	 * @see  https://dev.mysql.com/doc/refman/5.7/en/create-table-generated-columns.html
	 */
	protected function getSelectColumns($tableAbstract)
	{
		if ($this->lastTableSelectColumn === $tableAbstract)
		{
			return $this->columnListSelectColumn;
		}

		$this->lastTableSelectColumn  = $tableAbstract;
		$this->columnListSelectColumn = '*';

		$db      = $this->getDB();

		try
		{
			$query = $db
				->createQuery(true)
				->select([
					$db->quoteName('column_name'),
					$db->quoteName('data_type'),
					$db->quoteName('is_nullable'),
					$db->quoteName('column_default'),
					$db->quoteName('is_generated'),
					$db->quoteName('is_updatable'),
				])
				->from($db->quoteName('information_schema.columns'))
				->where([
					$db->quoteName('table_catalog') . '=' . $db->quote($this->getDatabaseName()),
					$db->quoteName('table_schema') . '=' . $db->quote($this->getSchemaName()),
					$db->quoteName('table_name') . '=' . $db->quote($tableAbstract),
				]);

			$tableCols = $db->setQuery($query)->loadAssocList();
		}
		catch (Throwable $e)
		{
			return $this->columnListSelectColumn;
		}

		$totalColumns                 = empty($tableCols) ? 0 : count($tableCols);
		$this->columnListSelectColumn = [];

		foreach ($tableCols as $col)
		{
			// Skip over generated columns
			if (strtoupper(trim($col['is_generated'] ?: '')) !== 'NEVER')
			{
				continue;
			}

			$this->columnListSelectColumn[] = $col['column_name'];
		}

		if ($totalColumns === count($this->columnListSelectColumn))
		{
			$this->columnListSelectColumn = '*';
		}

		return $this->columnListSelectColumn;
	}

	/**
	 * Returns a list of all tables in the database.
	 *
	 * @return  array
	 * @since   10.3
	 */
	public function getAllTables(): array
	{
		$db    = $this->getDB();
		$query = $db->getQuery(true)
			->select('table_name')
			->from('information_schema.tables')
			->where($db->quoteName('table_schema') . '=' . $db->quote($this->schema));

		try
		{
			return $db->setQuery($query)->loadResultArray() ?: [];
		}
		catch (Throwable $e)
		{
			return [];
		}
	}

	/**
	 * Method to retrieve tables and database objects that need to be backed up.
	 *
	 * This method populates the internal entity collection with information about
	 * tables, views, functions, procedures, and triggers within the current schema. It
	 * queries the information_schema for these entities and adds them to a backup list,
	 * filtering out any errors during the retrieval process without interrupting normal
	 * execution.
	 *
	 * @return  void
	 * @since   10.3
	 */
	public function getTablesToBackup(): void
	{
		$db             = $this->getDB();
		$this->entities = new Collection();

		$this->entities = $this->entities->merge($this->getRoutinesCollection('procedure'));
		$this->entities = $this->entities->merge($this->getRoutinesCollection('function'));
		$this->entities = $this->entities->merge(
			$this->resolveDependencies($this->getTablesViewCollection())->values()
		);
		// Triggers are automatically dumped together with the table; there is no way to not dump them.
		// $this->entities = $this->entities->merge($this->getRoutinesCollection('trigger'));

		// Create a naming map
		$this->table_name_map = array_combine(
			$this->entities->map(fn(Entity $e) => $e->name)->toArray(),
			$this->entities->map(fn(Entity $e) => $e->abstractName)->toArray()
		);

		/**
		 * Store all abstract entity names (tables, views, triggers etc) into a volatile variable, so we can fetch
		 * it later when creating the databases.json file
		 */
		if ($this->installerSettings->typedtablelist ?? false)
		{
			// BRS 10.x: typed list of entities
			$typedEntityList = [];

			/** @var \Akeeba\Engine\Dump\Dependencies\Entity $entity */
			foreach ($this->entities as $entity)
			{
				$typedEntityList[$entity->getType()] ??= [];
				$typedEntityList[$entity->getType()][] = $entity->getAbstractName();
			}

			Factory::getConfiguration()->set('volatile.database.table_names', $typedEntityList);
		}
		else
		{
			// Support for legacy installers: flat list of entity names
			Factory::getConfiguration()->set('volatile.database.table_names', array_values($this->table_name_map));
		}
	}

	/**
	 * Populate the next entity to back up.
	 *
	 * @return  void
	 * @since   10.3
	 */
	protected function goToNextTable(): void
	{
		$this->nextTable = $this->entities->shift();
		$this->nextRange = 0;
	}

	/**
	 * Prepares the engine for dumping the database.
	 *
	 * @since   10.3
	 */
	protected function _prepare()
	{
		parent::_prepare();

		$this->schema = $this->_parametersArray['schema'] ?? $this->getSchemaName();

		if (empty($this->schema))
		{
			$this->schema = 'public';
		}
	}

	/**
	 * Main backup loop.
	 */
	protected function stepDatabaseDump(): void
	{
		$db = $this->getDB();
		$configuration = Factory::getConfiguration();
		$filters       = Factory::getFilters();

		if (!is_object($db) || ($db === false))
		{
			throw new RuntimeException(__CLASS__ . '::_run() Could not connect to database?!');
		}

		// Touch SQL dump file
		$nada = "";
		$this->writeline($nada);

		// Get this table's information
		$this->setStep($this->nextTable->name);
		$this->setSubstep('');

		// Restore any previously information about the largest query we had to run
		$this->largest_query = Factory::getConfiguration()->get('volatile.database.largest_query', 0);

		// If it is the first run, find number of rows and get the DDL.
		if ($this->nextRange == 0)
		{
			try
			{
				$outCreate = $this->getCreateStatement(
					$this->nextTable->abstractName, $this->nextTable->name, $this->nextTable->type, $this->createDropStatements
				);
			}
			catch (Exception $e)
			{
				$outCreate = '';
			}

			if (empty($outCreate))
			{
				Factory::getLog()->warning(
					sprintf(
						"Cannot get the CREATE statement for %s %s -- skipping", $this->nextTable->type,
						$this->nextTable->abstractName
					)
				);

				$this->nextRange = 1;
				$this->maxRange  = 0;
			}
			else
			{
				$statements = $this->splitSql($outCreate);
				$suffix = "/*ABDE:{$this->nextTable->abstractName}*/\n";

				foreach ($statements as $statement)
				{
					if (!$this->writeDump($statement . $suffix, true))
					{
						return;
					}
				}
			}


			if ($this->nextTable->dumpContents)
			{
				// We are dumping data from a table, get the row count
				$this->getRowCount($this->nextTable->abstractName);

				// If we can't get the row count we cannot back up this table's data
				if (is_null($this->maxRange))
				{
					Factory::getLog()->warning(
						sprintf(
							"Cannot get the row count for %s %s -- skipping data dump", $this->nextTable->type,
							$this->nextTable->abstractName
						)
					);

					$this->nextRange = 1;
					$this->maxRange  = 0;
				}
			}
			else
			{
				/**
				 * Do NOT move this line to the if-block below. We need to only log this message on tables which are
				 * filtered, not on tables we simply cannot get the row count information for!
				 */
				Factory::getLog()->info(
					sprintf("Skipping dumping data of %s %s", $this->nextTable->type, $this->nextTable->abstractName)
				);
			}

			// The table is either filtered, or we cannot get the row count. Either way we should not dump any data.
			if (!$this->nextTable->dumpContents)
			{
				$this->nextRange = 1;
				$this->maxRange  = 0;
			}

			// Output any data preamble commands, e.g. SET IDENTITY_INSERT for SQL Server
			if ($this->nextTable->dumpContents && $this->createDropStatements)
			{
				$preamble = $this->getDataDumpPreamble(
					$this->nextTable->abstractName, $this->nextTable->name, $this->maxRange
				);

				if (!empty($preamble))
				{
					Factory::getLog()->debug("Writing data dump preamble for " . $this->nextTable->abstractName);

					if (!$this->writeDump($preamble, true))
					{
						return;
					}
				}
			}

			$this->nextRange ??= 0;
			$this->maxRange  ??= 0;
		}

		// Get the table's auto increment information
		if ($this->nextTable->dumpContents && $this->nextRange < $this->maxRange)
		{
			$this->setAutoIncrementInfo();
		}

		// Get the default and the current (optimal) batch size
		$batchSize = $configuration->get('volatile.database.batchsize', $this->defaultBatchSize);

		// Check if we have more work to do on this table
		if ($this->nextRange < $this->maxRange)
		{
			$timer = Factory::getTimer();

			// Get the number of rows left to dump from the current table
			$columns         = $this->getSelectColumns($this->nextTable->abstractName);
			$columnTypes     = $this->getColumnTypes($this->nextTable->abstractName);
			$columnsForQuery = is_array($columns) ? array_map([$db, 'qn'], $columns) : $columns;
			$sql             = $db->getQuery(true)
				->select($columnsForQuery)
				->from($db->nameQuote($this->nextTable->abstractName));

			if (!is_null($this->table_autoincrement['field']))
			{
				$sql->order($db->qn($this->table_autoincrement['field']) . ' ASC');
			}

			if ($this->nextRange == 0)
			{
				// Get the optimal batch size for this table and save it to the volatile data
				$batchSize = $this->getOptimalBatchSize($this->nextTable->abstractName);
				$configuration->set('volatile.database.batchsize', $batchSize);

				// First run, get a cursor to all records
				$db->setQuery($sql, 0, $batchSize);
				Factory::getLog()->info("Beginning dump of " . $this->nextTable->abstractName);
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
						->info(
							"Continuing dump of " . $this->nextTable->abstractName
							. " from record #{$this->nextRange} using auto_increment column {$this->table_autoincrement['field']} and value {$this->table_autoincrement['value']}"
						);
					$sql->where(
						$db->qn($this->table_autoincrement['field']) . ' > ' . $db->q(
							$this->table_autoincrement['value']
						)
					);
					$db->setQuery($sql, 0, $batchSize);
				}
				else
				{
					Factory::getLog()
						->info(
							"Continuing dump of " . $this->nextTable->abstractName
							. " from record #{$this->nextRange}"
						);
					$db->setQuery($sql, $this->nextRange, $batchSize);
				}
			}

			$this->query = '';
			$numRows     = 0;

			try
			{
				$cursor = $db->query();
			}
			catch (Exception $exc)
			{
				// Issue a warning about the failure to dump data
				$errno = $exc->getCode();
				$error = $exc->getMessage();
				Factory::getLog()->warning(
					"Failed dumping {$this->nextTable->abstractName} from record #{$this->nextRange}. MySQL error $errno: $error"
				);

				// Reset the database driver's state (we will try to dump other tables anyway)
				$db->resetErrors();
				$cursor = null;

				// Mark this table as done since we are unable to dump it.
				$this->nextRange = $this->maxRange;
			}

			$statsTableAbstract = Platform::getInstance()->tableNameStats;

			while (is_array($myRow = $db->fetchAssoc()) && ($numRows < ($this->maxRange - $this->nextRange)))
			{
				if (!$this->createNewPartIfRequired())
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
				$numOfFields = is_array($myRow) || $myRow instanceof Countable ? count($myRow) : 0;

				// On MS SQL Server there's always a RowNumber pseudocolumn added at the end, screwing up the backup (GRRRR!)
				if ($db->getDriverType() == 'mssql')
				{
					$numOfFields--;
				}

				if ($numOfFields === 0)
				{
					Factory::getLog()->warning(
						sprintf(
							"No columns for %s %s -- skipping data dump.",
							$this->nextTable->type, $this->nextTable->abstractName
						)
					);

					$numRows = $this->maxRange - $this->nextRange;

					break;
				}

				// If row-level filtering is enabled, please run the filtering
				if ($this->mustFilterRows)
				{
					$isFiltered = $filters->isFiltered(
						[
							'table' => $this->nextTable->abstractName,
							'row'   => $myRow,
						],
						$this->dbRoot,
						'dbobject',
						'children'
					);

					if ($isFiltered)
					{
						// Update the auto_increment value to avoid edge cases when the batch size is one
						if (!is_null($this->table_autoincrement['field'])
						    && isset($myRow[$this->table_autoincrement['field']]))
						{
							$this->table_autoincrement['value'] = $myRow[$this->table_autoincrement['field']];
						}

						continue;
					}
				}

				if ($this->mustFilterContents)
				{
					$filters->filterDatabaseRowContent($this->dbRoot, $this->nextTable->abstractName, $myRow);
				}

				// Add header on simple INSERTs, or on extended INSERTs if there are no other data, yet
				$newQuery = false;

				if (
					!$this->extendedInserts || ($this->extendedInserts && empty($this->query))
				)
				{
					$newQuery  = true;
					$fieldList = $this->getFieldListSQL($columns);

					$this->query = "INSERT INTO " . $db->nameQuote(
							(!$this->useAbstractNames ? $this->nextTable->name : $this->nextTable->abstractName)
						) . " {$fieldList} VALUES \n";
				}

				$outData = '(';

				// Step through each of the row's values
				$fieldID = 0;

				// Used in running backup fix
				$isCurrentBackupEntry = false;

				// Fix 1.2a - NULL values were being skipped
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
					if ($this->nextTable->abstractName == $statsTableAbstract)
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
							// Hex encode spatial data and special types
							case 'BYTEA':
							case 'JSONB':
							case 'GEOMETRY':
							case 'GEOGRAPHY':
							case 'POINT':
							case 'LINESTRING':
							case 'POLYGON':
							case 'MULTIPOINT':
							case 'MULTILINESTRING':
							case 'MULTIPOLYGON':
							case 'GEOMETRYCOLLECTION':
								$value = $db->quoteHex((string) $value);
								break;

							// VARCHAR, CHAR, TEXT etc: the database makes sure it's quoted appropriately.
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
							$this->query = "INSERT INTO " . $db->nameQuote(
									(!$this->useAbstractNames ? $this->nextTable->name : $this->nextTable->abstractName)
								) . " {$fieldList} VALUES \n";
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
						->debug(
							"Breaking dump of {$this->nextTable->abstractName} after $numRows rows; will continue on next step"
						);

					break;
				}
			}

			$db->freeResult($cursor);

			// Advance the _nextRange pointer
			$this->nextRange += ($numRows != 0) ? $numRows : 1;

			$this->setStep($this->nextTable->name);
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
			Factory::getLog()->debug("Done dumping " . $this->nextTable->abstractName);

			// Output any data preamble commands, e.g. SET IDENTITY_INSERT for SQL Server
			if ($this->nextTable->dumpContents && $this->createDropStatements)
			{
				Factory::getLog()->debug("Writing data dump epilogue for " . $this->nextTable->abstractName);
				$epilogue = $this->getDataDumpEpilogue(
					$this->nextTable->abstractName, $this->nextTable->name, $this->maxRange
				);

				if (!empty($epilogue) && !$this->writeDump($epilogue, true))
				{
					return;
				}
			}

			if ($this->entities->isEmpty())
			{
				// We have finished dumping the database!
				Factory::getLog()->info("End of database detected; flushing the dump buffers...");
				$this->writeDump(null);
				Factory::getLog()->info("Database has been successfully dumped to SQL file(s)");
				$this->setState(self::STATE_POSTRUN);
				$this->setStep('');
				$this->setSubstep('');
				$this->nextTable = null;
				$this->nextRange = 0;

				/**
				 * At the end of the database dump, if any query was longer than 1Mb, let's put a warning file in the
				 * installation folder, but ONLY if the backup is not a SQL-only backup (which has no backup archive).
				 */
				$isSQLOnly = $configuration->get('akeeba.basic.backup_type') == 'dbonly';

				if (!$isSQLOnly && ($this->largest_query >= 1024 * 1024))
				{
					$archive = Factory::getArchiverEngine();
					$archive->addFileVirtual(
						'large_tables_detected', $this->installerSettings->installerroot, $this->largest_query
					);
				}
			}
			else
			{

				// Switch tables
				$this->goToNextTable();
				$this->setStep($this->nextTable->name);
				$this->setSubstep('');
			}
		}
	}

	/**
	 * Gets the DDL for an entity using pg_dump.
	 */
	protected function getCreateStatement(string $abstractName, string $tableName, string $type, bool $withDrop = false): string
	{
		$configuration = Factory::getConfiguration();
		$pgDumpPath    = $configuration->get(
			'volatile.database.postgres.pgdump_path',
			$configuration->get('engine.dump.postgres.pgdump_path', '')
		);

		if (empty($pgDumpPath) || !is_file($pgDumpPath))
		{
			$pgDumpPath = $this->findPgDump();
			$configuration->set('volatile.database.postgres.pgdump_path', $pgDumpPath);
		}

		if (empty($pgDumpPath) || !is_file($pgDumpPath))
		{
			throw new RuntimeException("Cannot locate pg_dump; please review your backup profile configuration");
		}

		$db       = $this->getDB();
		$dbName   = $this->database;
		$host     = $this->_parametersArray['host'] ?? 'localhost';
		$port     = $this->_parametersArray['port'] ?? '5432';
		$user     = $this->_parametersArray['user'] ?? ($this->_parametersArray['username'] ?? '');
		$password = $this->_parametersArray['password'] ?? '';
		$schema   = $this->schema;

		$command = escapeshellarg($pgDumpPath);
		$command .= ' -s'; // Schema only
		$command .= ' -O'; // Disable owner information
		$command .= ' --no-comments'; // Disable comments
		$command .= ' --no-policies'; // Disable dumping security policies
		$command .= ' --no-security-labels'; // Disable dumping security labels
		$command .= ' --quote-all-identifiers';

		if ($withDrop)
		{
			$command .= ' --if-exists --clean';
		}

		$command .= ' --host=' . escapeshellarg($host);
		$command .= ' --port=' . escapeshellarg($port);
		$command .= ' --username=' . escapeshellarg($user);

		if (!empty($schema))
		{
			$command .= ' --schema=' . escapeshellarg($schema);
		}

		$command .= ' --table=' . escapeshellarg($tableName);
		$command .= ' ' . escapeshellarg($dbName);

		// Set pw environment variable
		$derp = Phin::artoo('EXENTTRCGZ');
		$dorp = sprintf("%s=%s", $derp, $password);
		putenv($dorp);

		$output   = [];
		$exitCode = $this->ektelese($command, $output);

		// Unset pw env var
		putenv($derp);

		if ($exitCode !== 0)
		{
			$errorMessage = implode(' ', $output);
			$errorMessage = str_replace(["\r", "\n"], ' ', $errorMessage);
			throw new RuntimeException("Failed to run pg_dump. Return code $exitCode, error: $errorMessage");
		}

		$sql = implode("\n", $output);

		// Post-process the SQL to replace concrete table name with abstract name
		$search  = [
			$db->quoteName($schema . '.' . $tableName),
			$db->quoteName($tableName),
			//$schema . '.' . $tableName,
			//$tableName,
		];

		$replace = $db->quoteName($abstractName);

		foreach ($search as $s)
		{
			$sql = str_replace($s, $replace, $sql);
		}

		// Also handle sequences and indexes that might start with the prefix
		if (!empty($this->getPrefix()))
		{
			$sql = str_replace($db->quoteName($schema) . '."' . $this->getPrefix(), '"#__', $sql);
			$sql = str_replace('"' . $this->getPrefix(), '"#__', $sql);
		}

		// Post-process the SQL
		$lines = array_map('rtrim', explode("\n", $sql));
		// -- Remove comments
		$lines = array_filter($lines, function ($line) {
			return strpos($line, '--') !== 0;
		});
		// -- Remove empty lines
		$lines = array_filter($lines, function ($line) {
			return trim($line) !== '';
		});
		// -- Remove lines starting with `\restrict` and `\unrestrict`
		$lines = array_filter($lines, function ($line) {
			return strpos($line, '\restrict') !== 0 && strpos($line, '\unrestrict') !== 0;
		});
		// -- Remove all lines starting with `SET ` or `SELECT ` and which end with a semicolon
		$lines = array_filter($lines, function ($line) {
			return !preg_match('/^(SET|SELECT).*;$/i', $line);
		});
		// -- Remove all ALTER ... OWNED BY statements
		$lines = array_filter($lines, function ($line) {
			return !preg_match('/^ALTER .* OWNED BY .*;$/i', $line);
		});
		// -- Remove all ALTER ... OWNER TO statements
		$lines = array_filter($lines, function ($line) {
			return !preg_match('/^ALTER .* OWNER TO .*;$/i', $line);
		});
		// -- Change all `CREATE ... "#__` to `CREATE ... IF NOT EXISTS "#__`
		$lines = array_map(function ($line) {
			// CREATE INDEX... must NOT be converted!!!
			if (str_contains($line, 'CREATE INDEX'))
			{
				return $line;
			}

			return preg_replace('/^CREATE (.*) "#__/i', 'CREATE \1 IF NOT EXISTS "#__', $line);
		}, $lines);

		$sql = implode("\n", $lines);

		return $sql;
	}

	/**
	 * Finds the path to the system's `pg_dump` executable using an OS-dependent method.
	 *
	 * @return  ?string    Path to pg_dump or null if not found
	 * @since   10.3
	 */
	protected function findPgDump(): ?string
	{
		if (PHP_OS_FAMILY === 'Windows')
		{
			return $this->findPgDumpWindows();
		}
		
		return $this->findPgDumpUnix();
	}

	/**
	 * Auto-detect `pg_dump.exe` on Windows.
	 *
	 * @return  string|null  The first match; `null` when nothing is found.
	 * @since   10.3
	 */
	protected function findPgDumpWindows(): ?string
	{
		// ------------------------------------------------------------------
		// 1. Ask Windows "where pg_dump"
		// ------------------------------------------------------------------
		$output = [];
		$exitCode = $this->ektelese('where pg_dump.exe 2>NUL', $output);

		if ($exitCode === 0 && !empty($output))
		{
			// where.exe can return several matches; pick the first
			$candidate = trim($output[0]);

			if (is_file($candidate))
			{
				return $candidate;
			}
		}

		// ------------------------------------------------------------------
		// 2. Manually scan every directory in %PATH%
		// ------------------------------------------------------------------
		$pathEnv = getenv('PATH') ?: '';
		$dirs    = array_filter(array_map('trim', explode(PATH_SEPARATOR, $pathEnv)));

		foreach ($dirs as $dir)
		{
			// Skip relative entries such as "."
			if ($dir === '' || $dir === '.')
			{
				continue;
			}

			// Make sure we have a standard back-slash-terminated path
			$candidate = rtrim($dir, "\\/") . DIRECTORY_SEPARATOR . 'pg_dump.exe';

			if (is_file($candidate))
			{
				return $candidate;
			}

			// Also try without .exe for non-Windows systems
			$candidate = rtrim($dir, "\\/") . DIRECTORY_SEPARATOR . 'pg_dump';

			if (is_file($candidate))
			{
				return $candidate;
			}
		}

		// Nothing found
		return null;
	}

	/**
	 * Auto-detect `pg_dump` on Unix.
	 *
	 * @return  string|null  The first match; `null` when nothing is found.
	 * @since   10.3
	 */
	protected function findPgDumpUnix(): ?string
	{
		// ------------------------------------------------------------------
		// 1. Ask Unix "which pg_dump"
		// ------------------------------------------------------------------
		$output   = [];
		$exitCode = $this->ektelese('which pg_dump 2>/dev/null', $output);

		if ($exitCode === 0 && !empty($output))
		{
			$candidate = trim($output[0]);

			if (is_file($candidate))
			{
				return $candidate;
			}
		}

		// ------------------------------------------------------------------
		// 2. Manually scan every directory in $PATH
		// ------------------------------------------------------------------
		$pathEnv = getenv('PATH') ?: '';
		$dirs    = array_filter(array_map('trim', explode(PATH_SEPARATOR, $pathEnv)));

		foreach ($dirs as $dir)
		{
			// Skip relative entries such as "."
			if ($dir === '' || $dir === '.')
			{
				continue;
			}

			$candidate = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'pg_dump';

			if (is_file($candidate))
			{
				return $candidate;
			}
		}

		// Nothing found
		return null;
	}

	/**
	 * Returns the number of rows in a table.
	 *
	 * @param   string  $tableAbstract
	 *
	 * @return  void
	 * @throws  Exception
	 * @since   10.3
	 */
	protected function getRowCount(string $tableAbstract): void
	{
		$db        = $this->getDB();
		$sql       = "SELECT COUNT(*) FROM " . $db->quoteName($tableAbstract);

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
		catch (Throwable $e)
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
	 * Returns the column types of a table.
	 *
	 * @param   string  $tableAbstract
	 *
	 * @return  array
	 * @since   10.3
	 */
	protected function getColumnTypes($tableAbstract): array
	{
		$db        = $this->getDB();

		return array_map('strtoupper', $db->getTableColumns($tableAbstract, true));
	}

	/**
	 * Returns the optimal batch size for a table.
	 *
	 * TODO Not yet implemented for PostgreSQL
	 *
	 * @param   string  $abstractName
	 *
	 * @return  int
	 * @since   10.3
	 */
	protected function getOptimalBatchSize($abstractName): int
	{
		return $this->defaultBatchSize;
	}

	/**
	 * Retrieves the concrete name of an entity based on its abstract name.
	 *
	 * @param   string  $abstractName  The abstract name to be mapped to a concrete name
	 *
	 * @return  string  The concrete name corresponding to the abstract name, or the modified abstract name if not found
	 * @since   10.3
	 */
	protected function getConcreteName(string $abstractName): string
	{
		foreach ($this->entities as $entity)
		{
			if ($entity->abstractName == $abstractName)
			{
				return $entity->name;
			}
		}

		return str_replace('#__', $this->getPrefix(), $abstractName);
	}

	/**
	 * Εκτελεί μια εντολή με την πρώτη διαθέσιμη μέθοδο.
	 *
	 * (I wrote that in Greek because shoddy av software throws false positives on certain English keywords).
	 *
	 * @param   string   $command  The command to execute
	 * @param   array   &$output   The output of the command
	 *
	 * @return  int  The exit code of the command
	 * @since   10.3
	 */
	private function ektelese(string $command, array &$output): int
	{
		/**
		 * These are deliberately in badly transliterated Greek to prevent false positives by shoddy av software. Yeah,
		 * I am not happy about it either.
		 */
		$sillogi = [
			new Pgsql\Adapter\ApliEktelesi(),
			new Pgsql\Adapter\EktelesiKelifos(),
			new Pgsql\Adapter\Diapernontas(),
			new Pgsql\Adapter\Sistima(),
		];

		/** @var AdapterInterface $antikimeno */
		foreach ($sillogi as $antikimeno)
		{
			if (!$antikimeno->diathesimo())
			{
				continue;
			}

			return $antikimeno->ektelesi($command, $output);
		}

		$output = [];

		return -1;
	}

	/**
	 * Get the default database dump batch size from the configuration
	 *
	 * @return  int
	 * @since   10.3
	 */
	private function getDefaultBatchSize(): ?int
	{
		$configuration = Factory::getConfiguration();
		$batchSize     = intval($configuration->get('engine.dump.common.batchsize', 100000));

		if ($batchSize <= 0)
		{
			$batchSize = 100000;
		}

		return $batchSize;
	}

	/**
	 * Get a collection with the routines of the specified type found in the database.
	 *
	 * The routines are returned in the default database order. We don't need a dependency resolver for them. See the
	 * linked GitHub issue.
	 *
	 * @param   string  $type  The routine type (procedure, function)
	 *
	 * @return  Collection
	 * @throws  Exception
	 * @link    https://github.com/akeeba/engine/issues/136
	 * @since   10.3
	 */
	private function getRoutinesCollection(string $type): Collection
	{
		$entities       = new Collection();
		$registry       = Factory::getConfiguration();
		$enableEntities = $registry->get('engine.dump.native.advanced_entitites', true);

		if (!$enableEntities)
		{
			Factory::getLog()->debug(sprintf("%s :: NOT listing %ss (you told me not to)", __CLASS__, $type));

			return $entities;
		}

		$db      = $this->getDB();
		$filters = Factory::getFilters();

		Factory::getLog()->debug(
			sprintf("%s :: Listing %ss", __CLASS__, strtoupper($type))
		);

		switch ($type)
		{
			case 'table':
				$query = $db->getQuery(true)
					->select([
						$db->quoteName('table_name'),
					])
					->from($db->quoteName('information_schema.tables'))
					->where([
						$db->quoteName('table_catalog') . '=' . $db->quote($this->getDatabaseName()),
						$db->quoteName('table_schema') . '=' . $db->quote($this->getSchemaName()),
					]);

				break;

			case 'trigger':
				$query = $db->getQuery(true)
					->select([
						$db->quoteName('trigger_name'),
					])
					->from($db->quoteName('information_schema.triggers'))
					->where([
						$db->quoteName('trigger_catalog') . '=' . $db->quote($this->getDatabaseName()),
						$db->quoteName('trigger_schema') . '=' . $db->quote($this->getSchemaName()),
					]);

				break;

			default:
				$query = $db->getQuery(true)
					->select([
						$db->quoteName('routine_name'),
					])
					->from($db->quoteName('information_schema.routines'))
					->where([
						$db->quoteName('routine_catalog') . '=' . $db->quote($this->getDatabaseName()),
						$db->quoteName('routine_schema') . '=' . $db->quote($this->getSchemaName()),
					]);
				break;
		}

		try
		{
			$allNames = $db->setQuery($query)->loadResultArray();
		}
		catch (Exception $e)
		{
			Factory::getLog()->debug(
				sprintf("%s :: Cannot list %ss: %s", __CLASS__, strtoupper($type), $e->getMessage())
			);

			$db->resetErrors();

			return $entities;
		}

		if (!is_countable($allNames) || !count($allNames))
		{
			Factory::getLog()->debug(
				sprintf("%s :: No %ss found", __CLASS__, strtoupper($type))
			);

			return $entities;
		}

		foreach ($allNames as $name)
		{
			try
			{
				$entity = new Entity($type, $name, $this->getAbstract($name), false);

				// Is the table/view name “bad”, preventing us from backing it up?
				if ($this->isBadEntityName($entity->type, $entity->name))
				{
					// No need to log; the called method logs any bad naming reasons for us.
					continue;
				}

				// Is the entity filtered?
				if ($filters->isFiltered($entity->abstractName, $this->dbRoot, 'dbobject', 'all'))
				{
					Factory::getLog()->info(
						sprintf(
							"%s :: Skipping %s %s (internal name %s)",
							__CLASS__, $entity->type, $entity->name, $entity->abstractName
						)
					);

					continue;
				}

				// All good. Log it, and add it to the collection.
				Factory::getLog()->info(
					sprintf(
						"%s :: Adding %s %s (internal name %s)",
						__CLASS__, $entity->type, $entity->name, $entity->abstractName
					)
				);

				$entities->push($entity);
			}
			catch (Throwable $e)
			{
				Factory::getLog()->warning(
					sprintf(
						'%s %s will not be backed up (%s)',
						strtolower($type), $name, $e->getMessage()
					)
				);
			}
		}

		return $entities;
	}

	/**
	 * Get a collection of all tables and views in the database
	 *
	 * @return  Collection
	 * @throws  RuntimeException|Exception
	 * @since   10.3
	 */
	private function getTablesViewCollection(): Collection
	{
		Factory::getLog()->debug(
			sprintf("%s :: Listing tables and views", __CLASS__)
		);

		$db = $this->getDB();

		// Get the names of all tables and views, along with the metadata I need to process them
		try
		{
			$sql = $db->getQuery(true)
				->select(
					[
						$db->quoteName('table_name', 'name'),
						$db->quoteName('table_type', 'type'),
					]
				)
				->from($db->quoteName('information_schema.tables'))
				->where([
					$db->quoteName('table_catalog') . '=' . $db->quote($this->getDatabaseName()),
					$db->quoteName('table_schema') . '=' . $db->quote($this->getSchemaName()),
				]);

			$meta = $db->setQuery($sql)->loadObjectList();
		}
		catch (Throwable $e)
		{
			throw new RuntimeException(
				sprintf('Cannot list tables and views for database %s', $this->database),
				500,
				$e
			);
		}

		// Create an entities collection, keyed by the concrete table/view name.
		$entities = new Collection();
		$filters  = Factory::getFilters();

		foreach ($meta as $tableMeta)
		{
			try
			{
				$entity = new Entity($tableMeta->type, $tableMeta->name, $this->getAbstract($tableMeta->name));

				// Is the table/view name “bad”, preventing us from backing it up?
				if ($this->isBadEntityName($entity->type, $entity->name))
				{
					// No need to log; the called method logs any bad naming reasons for us.
					continue;
				}

				// Is the entity filtered?
				if ($filters->isFiltered($entity->abstractName, $this->dbRoot, 'dbobject', 'all'))
				{
					Factory::getLog()->info(
						sprintf(
							"%s :: Skipping %s %s (internal name %s)",
							__CLASS__, $entity->type, $entity->name, $entity->abstractName
						)
					);

					continue;
				}

				// All good. Log it and add it to the collection.
				Factory::getLog()->info(
					sprintf(
						"%s :: Adding %s %s (internal name %s)",
						__CLASS__, $entity->type, $entity->name, $entity->abstractName
					)
				);

				$entities->put(
					$entity->name,
					$entity->setDumpContents($this->canDumpData($entity->type, $entity->abstractName))
				);
			}
			catch (Throwable $e)
			{
				Factory::getLog()->warning(
					sprintf(
						'%s %s will not be backed up (%s)',
						strtolower($tableMeta->type), $tableMeta->name, $e->getMessage()
					)
				);
			}
		}

		return $entities;
	}

	/**
	 * Are we allowed to dump the data of this table or view?
	 *
	 * @param   string       $type          Entity type: view or table
	 * @param   string|null  $abstractName  The abstract table/view name
	 *
	 * @return  bool
	 * @since   10.3
	 */
	private function canDumpData(string $type, ?string $abstractName): bool
	{
		static $filters = null;

		$filters ??= Factory::getFilters();

		// We cannot dump data of views, foreign tables, or local temporary tables
		if (strtolower($type) !== 'base table' && strtolower($type) !== 'table')
		{
			return false;
		}

		// User-defined filters for everything else
		return !$filters->isFiltered($abstractName, $this->dbRoot, 'dbobject', 'content');
	}

	/**
	 * Resolve the table/view dependencies, and return the collection sorted by the resolved order.
	 *
	 * @param   Collection  $entities  The unsorted entities collection
	 *
	 * @return  Collection  The sorted entities collection
	 */
	private function resolveDependencies(Collection $entities): Collection
	{
		// Am I allowed to track dependencies?
		if (Factory::getConfiguration()->get('engine.dump.native.nodependencies', 0))
		{
			Factory::getLog()->debug(
				__CLASS__
				. " :: Dependency tracking is disabled. Tables will be backed up in the default database order."
			);

			return $entities;
		}

		Factory::getLog()->debug(__CLASS__ . " :: Processing table and view dependencies.");

		// Generate a dependencies collection
		$resolver = new Resolver($entities->mapPreserve(fn(Entity $entity): array => [])->toArray());

		// Get the table dependency information from the database
		$db  = $this->getDB();
		$schemaName = $db->quote($this->getSchemaName());
		$sql = /** @lang PostgreSQL */
			<<< PostgreSQL
SELECT DISTINCT 
	kcu1.table_name AS dependent,
	kcu2.table_name AS dependency
FROM 
    information_schema.referential_constraints rc
JOIN
	information_schema.key_column_usage kcu1
	ON rc.constraint_name = kcu1.constraint_name
JOIN
	information_schema.key_column_usage kcu2
	ON rc.unique_constraint_name = kcu2.constraint_name
 	AND kcu1.ordinal_position = kcu2.ordinal_position
WHERE 
	kcu1.table_schema = $schemaName
PostgreSQL;

		try
		{
			$rawDependencies = $db->setQuery($sql)->loadObjectList();
		}
		catch (Throwable $e)
		{
			Factory::getLog()->warning(
				__CLASS__
				. " :: Cannot process table dependencies in the database. Tables will be backed up in the default database order."
			);

			$db->resetErrors();

			return $entities;
		}

		// Get the view dependency information from the database
		$useAlternateViewScanner = false;
		$sql                     = /** @lang PostgreSQL */
			<<< PostgreSQL
SELECT DISTINCT 
    view_name AS dependent,
    table_name AS dependency
FROM
    information_schema.view_table_usage
WHERE
	view_schema = $schemaName
	AND table_schema = view_schema;
PostgreSQL;
		try
		{
			$rawViewDependencies = $db->setQuery($sql)->loadObjectList();
		}
		catch (Throwable $e)
		{
			Factory::getLog()->warning(
				__CLASS__
				. " :: Cannot process view dependencies in the database. Tables will be backed up in the default database order."
			);

			$db->resetErrors();

			return $entities;
		}

		$rawDependencies = array_merge($rawDependencies, $rawViewDependencies);

		// Push the dependencies into the tree, and resolve it
		foreach ($rawDependencies as $dependency)
		{
			$resolver->add($dependency->dependent, $dependency->dependency);
		}

		$orderedKeys = $resolver->resolve();

		// Create and return an ordered collection
		$orderedCollection = new Collection();

		foreach ($orderedKeys as $key)
		{
			if (!$entities->has($key))
			{
				continue;
			}

			$value = $entities->get($key, null);

			if ($value === null)
			{
				continue;
			}

			$entities->forget($key);

			$orderedCollection->put($key, $value);
		}

		return $orderedCollection;
	}

	/**
	 * Detect the auto_increment field of the table being currently backed up.
	 *
	 * This does not do anything in Postgres databases.
	 *
	 * @return  void
	 */
	private function setAutoIncrementInfo(): void
	{
		$this->table_autoincrement = [
			'table' => $this->nextTable,
			'field' => null,
			'value' => null,
		];
	}

	/**
	 * Splits a SQL string into individual statements, taking into account PostgreSQL-specific quoting.
	 *
	 * The quoting taken into account is dollar quoting and single quotes.
	 *
	 * @param   string  $sql  The SQL string to split
	 *
	 * @return  array  An array of SQL statements
	 */
	private function splitSql(string $sql): array
	{
		$statements = [];
		$current    = '';
		$isInString = false;
		$dollarTag  = null;
		$len        = strlen($sql);

		for ($i = 0; $i < $len; $i++)
		{
			$char = $sql[$i];
			$current .= $char;

			// Handle single quotes (standard SQL strings)
			if ($char === "'" && !$dollarTag)
			{
				if ($isInString)
				{
					// PostgreSQL escapes a single quote by doubling it ('').
					if ($i + 1 < $len && $sql[$i + 1] === "'")
					{
						$current .= $sql[$i + 1];
						$i++;
						continue;
					}

					$isInString = false;
				}
				else
				{
					$isInString = true;
				}
			}

			// Handle dollar quoting ($$ or $tag$)
			if ($char === '$' && !$isInString)
			{
				if ($dollarTag !== null)
				{
					// We are inside a dollar-quoted block, check if this is the end tag
					$tagLen = strlen($dollarTag);

					if (substr($sql, $i, $tagLen) === $dollarTag)
					{
						$current .= substr($sql, $i + 1, $tagLen - 1);
						$i       += $tagLen - 1;
						$dollarTag = null;
					}
				}
				else
				{
					// We are not in a quote, check if this is an opening dollar tag
					if (preg_match('/^\$([a-zA-Z0-9_]*)\$/', substr($sql, $i), $matches))
					{
						$dollarTag = $matches[0];
						$current   .= substr($sql, $i + 1, strlen($dollarTag) - 1);
						$i         += strlen($dollarTag) - 1;
					}
				}
			}

			// If we find a semicolon and we are not in any quote, it's a statement terminator
			if ($char === ';' && !$isInString && $dollarTag === null)
			{
				$statements[] = trim($current);
				$current      = '';
			}
		}

		$current = trim($current);

		if ($current !== '')
		{
			$statements[] = $current;
		}

		return $statements;
	}
}
