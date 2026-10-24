<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\CmsInfo\Adapter;

use Akeeba\UsageStats\Collector\Constants\CmsType;

/**
 * Collect CMS information adapter for Joomla sites, version 4 and later
 */
final class WordPressAdapter implements AdapterInterface
{

	/** @inheritDoc */
	public function getType(): int
	{
		if (function_exists('classicpress_version'))
		{
			return CmsType::CLASSICPRESS;
		}

		return CmsType::WORDPRESS;
	}

	/** @inheritDoc */
	public function getVersion(): string
	{
		return get_bloginfo();
	}

	/** @inheritDoc */
	public function isAvailable(): bool
	{
		global $wp_version;

		return defined('WPINC') && function_exists('get_bloginfo');
	}
}