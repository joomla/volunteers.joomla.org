<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\CommonVariables\Adapter;

use Akeeba\DarkLink\Factory;
use Awf\Container\Container;

/**
 * Common Variables Interaction Adapter for Akeeba Panopticon
 *
 * @since  1.0.0
 */
final class DarkLinkAdapter extends AbstractAwfAdapter
{

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		return class_exists(\Akeeba\DarkLink\Container::class);
	}

	/**
	 * @inheritDoc
	 */
	protected function getContainer(): Container
	{
		return Factory::getContainer();
	}
}