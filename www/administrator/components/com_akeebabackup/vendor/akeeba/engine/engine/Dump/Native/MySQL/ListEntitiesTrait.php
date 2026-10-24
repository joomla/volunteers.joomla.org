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

use Akeeba\Engine\Dump\Dependencies\Entity;
use Akeeba\Engine\Dump\Dependencies\Resolver;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Util\Collection;
use Exception;
use RuntimeException;
use Throwable;

/**
 * Trait to list the entities which will be backed up.
 *
 * This uses an integrated view/table dependency resolver.
 *
 * @since  9.10.0
 */
trait ListEntitiesTrait
{
	/**
	 * Get a collection of all tables and views in the database
	 *
	 * @return  Collection
	 * @throws  RuntimeException|Exception
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
						$db->quoteName('TABLE_NAME', 'name'),
						$db->quoteName('TABLE_TYPE', 'type'),
						$db->quoteName('ENGINE', 'engine'),
					]
				)
				->from($db->quoteName('INFORMATION_SCHEMA.TABLES'))
				->where($db->quoteName('TABLE_SCHEMA') . ' = DATABASE()');

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

		// Create entities collection, keyed by the concrete table/view name.
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

				// All good. Log it, and add it to the collection.
				Factory::getLog()->info(
					sprintf(
						"%s :: Adding %s %s (internal name %s)",
						__CLASS__, $entity->type, $entity->name, $entity->abstractName
					)
				);

				$entities->put(
					$entity->name,
					$entity->setDumpContents(
						$this->canDumpData($entity->type, $entity->abstractName, $tableMeta->engine)
					)
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
	 * Get a collection with the routines of the specified type found in the database.
	 *
	 * The routines are returned in the default database order. We don't need a dependency resolver for them. See the
	 * linked GitHub issue.
	 *
	 * @param   string  $type  The routine type (procedure, function, trigger, event)
	 *
	 * @return  Collection
	 * @throws  Exception
	 * @link    https://github.com/akeeba/engine/issues/136
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
				$sql    = 'SHOW TABLES';
				$offset = 0;
				break;

			case 'event':
				$sql    = "SHOW EVENTS WHERE `Db`=" . $db->quote($this->database);
				$offset = 1;
				break;

			case 'trigger':
				$sql    = 'SHOW TRIGGERS IN ' . $db->quoteName($this->database);
				$offset = 0;
				break;

			default:
				$sql    = "SHOW " . $type . " STATUS WHERE `Db`=" . $db->quote($this->database);
				$offset = 1;
				break;
		}

		try
		{
			$allNames = $db->setQuery($sql)->loadResultArray($offset);
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
	 * Isolates parsed SQL nodes by their type.
	 *
	 * @param   array   $parsed  The parsed SQL nodes tree
	 * @param   string  $type    The node type to extract
	 *
	 * @return  array|string
	 * @see     self::getViewDependencies()
	 */
	private function isolateParsedNodes(array $parsed, string $type)
	{
		if (isset($parsed[$type]))
		{
			return $parsed[$type];
		}

		$ret = [];

		foreach ($parsed as $key => $node)
		{
			if (is_array($node) && ($maybe = $this->isolateParsedNodes($node, $type)))
			{
				$ret[] = $maybe;
			}
		}

		return $ret;
	}

	/**
	 * Extract table names from a list of parsed SQL nodes
	 *
	 * @param   array  $parsed  The parsed SQL nodes tree
	 *
	 * @return  array|string
	 * @see     self::getViewDependencies()
	 */
	private function extractTablesFromParsedNodes(array $parsed)
	{
		if (isset($parsed['expr_type']) && $parsed['expr_type'] == 'table')
		{
			return end($parsed['no_quotes']['parts']) ?: null;
		}

		$ret = [];

		foreach ($parsed as $key => $node)
		{
			if (is_array($node) && ($maybe = $this->extractTablesFromParsedNodes($node)))
			{
				$ret[] = $maybe;
			}
		}

		return $ret;
	}

	/**
	 * Flattens a deeply nested array
	 *
	 * @param   array  $nestedArray  The nested array to flatten
	 *
	 * @return  array
	 * @see     self::getViewDependencies()
	 */
	private function flattenArray(array $nestedArray): array
	{
		$ret = [];

		array_walk_recursive(
			$nestedArray,
			function ($value) use (&$ret) {
				$ret[] = $value;
			}
		);

		return $ret;
	}

