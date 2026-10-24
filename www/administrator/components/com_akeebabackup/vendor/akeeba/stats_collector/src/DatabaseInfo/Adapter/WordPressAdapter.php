<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\DatabaseInfo\Adapter;

use Akeeba\UsageStats\Collector\Constants\DatabaseType;
use Throwable;
use wpdb;

/**
 * Database information adapter for WordPress sites
 *
 * @since  1.0.0
 */
final class WordPressAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getType(): int
	{
		global $wpdb;

		// Our WordPress software only runs on MySQL and MariaDB.
		try
		{
			if (!$wpdb->is_mysql)
			{
				return DatabaseType::UNKNOWN;
			}
		}
		catch (Throwable $e)
		{
			return DatabaseType::UNKNOWN;
		}

		$rawVersion = $wpdb->db_server_info();
		$isMariaDB  = false;

		// Old MariaDB versions on Windows report their version as something like `5.5.5-10.0.17-MariaDB-log`
		$isMariaDB = strpos($rawVersion, '-MariaDB') !== false;

		return $isMariaDB ? DatabaseType::MARIADB : DatabaseType::MYSQL;
	}

	/**
	 * @inheritDoc
	 */
	public function getVersion(): string
	{
		global $wpdb;

		// Our WordPress software only runs on MySQL and MariaDB.
		try
		{
			if (!$wpdb->is_mysql)
			{
				return '0.0.0';
			}
		}
		catch (Throwable $e)
		{
			return '0.0.0';
		}

		$rawVersion = $wpdb->db_server_info();
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
		return defined('WPINC') && class_exists(wpdb::class);
	}
}