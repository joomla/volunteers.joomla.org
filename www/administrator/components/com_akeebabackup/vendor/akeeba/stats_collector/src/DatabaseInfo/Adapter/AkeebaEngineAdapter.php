<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\DatabaseInfo\Adapter;

use Akeeba\Engine\Factory;
use Akeeba\UsageStats\Collector\Constants\DatabaseType;
use Throwable;

/**
 * Database information adapter for Akeeba Engine, used in our backup software
 *
 * @since  1.0.0
 */
final class AkeebaEngineAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getType(): int
	{
		try
		{
			$db = Factory::getDatabase();
		}
		catch (Throwable $e)
		{
			return DatabaseType::UNKNOWN;
		}

		$driverType = $db->getDriverType();

		switch ($driverType)
		{
			default:
				return DatabaseType::UNKNOWN;

			case 'sqlite':
				return DatabaseType::SQLITE;

			case 'mysql':
				$rawVersion = $db->getVersion();
				$isMariaDB  = strpos($rawVersion, '-MariaDB') !== false;

				return $isMariaDB ? DatabaseType::MARIADB : DatabaseType::MYSQL;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getVersion(): string
	{
		try
		{
			$db = Factory::getDatabase();
		}
		catch (Throwable $e)
		{
			return '0.0.0';
		}

		$driverType = $db->getDriverType();
		$rawVersion = $db->getVersion();

		if ($driverType !== 'mysql')
		{
			return $rawVersion;
		}

		// Old MariaDB versions on Windows report their version as something like `5.5.5-10.0.17-MariaDB-log`
		if (strpos($rawVersion, '-MariaDB') !== false && strpos('5.5.5-', $rawVersion) === 0)
		{
			$rawVersion = substr($rawVersion, 6);
		}

		return $rawVersion;
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		return defined('AKEEBA') && class_exists(Factory::class);
	}
}