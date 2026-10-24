<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Filter\Stack;

// Protection against direct access
defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Library\ExtensionForTables;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Filter\Base as FilterBase;
use Akeeba\Engine\Platform;
use Throwable;

/**
 * Filter tables by what is installed by Joomla and its extensions.
 *
 * @since  10.1.0
 */
class StackInstalledtables extends FilterBase
{
	private ?array $installedTables = null;

	/** @inheritDoc */
	public function __construct()
	{
		$this->object  = 'dbobject';
		$this->subtype = 'all';
		$this->method  = 'api';

		parent::__construct();
	}

	/** @inheritDoc */
	protected function is_excluded_by_api($test, $root)
	{
		// This filter only applies to the main site database.
		if ($root !== '[SITEDB]')
		{
			return false;
		}

		// Check if we're told to only apply this to Joomla tables but this isn't such a table.
		if (
			Factory::getConfiguration()->get('core.filters.installedtables.onlyjoomla', 0)
			&& !str_starts_with($test, '#__')
		)
		{
			return false;
		}

		// If it's an installed or explicitly allowed table, or no tables were found, do not filter it out.
		$installedTables = $this->getInstalledTables();

		if (empty($installedTables) || in_array($test, $installedTables))
		{
			return false;
		}

		// Everything else is filtered out.
		return true;
	}

	private function getInstalledTables(): array
	{
		if ($this->installedTables !== null)
		{
			return $this->installedTables;
		}

		$config = Factory::getConfiguration();
		$json   = $config->get('volatile.stackfilters.installedtables', null);

		if (!empty($json) && is_string($json) && str_starts_with($json, '['))
		{
			try
			{
				$this->installedTables = @json_decode($json ?: '', true, 512, JSON_THROW_ON_ERROR);
			}
			catch (Throwable $e)
			{
				$this->installedTables = null;
			}
		}

		if (!is_null($this->installedTables))
		{
			return $this->installedTables;
		}


		$this->installedTables = $this->populateTables() ?? [];

		$config->set('volatile.stackfilters.installedtables', json_encode($this->installedTables));

		return $this->installedTables = $this->populateTables();
	}

	private function populateTables(): array
	{
		// First, get the installed tables from the database
		try
		{
			$platform  = Platform::getInstance();
			$dbOptions = $platform->get_platform_database_options();
			$db        = Factory::getDatabase($dbOptions);
			$allTables = ExtensionForTables::allTables($db) ?: [];
		}
		catch (Throwable $e)
		{
			$allTables = [];
		}

		$logger = Factory::getLog();

		// Debug log
		if ($allTables)
		{
			$logger->debug(sprintf('“Only back up tables installed by Joomla! and its extensions” filter found %d installed tables:', count($allTables)));

			foreach ($allTables as $table)
			{
				$logger->debug($table);
			}
		}
		else
		{
			$logger->warning('“Only back up tables installed by Joomla! and its extensions” filter did not find any installed tables');
		}

		// We could not find installed tables; skip over this filter.
		if (empty($allTables))
		{
			return $allTables;
		}

		// Then, add any extra configured tables.
		$extraTables = explode(
			',',
			Factory::getConfiguration()->get('core.filters.installedtables.extra', '') ?: ''
		);
		$extraTables = array_map('trim', $extraTables);
		$extraTables = array_filter($extraTables);
		$extraTables = array_map(
			fn($tableName) => str_starts_with($tableName, $db->getPrefix()) ? ('#__' . substr($tableName, strlen($db->getPrefix()))) : $tableName,
			$extraTables
		);

		if ($extraTables)
		{
			$logger->debug(sprintf('“Only back up tables installed by Joomla! and its extensions” filter was giver %d explicitly allowed tables:', count($extraTables)));

			foreach ($extraTables as $table)
			{
				$logger->debug($table);
			}
		}

		// Add the default Joomla core tables...
		/** @var array $knownJoomlaCoreTables */
		include __DIR__ . '/core_tables.php';

		if (isset($knownJoomlaCoreTables) && is_array($knownJoomlaCoreTables))
		{
			$allTables = array_merge($allTables, $knownJoomlaCoreTables);
		}

		// Normalise the array
		$allTables = array_unique(
			array_merge($allTables ?? [], $extraTables)
		);

		return $allTables ?: [];
	}
}