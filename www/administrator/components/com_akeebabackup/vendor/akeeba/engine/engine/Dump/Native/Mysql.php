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
use Akeeba\Engine\Dump\Dependencies\Entity;
use Akeeba\Engine\Dump\Native\MySQL\BadEntityNamesTrait;
use Akeeba\Engine\Dump\Native\MySQL\CreateStatementTrait;
use Akeeba\Engine\Dump\Native\MySQL\DropStatementTrait;
use Akeeba\Engine\Dump\Native\MySQL\ListEntitiesTrait;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Util\Collection;
use AllowDynamicProperties;
use Countable;
use Exception;
use RuntimeException;
use Throwable;
use function array_map;
use function array_shift;
use function count;
use function intval;
use function max;
use function memory_get_usage;
use function min;
use function str_replace;

/**
 * A generic MySQL database dump class.
 *
 * Configuration parameters:
 * host            <string>    MySQL database server host name or IP address
 * port            <string>    MySQL database server port (optional)
 * username        <string>    MySQL user name, for authentication
 * password        <string>    MySQL password, for authentication
 * database        <string>    MySQL database
 * dumpFile        <string>    Absolute path to dump file; must be writable (optional; if left blank it is
 * automatically calculated)
 */
#[AllowDynamicProperties]
class Mysql extends Base
{
	use BadEntityNamesTrait;
	use ListEntitiesTrait;
	use CreateStatementTrait;
	use DropStatementTrait;

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

	private ?string $dbRoot;

	private bool $mustFilterRows;

	private bool $mustFilterContents;

	private int $defaultBatchSize;

	private bool $createDropStatements;

	private bool $useDelimiterStatements;

