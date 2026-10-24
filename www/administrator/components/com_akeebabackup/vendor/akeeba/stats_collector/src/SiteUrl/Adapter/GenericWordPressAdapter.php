<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\SiteUrl\Adapter;

/**
 * Site URL adapter for WordPress sites.
 *
 * @since  1.0.0
 */
final class GenericWordPressAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getUrl(): string
	{
		return home_url();
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		return defined('WPCLI')
		       && function_exists('home_url');
	}
}