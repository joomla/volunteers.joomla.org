<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\CmsInfo\Adapter;

use Akeeba\UsageStats\Collector\Constants\CmsType;
use Joomla\CMS\Version;

/**
 * Collect CMS information adapter for Joomla sites, version 4 and later
 */
final class JoomlaAdapter implements AdapterInterface
{

	/** @inheritDoc */
	public function getType(): int
	{
		return CmsType::JOOMLA;
	}

	/** @inheritDoc */
	public function getVersion(): string
	{
		return JVERSION;
	}

	/** @inheritDoc */
	public function isAvailable(): bool
	{
		return defined('_JEXEC') && class_exists(Version::class);
	}
}