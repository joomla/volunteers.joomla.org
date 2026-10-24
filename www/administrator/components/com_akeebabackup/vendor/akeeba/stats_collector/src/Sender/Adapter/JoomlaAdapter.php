<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\Sender\Adapter;

use Joomla\Http\HttpFactory;

/**
 * Information Sending adapter for Joomla sites, version 4 or later
 *
 * @since  1.0.0
 */
final class JoomlaAdapter implements AdapterInterface
{
	use ServerUrlTrait;

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		return defined('_JEXEC')
		       && version_compare(JVERSION, '4.0.0', 'ge');
	}

	/**
	 * @inheritDoc
	 */
	public function sendStatistics(array $queryParameters): void
	{
		$factory = new HttpFactory();
		$http    = $factory->getHttp(
			[
				/**
				 * Do not follow redirects.
				 *
				 * This is a one-way, unauthenticated beacon. It has nothing to collect from the response and no reason
				 * to be steered to a second host by whoever answers the first. Following redirects would mean that
				 * anybody able to answer for the collection endpoint — including anybody who later acquires the domain
				 * — could point every installation at a destination of their choosing.
				 */
				'follow_location' => false,
				'userAgent'       => $this->getUserAgent(),
				'timeout'         => $this->getTimeout(),
			]
		);

		$http->get($this->getUrl($queryParameters));
	}
}