<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\DatabaseInfo\Adapter;

use Akeeba\DarkLink\Factory;
use Awf\Container\Container;

final class DarkLinkAdapter extends AbstractAwfAdapter
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