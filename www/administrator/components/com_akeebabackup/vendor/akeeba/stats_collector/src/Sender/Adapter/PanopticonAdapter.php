<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\Sender\Adapter;

use Akeeba\Panopticon\Factory;
use Awf\Container\Container;

/**
 * Information Sending adapter for Akeeba Panopticon
 *
 * @since  1.0.0
 */
final class PanopticonAdapter extends AbstractAwfAdapter
{
	/** @inheritDoc */
	protected function getContainer(): ?Container
	{
		if (!class_exists(Factory::class))
		{
			return null;
		}

		return Factory::getContainer();
	}
}