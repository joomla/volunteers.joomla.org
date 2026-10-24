<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\Sender\Adapter;

use Awf\Container\Container;

/**
 * Information Sending adapter for Akeeba Solo
 *
 * @since  1.0.0
 */
final class AkeebaSoloAdapter extends AbstractAwfAdapter
{
	/** @inheritDoc */
	protected function getContainer(): ?Container
	{
		global $akeebaSoloContainer;

		return $akeebaSoloContainer ?? null;
	}
}