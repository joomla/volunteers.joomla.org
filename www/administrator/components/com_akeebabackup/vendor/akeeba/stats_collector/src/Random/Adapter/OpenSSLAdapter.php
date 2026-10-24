<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\Random\Adapter;

/**
 * Random Bytes adapter for the OpenSSL extension
 *
 * @since  1.0.0
 */
final class OpenSSLAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getRandomBytes(int $length = 120): string
	{
		$strong = false;

		return openssl_random_pseudo_bytes($length, $strong);
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		return
			function_exists('openssl_random_pseudo_bytes')
			&& (
				version_compare(PHP_VERSION, '5.3.4', 'ge')
				|| substr(PHP_OS, 0, 3) == 'WIN'
			);
	}
}