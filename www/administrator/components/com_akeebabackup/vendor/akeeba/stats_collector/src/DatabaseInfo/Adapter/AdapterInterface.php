<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\DatabaseInfo\Adapter;

/**
 * Database Information adapter interface.
 *
 * @since 1.0.0
 */
interface AdapterInterface
{
	/**
	 * Get the database type
	 *
	 * @return  int
	 * @since   1.0.0
	 */
	public function getType(): int;

	/**
	 * Get the database version
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function getVersion(): string;

	/**
	 * Is the adapter available under the current environment?
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function isAvailable(): bool;
}