	/**
	 * Implements the constructor of the class
	 *
	 * @return  void
	 */
	public function __construct()
	{
		parent::__construct();

		Factory::getLog()->debug(__CLASS__ . " :: New instance");

		$engineParams                 = Factory::getEngineParamsProvider();
		$this->useAbstractNames       = $engineParams->getScriptingParameter('db.abstractnames', 1) == 1;
		$this->createDropStatements   = $engineParams->getScriptingParameter('db.dropstatements', 0) == 1;
		$this->useDelimiterStatements = $engineParams->getScriptingParameter('db.delimiterstatements', 0) == 1;

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
			return $this->getDB()->setQuery('SELECT DATABASE()')->loadResult() ?: '';
		}
		catch (Throwable $e)
		{
			return '';
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

		try
		{
			$tableCols = $this
				->getDB()
				->setQuery('SHOW COLUMNS FROM ' . $this->getDB()->qn($tableAbstract))
				->loadAssocList();
		}
		catch (Throwable $e)
		{
			return $this->columnListSelectColumn;
		}

		$totalColumns                 = empty($tableCols) ? 0 : count($tableCols);
		$this->columnListSelectColumn = [];
		$hasInvisibleColumns          = false;

		foreach ($tableCols as $col)
		{
			// Skip over generated columns
			$attribs = array_map(
				'strtoupper',
				empty($col['Extra']) ? [] : explode(' ', $col['Extra'])
			);

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

		if (!$hasInvisibleColumns && ($totalColumns === count($this->columnListSelectColumn)))
		{
			$this->columnListSelectColumn = '*';
		}

		return $this->columnListSelectColumn;
	}

	/** @inheritDoc */
	protected function getAllTables(): array
	{
		// Get a database connection
		$this->enforceSQLCompatibility();

		try
		{
			return $this->getDB()->setQuery('SHOW TABLES')->loadColumn() ?: [];
		}
		catch (Throwable $e)
		{
			return [];
		}
	}

	/**
	 * Adds tables, views, and routines to the backup queue.
	 *
	 * This method will add procedures, functions, tables, views, triggers, and events to the backup queue.
	 *
	 * Everything but tables and views will only be added if the option to back up stored routines is enabled.
	 *
	 * Tables and views will be added in the order determined by resolving their dependencies. If dependency tracking is
	 * disabled, they are backed up at the order they appear in the database listing.
	 *
	 * @return  void
	 * @throws  Exception
	 */
	protected function getTablesToBackup(): void
	{
		// Makes the MySQL connection compatible with our class
		$this->enforceSQLCompatibility();

		/**
		 * The order routines, tables, and views are added is IMPORTANT. See the linked GitHub issue for information on
		 * the reasoning behind this decision.
		 *
		 * @link https://github.com/akeeba/engine/issues/136
		 */
		$this->entities = new Collection();

		$this->entities = $this->entities->merge($this->getRoutinesCollection('procedure'));
		$this->entities = $this->entities->merge($this->getRoutinesCollection('function'));
		$this->entities = $this->entities->merge(
			$this->resolveDependencies($this->getTablesViewCollection())->values()
		);
		$this->entities = $this->entities->merge($this->getRoutinesCollection('trigger'));
		$this->entities = $this->entities->merge($this->getRoutinesCollection('event'));

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
	 * @return void
	 */
	protected function goToNextTable(): void
	{
		$this->nextTable = $this->entities->shift();
		$this->nextRange = 0;
	}

	/**
	 * Performs one more step of dumping database data.
	 *
	 * DO NOT BREAK INTO MULTIPLE METHODS.
	 *
	 * Calling methods incurs a performance penalty. Since this is a very tight loop which runs thousands to millions of
	 * times per backup job any such small performance penalties accrue into something impractical.
	 *
	 * @return  void
	 *
	 * @throws  QueryException
	 * @throws  Exception
	 */
	protected function stepDatabaseDump(): void
	{
		// Initialize local variables
		$db            = $this->getDB();
		$configuration = Factory::getConfiguration();
		$filters       = Factory::getFilters();

		if (!is_object($db) || ($db === false))
		{
			throw new RuntimeException(__CLASS__ . '::_run() Could not connect to database?!');
		}

		// Apply MySQL compatibility option
		$this->enforceSQLCompatibility();

		// Touch SQL dump file
		$nada = "";
		$this->writeline($nada);

		// Get this table's information
		$this->setStep($this->nextTable->name);
		$this->setSubstep('');
		// Restore any previously information about the largest query we had to run
		$this->largest_query = Factory::getConfiguration()->get('volatile.database.largest_query', 0);

		// If it is the first run, find number of rows and get the CREATE TABLE command.
		if ($this->nextRange == 0)
		{
			$outCreate = $this->getCreateStatement(
				$this->nextTable->abstractName, $this->nextTable->name, $this->nextTable->type
			);

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

			$suffix = "/*ABDE:{$this->nextTable->abstractName}*/\n";

			// Create drop statements if required (the key is defined by the scripting engine)
			if ($this->createDropStatements && !empty($outCreate))
			{
				$dropStatement = $this->createDrop($this->nextTable);

				if (!empty($dropStatement))
				{
					$dropStatement .= $suffix;

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
				!$this->nextTable->isTable() && !$this->nextTable->isView()
				&& $this->useDelimiterStatements
			)
			{
				$outCreate = rtrim($outCreate, ";\n");
				$outCreate = "DELIMITER $$\n$outCreate$$\nDELIMITER ;" . $suffix;
			}

			// Write the CREATE command after any DROP command which might be necessary.
			if (!$this->writeDump(rtrim($outCreate, "\n") . $suffix, true))
			{
				return;
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
			elseif (!$this->nextTable->dumpContents)
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

			// Get the table's auto increment information
			if ($this->nextTable->dumpContents && $this->nextRange < $this->maxRange)
			{
				$this->setAutoIncrementInfo();
			}
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
							// Hex encode spatial data
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
								$value = $db->quoteHex($value);
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
	 * Detect the auto_increment field of the table being currently backed up.
	 *
	 * This method populates the $this->table_autoincrement array.
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

		$db = $this->getDB();

		$query   = 'SHOW COLUMNS FROM ' . $db->qn($this->nextTable->name) . ' WHERE ' . $db->qn('Extra') . ' = ' .
		           $db->q('auto_increment') . ' AND ' . $db->qn('Null') . ' = ' . $db->q('NO');
		$keyInfo = $db->setQuery($query)->loadAssocList();

		if (!empty($keyInfo))
		{
			$row                                = array_shift($keyInfo);
			$this->table_autoincrement['field'] = $row['Field'];
		}
	}

	/**
	 * Get the default database dump batch size from the configuration
	 *
	 * @return  int
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
	 * Get the optimal row batch size for a given table based on the available memory
	 *
	 * @param   string  $tableAbstract  The abstract table name, e.g. #__foobar
	 *
	 * @return  int
	 */
	private function getOptimalBatchSize($tableAbstract)
	{
		$db = $this->getDB();

		try
		{
			$info = $db->setQuery('SHOW TABLE STATUS LIKE ' . $db->q($tableAbstract))->loadAssoc();
		}
		catch (Exception $e)
		{
			return $this->defaultBatchSize;
		}

		if (!isset($info['Avg_row_length']) || empty($info['Avg_row_length']))
		{
			return $this->defaultBatchSize;
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

		return max(1, min($maxRows, $this->defaultBatchSize));
	}

	/**
	 * Applies the SQL compatibility setting
	 *
	 * @return  void
	 */
	private function enforceSQLCompatibility()
	{
		try
		{
			$this->getDB()->setQuery('SET sql_big_selects=1')->query();
		}
		catch (Throwable $e)
		{
			// Do nothing; some versions of MySQL don't allow you to use the BIG_SELECTS option.
		}
		finally
		{
			$this->getDB()->resetErrors();
		}
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
	private function getRowCount($tableAbstract)
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
	 * Return a list of columns and their data types.
	 *
	 * @param   string  $tableAbstract
	 *
	 * @return  array  An array of table columns and their data types.
	 */
	private function getColumnTypes($tableAbstract)
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
}