<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\Constants;

/**
 * CMS Types known to Akeeba Usage Stats
 *
 * @since  1.0.0
 */
final class CmsType
{
	/** @deprecated */
	public const OLD_STANDALONE = 0;

	public const JOOMLA = 1;

	public const WORDPRESS = 2;

	/** @deprecated */
	public const CLASSICPRESS = 3;

	public const STANDALONE = 4;
}