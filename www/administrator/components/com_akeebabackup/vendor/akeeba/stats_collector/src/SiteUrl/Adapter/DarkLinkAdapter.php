<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\SiteUrl\Adapter;

use Akeeba\DarkLink\Container;
use Awf\Application\Application;
use Awf\Uri\Uri;

/**
 * Site URL adapter for Akeeba Panopticon
 *
 * @since  1.0.0
 */
final class DarkLinkAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getUrl(): string
	{
		return Uri::base() ?? '';
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		return !defined('WPINC')
		       && class_exists(Uri::class)
		       && class_exists(Application::class)
		       && class_exists(Container::class);
	}
}