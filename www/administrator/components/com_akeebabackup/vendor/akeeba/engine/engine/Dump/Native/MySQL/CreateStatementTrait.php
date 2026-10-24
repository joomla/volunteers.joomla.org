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

namespace Akeeba\Engine\Dump\Native\MySQL;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;
use Exception;

trait CreateStatementTrait
{
	/**
	 * Gets the CREATE TABLE command for a given table, view, procedure, event, function, or trigger.
	 *
	 * @param   string  $abstractName  The abstracted name of the entity.
	 * @param   string  $concreteName  The concrete (database) name of the entity.
	 * @param   string  $type          The type of the entity to scan.
	 *
	 * @return  string|null  The CREATE statement
	 */
	private function getCreateStatement(
		string $abstractName, string $concreteName, string $type
	): ?string
	{
		$db = $this->getDB();

		try
		{
			$sql  = sprintf("SHOW CREATE %s %s", strtoupper($type), $db->quoteName($abstractName));
			$temp = $db->setQuery($sql)->loadAssocList();
		}
		catch (Exception $e)
		{
			// If the query failed we don't have the necessary SHOW privilege. Log the error and fake an empty reply.
			$msg        = $e->getMessage();
			Factory::getLog()->warning(
				"Cannot get the structure of $type $abstractName. Database returned error “{$msg}” running $sql  Please check your database privileges. Your database backup may be incomplete."
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

		switch ($type)
		{
			case 'procedure':
			case 'event':
			case 'function':
			case 'trigger':
				$table_sql = $this->preProcessCreateSQLForRoutine($table_sql, $type);
				break;

			case 'view':
				$table_sql = $this->preProcessCreateSQLForView($table_sql);
				break;

			case 'table':
			case 'merge':
			default:
				$table_sql = $this->preProcessCreateSQLForTable($table_sql);
		}

		/**
		 * Replace table name and names of referenced tables with their abstracted forms and populate dependency tables
		 * at the same time.
		 */
		if (!$this->useAbstractNames)
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
		$table_sql = $this->replaceTableNamesWithAbstracts($concreteName, $table_sql);

		if (!$this->useAbstractNames)
		{
			$table_sql = $old_table_sql;
		}

		return $this->postProcessCreateSQL($table_sql, $type);
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
	private function preProcessCreateSQLForRoutine(string $table_sql, string $type): string
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

		// Remove MySQL option KEY_BLOCK_SIZE
		// @link https://www.akeeba.com/support/akeeba-backup/41463-incorrect-usage-placement-of-key-block-size.html
		$regex     = "#KEY_BLOCK_SIZE\s*=\s*[\d]{1,}#i";
		$table_sql = preg_replace($regex, '', $table_sql);

		// Abstract the names of table constraints and indices
		$regex     = "#(CONSTRAINT|KEY|INDEX)\s{1,}`{$this->prefix}#i";
		$table_sql = preg_replace($regex, '$1 `#__', $table_sql);

		return $table_sql;
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
	private function getMySQLIdentifierCharacterRegEx(): array
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
	 * Replaces the table names in the CREATE query with their abstract form. Optionally updates dependencies.
	 *
	 * @param   string  $tableName        The table name the CREATE query is for
	 * @param   string  $tableSql         The CREATE query itself
	 *
	 * @return  string
	 *
	 * @throws  Exception  When we cannot get the DB object
	 */
	private function replaceTableNamesWithAbstracts(string $tableName, string $tableSql): string
	{
		// Initialization
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

			if ($pos !== false)
			{
				// Do the replacement
				$tableSql = str_replace($quotedFullName, $quotedAbstractName, $tableSql);

				continue;
			}

			if (is_numeric($fullName))
			{
				continue;
			}

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

				$tableSql = $before . $quotedAbstractName . $after;

				$offset = $pos + $quotedAbstractNameLength;
			}

		}

		return $tableSql;
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