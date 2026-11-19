<?php
/**
 * Akeeba WebPush
 *
 * An abstraction layer for easier implementation of WebPush in Joomla components.
 *
 * @copyright Copyright (c) 2022-2026 Akeeba Ltd
 * @license   GNU GPL v3 or later; see LICENSE.txt
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Akeeba\WebPush\WebPush;

/**
 * Decides whether a Web Push endpoint URL is safe for the server to send a request to.
 *
 * The endpoint of a push subscription is chosen by the browser, which means by whoever controls the browser. Sending a
 * notification makes the server POST to that URL. Without validation any user able to subscribe could have the server
 * issue requests to itself, to other hosts on its internal network, or to a cloud metadata service.
 *
 * Real push services (Google FCM, Mozilla autopush, Microsoft WNS, Apple) are public HTTPS hosts on the default port,
 * addressed by host name. Anything else is refused: other schemes, credentials in the URL, non-default ports, IP
 * literals, local-only host names, and host names resolving to any private, loopback, link-local, reserved, or
 * carrier-grade NAT address. The check runs both when a subscription is saved and right before sending.
 *
 * @since  __DEPLOY_VERSION__
 */
final class EndpointValidator
{
	/**
	 * Is the endpoint a URL the server may send a push request to?
	 *
	 * @param   string  $endpoint  The subscription endpoint URL.
	 *
	 * @return  bool
	 * @since   __DEPLOY_VERSION__
	 */
	public static function isAllowed(string $endpoint): bool
	{
		$parts = parse_url($endpoint);

		if (!is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https')
		{
			return false;
		}

		if (isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && (int) $parts['port'] !== 443))
		{
			return false;
		}

		$host = strtolower(trim($parts['host'] ?? '', '[].'));

		// Push services are addressed by host name; an IP literal or a single-label name is an internal target.
		if ($host === '' || filter_var($host, FILTER_VALIDATE_IP) !== false || strpos($host, '.') === false)
		{
			return false;
		}

		foreach (['.localhost', '.local', '.internal', '.localdomain', '.home.arpa'] as $localSuffix)
		{
			if (substr('.' . $host, -strlen($localSuffix)) === $localSuffix)
			{
				return false;
			}
		}

		$addresses = self::resolve($host);

		if (empty($addresses))
		{
			return false;
		}

		foreach ($addresses as $address)
		{
			if (!self::isPublicAddress($address))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * The IPv4 and IPv6 addresses a host name resolves to.
	 *
	 * @param   string  $host  The host name.
	 *
	 * @return  string[]
	 * @since   __DEPLOY_VERSION__
	 */
	private static function resolve(string $host): array
	{
		$addresses = @gethostbynamel($host) ?: [];

		if (function_exists('dns_get_record'))
		{
			foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record)
			{
				if (!empty($record['ipv6']))
				{
					$addresses[] = $record['ipv6'];
				}
			}
		}

		return array_values(array_unique($addresses));
	}

	/**
	 * Is the address publicly routable?
	 *
	 * @param   string  $address  An IPv4 or IPv6 address.
	 *
	 * @return  bool
	 * @since   __DEPLOY_VERSION__
	 */
	private static function isPublicAddress(string $address): bool
	{
		if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false)
		{
			return false;
		}

		$long = ip2long($address);

		// Carrier-grade NAT, 100.64.0.0/10, which PHP's reserved range flag does not cover.
		if ($long !== false && ($long & 0xFFC00000) === (ip2long('100.64.0.0') & 0xFFC00000))
		{
			return false;
		}

		// IPv4-mapped IPv6 addresses, e.g. ::ffff:127.0.0.1, are judged by the IPv4 address they carry.
		if ($long === false && stripos($address, '::ffff:') === 0)
		{
			return self::isPublicAddress(substr($address, 7));
		}

		return true;
	}
}
