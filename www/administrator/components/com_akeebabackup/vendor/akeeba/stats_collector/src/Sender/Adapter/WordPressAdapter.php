<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\Sender\Adapter;

/**
 * Information Sending adapter for WordPress sites
 *
 * @since  1.0.0
 */
final class WordPressAdapter implements AdapterInterface
{
	use ServerUrlTrait;

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		return defined('WPINC')
		       && function_exists('wp_remote_get');
	}

	/**
	 * @inheritDoc
	 */
	public function sendStatistics(array $queryParameters): void
	{
		wp_remote_get(
			$this->getUrl($queryParameters), [
				'timeout'    => $this->getTimeout(),
				'user-agent' => $this->getUserAgent(),
			]
		);
	}
}