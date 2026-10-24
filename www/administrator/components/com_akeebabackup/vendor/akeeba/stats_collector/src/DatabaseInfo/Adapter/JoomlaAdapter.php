<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\DatabaseInfo\Adapter;

use Akeeba\UsageStats\Collector\Constants\DatabaseType;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Throwable;

/**
 * Database information adapter for Joomla! sites, version 4 or later
 *
 * @since  1.0.0
 */
final class JoomlaAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getType(): int
	{
		try
		{
			/** @var DatabaseDriver $db */
			$db         = Factory::getContainer()->get(DatabaseInterface::class);
			$serverType = $db->getServerType();
		}
		catch (Throwable $e)
		{
			return DatabaseType::UNKNOWN;
		}

		switch ($serverType)
		{
			case 'mysql':
				if (method_exists($db, 'isMariaDb') && $db->isMariaDb())
				{
					return DatabaseType::MARIADB;
				}

				return DatabaseType::MYSQL;

			case 'postgresql':
				return DatabaseType::POSTGRESQL;

			case 'sqlite':
				return DatabaseType::SQLITE;

			case 'mssql':
				return DatabaseType::SQLSERVER;

			default:
				return DatabaseType::UNKNOWN;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getVersion(): string
	{
		try
		{
			/** @var DatabaseDriver $db */
			$db = Factory::getContainer()->get(DatabaseInterface::class);

			return $db->getVersion();
		}
		catch (Throwable $e)
		{
			return '0.0.0';
		}
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		return defined('_JEXEC') && interface_exists(DatabaseInterface::class);
	}
}