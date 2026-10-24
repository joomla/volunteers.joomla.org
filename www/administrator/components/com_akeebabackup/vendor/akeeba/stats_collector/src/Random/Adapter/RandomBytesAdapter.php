<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\Random\Adapter;

/**
 * Random Bytes adapter for PHP's `random_bytes` function (also works with polyfills).
 *
 * @since  1.0.0
 */
final class RandomBytesAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getRandomBytes(int $length = 120): string
	{
		return random_bytes($length);
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		return function_exists('random_bytes');
	}
}