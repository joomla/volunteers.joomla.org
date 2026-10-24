<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\CommonVariables\Adapter;

/**
 * Adapter interface for interacting with common variables
 *
 * @since  1.0.0
 */
interface AdapterInterface
{
	/**
	 * Load a variable from the common variables table. If it does not exist, it returns the default value
	 *
	 * @param   string  $key      The key to retrieve
	 * @param   mixed   $default  The default value in case the key is missing (default: NULL)
	 *
	 * @return  string|null  The value of the common variable; NULL if it does not exist
	 * @since   1.0.0
	 */
	public function getCommonVariable(string $key, ?string $default = null): ?string;

	/**
	 * Set a variable to the common variables table.
	 *
	 * @param   string       $key    The key to set
	 * @param   string|null  $value  The value to set
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function setCommonVariable(string $key, ?string $value): void;

	/**
	 * Is the adapter available under the current operating environment?
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function isAvailable(): bool;
}