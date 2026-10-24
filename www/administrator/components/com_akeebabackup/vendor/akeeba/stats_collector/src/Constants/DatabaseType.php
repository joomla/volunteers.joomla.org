<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\Constants;

/**
 * Database Types known to Akeeba Usage Stats
 *
 * @since  1.0.0
 */
final class DatabaseType
{
	public const UNKNOWN = 0;

	public const MYSQL = 1;

	/** @deprecated */
	public const SQLSERVER = 2;

	/** @deprecated */
	public const POSTGRESQL = 3;

	public const MARIADB = 4;

	/** @deprecated */
	public const SQLITE = 5;
}