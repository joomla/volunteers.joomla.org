<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\SiteUrl\Adapter;

use Akeeba\AdminTools\Library\Uri\Uri;

/**
 * Site URL adapter for Admin Tools for WordPress
 *
 * @since  1.0.0
 */
final class AdminToolsWordPressAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getUrl(): string
	{
		return Uri::base();
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		return defined('WPINC')
		       && class_exists(Uri::class)
		       && function_exists('home_url');
	}
}