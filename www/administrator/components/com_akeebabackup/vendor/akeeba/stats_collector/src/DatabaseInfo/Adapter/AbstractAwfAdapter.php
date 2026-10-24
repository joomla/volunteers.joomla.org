<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\DatabaseInfo\Adapter;

use Akeeba\UsageStats\Collector\Constants\DatabaseType;
use Awf\Container\Container;
use Awf\Database\Driver;
use Throwable;

/**
 * Abstract database information adapter for AWF-based software
 *
 * @since  1.0.0
 */
abstract class AbstractAwfAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getType(): int
	{
		try
		{
			$db = $this->getContainer()->db;
		}
		catch (Throwable $e)
		{
			return DatabaseType::UNKNOWN;
		}

		switch ($db->name)
		{
			default:
				return DatabaseType::UNKNOWN;

			case 'sqlsrv':
			case 'sqlzure':
			case 'sqlazure':
				return DatabaseType::SQLSERVER;

			case 'postgresql':
			case 'pgsql':
				return DatabaseType::POSTGRESQL;

			case 'sqlite':
				return DatabaseType::SQLITE;

			case 'mysql':
			case 'mysqli':
			case 'pdomysql':
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
			$db = $this->getContainer()->db;
		}
		catch (Throwable $e)
		{
			return '0.0.0';
		}

		$rawVersion = $db->getVersion();

		if (!in_array($db->name, ['mysql', 'mysqli', 'pdomysql']))
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

	/** @inheritDoc */
	public function isAvailable(): bool
	{
		return class_exists(Driver::class) && $this->getContainer() !== null;
	}

	/**
	 * Get the container for this AWF-based software
	 *
	 * @return  Container|null
	 * @since   1.0.0
	 */
	abstract protected function getContainer(): ?Container;
}