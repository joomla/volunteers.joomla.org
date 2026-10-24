<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\Sender\Adapter;

/**
 * Trait to handle the server URL and stats URL generation
 *
 * @since  1.0.0
 */
trait ServerUrlTrait
{
	/**
	 * The URL to the statistics collection server
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	private $serverUrl = '';

	/**
	 * Request timeout, in seconds
	 *
	 * @var   int
	 * @since 1.0.0
	 */
	private $timeout = 5;

	/**
	 * Sets the server URL
	 *
	 * @param   string  $url
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function setServerUrl(string $url): void
	{
		$this->serverUrl = $url;
	}

	/**
	 * Get the request timeout, in seconds
	 *
	 * @return  int
	 * @since   1.0.0
	 */
	protected function getTimeout(): int
	{
		return $this->timeout;
	}

	/**
	 * Set the request timeout
	 *
	 * @param   int  $timeout  The timeout, in seconds
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function setTimeout(int $timeout): void
	{
		$this->timeout = $timeout;
	}

	/**
	 * Get the URL to fetch
	 *
	 * @param   array  $queryParameters
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function getUrl(array $queryParameters): string
	{
		return $this->serverUrl . '?' . http_build_query($queryParameters);
	}

	/**
	 * Returns a custom User Agent string.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function getUserAgent(): string
	{
		return 'AkeebaUsageStats/1.0';
	}
}