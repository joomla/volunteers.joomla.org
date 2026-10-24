<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\Sender\Adapter;

/**
 * Information Sending adapter interface
 *
 * @since  1.0.0
 */
interface AdapterInterface
{
	/**
	 * Is the adapter available under the current environment?
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function isAvailable(): bool;

	/**
	 * Sets the server URL
	 *
	 * @param   string  $url
	 *
	 * @return  void
	 */
	public function setServerUrl(string $url): void;

	/**
	 * Send the usage statistics information to the server
	 *
	 * @param   array  $queryParameters  The information to send
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function sendStatistics(array $queryParameters): void;
}