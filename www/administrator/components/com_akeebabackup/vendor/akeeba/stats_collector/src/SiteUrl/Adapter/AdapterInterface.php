<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\SiteUrl\Adapter;

/**
 * Adapter to get the URL of the current site
 *
 * @since  1.0.0
 */
interface AdapterInterface
{
	/**
	 * Gets the URL of the current site
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function getUrl(): string;

	/**
	 * Is this adapter available under the current environment?
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function isAvailable(): bool;
}