	/**
	 * Get the VIEW dependencies by parsing the DDL stored in the database for each view.
	 *
	 * This is a slower, less accurate method for old database servers, e.g. MySQL 5.7 and MariaDB 10.11.
	 *
	 * @param   Collection  $entities
	 *
	 * @return  array
	 * @throws  Exception
	 * @see     self::resolveDependencies()
	 */
	private function getViewDependencies(Collection $entities): array
	{
		$ret = [];

		/** @var Entity $entity */
		foreach ($entities as $entity)
		{
			if ($entity->type !== 'view')
			{
				continue;
			}

			$db               = $this->getDB();
			$sql              = 'SHOW CREATE VIEW ' . $db->quoteName($entity->name);
			$viewInfo         = $db->setQuery($sql)->loadAssoc();
			$parsed           = (new \PHPSQLParser\PHPSQLParser())->parse($viewInfo['Create View']);
			$referencedTables = [];

			foreach ($this->isolateParsedNodes($parsed, 'FROM') as $node)
			{
				$temp             = $this->extractTablesFromParsedNodes($node);
				$referencedTables = array_merge(
					$referencedTables,
					is_array($temp) ? $temp : [$temp]
				);
			}

			foreach (array_unique($this->flattenArray($referencedTables)) as $refTable)
			{
				$ret[] = (object) [
					'dependent'  => $entity->name,
					'dependency' => $refTable,
				];
			}
		}

		return $ret;
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
		$sql = /** @lang MySQL */
			<<< MySQL
SELECT DISTINCT `TABLE_NAME` AS `dependent`,
                `REFERENCED_TABLE_NAME` AS `dependency`
FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `REFERENCED_TABLE_SCHEMA` = `TABLE_SCHEMA`
MySQL;
		$db  = $this->getDB();
		try
		{
			$rawDependencies = $db->setQuery($sql)->loadObjectList();
		}
		catch (Throwable $e)
		{
			Factory::getLog()->warning(
				__CLASS__
				. " :: Cannot process table and view dependencies in the database. Tables will be backed up in the default database order."
			);

			$db->resetErrors();

			return $entities;
		}


		// Get the view dependency information from the database
		$useAlternateViewScanner = false;
		$sql                     = /** @lang MySQL */
			<<< MySQL
SELECT DISTINCT `VIEW_NAME` AS `dependent`,
                `TABLE_NAME` AS `dependency`
FROM `INFORMATION_SCHEMA`.`VIEW_TABLE_USAGE`
WHERE `VIEW_SCHEMA` = DATABASE()
  AND `TABLE_SCHEMA` = DATABASE();
MySQL;
		try
		{
			$rawViewDependencies = $db->setQuery($sql)->loadObjectList();
		}
		catch (Throwable $e)
		{
			$useAlternateViewScanner = true;

			$db->resetErrors();
		}

		// Get the view dependency information from the database (fallback using SQL parsing)
		if ($useAlternateViewScanner)
		{
			try
			{
				$rawViewDependencies = $this->getViewDependencies($entities);
			}
			catch (Exception $e)
			{
				Factory::getLog()->warning(
					__CLASS__
					. " :: Cannot process table and view dependencies in the database. Tables will be backed up in the default database order."
				);

				$db->resetErrors();

				return $entities;
			}
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
	 * Are we allowed to dump the data of this table or view?
	 *
	 * @param   string       $type          Entity type: view or table
	 * @param   string|null  $abstractName  The abstract table/view name
	 * @param   string|null  $engine        The engine type, only applies to tables
	 *
	 * @return  bool
	 * @since   9.10.0
	 */
	private function canDumpData(string $type, ?string $abstractName, ?string $engine)
	{
		static $filters = null;

		$filters ??= Factory::getFilters();

		// We cannot dump data of views
		if ($type === 'view')
		{
			return false;
		}

		// We cannot dump data of tables with these database engines
		if (in_array(strtoupper($engine ?? ''), ['MRG_MYISAM', 'MEMORY', 'EXAMPLE', 'BLACKHOLE', 'FEDERATED']))
		{
			return false;
		}

		// User-defined filters for everything else
		return !$filters->isFiltered(
			$abstractName,
			$this->dbRoot,
			'dbobject',
			'content'
		);
	}
}