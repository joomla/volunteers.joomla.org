<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\Sender\Adapter;

use Awf\Container\Container;
use Awf\Download\Download;

/**
 * Abstract Information Sending adapter for AWF-based software
 *
 * @since  1.0.0
 */
abstract class AbstractAwfAdapter implements AdapterInterface
{
	use ServerUrlTrait;

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		return class_exists(Download::class) && $this->getContainer() !== null;
	}

	/**
	 * @inheritDoc
	 */
	public function sendStatistics(array $queryParameters): void
	{
		$download = new Download($this->getContainer());

		$timeout   = $this->getTimeout();
		$userAgent = $this->getUserAgent();

		if ($download->getAdapterName() === 'curl')
		{
			$download->setAdapterOptions(
				[
					CURLOPT_TIMEOUT   => $timeout,
					CURLOPT_USERAGENT => $userAgent,
				]
			);
		}
		elseif ($download->getAdapterName() === 'fopen')
		{
			$download->setAdapterOptions(
				[
					'http' => [
						'timeout'    => (float) $timeout,
						'user_agent' => $userAgent,
					],
				]
			);
		}

		$download->getFromURL($this->getUrl($queryParameters));
	}

	/**
	 * Get the AWF Container for the current application
	 *
	 * @return  Container|null
	 * @since   1.0.0
	 */
	abstract protected function getContainer(): ?Container;

}