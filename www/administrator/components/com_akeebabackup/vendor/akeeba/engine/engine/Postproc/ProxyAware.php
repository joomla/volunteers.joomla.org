<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Postproc;

use Akeeba\Engine\Platform;

trait ProxyAware
{
	/**
	 * Apply the platform proxy configuration to the cURL resource.
	 *
	 * @param   resource  $ch  The cURL resource, returned by curl_init();
	 */
	protected function applyProxySettingsToCurl($ch)
	{
		if (defined('AKEEBA_NO_PROXY_AWARE'))
		{
			return;
		}

		$proxySettings = Platform::getInstance()->getProxySettings();

		if (!$proxySettings['enabled'])
		{
			return;
		}

		curl_setopt($ch, CURLOPT_PROXY, $proxySettings['host'] . ':' . $proxySettings['port']);

		if (empty($proxySettings['user']))
		{
			return;
		}

		curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxySettings['user'] . ':' . $proxySettings['pass']);
	}

	protected function getProxyStreamContext()
	{
		if (defined('AKEEBA_NO_PROXY_AWARE'))
		{
			return [];
		}

		$ret           = [];
		$proxySettings = Platform::getInstance()->getProxySettings();

		if (!$proxySettings['enabled'])
		{
			return $ret;
		}

		$ret['http'] = [
			'proxy'           => $proxySettings['host'] . ':' . $proxySettings['port'],
			'request_fulluri' => true,
		];
		$ret['ftp']  = [
			'proxy'           => $proxySettings['host'] . ':' . $proxySettings['port'],
			// So, request_fulluri isn't documented for the FTP transport but seems to be required...?!
			'request_fulluri' => true,
		];

		if (empty($proxySettings['user']))
		{
			return $ret;
		}

		$ret['http']['header'] = ['Proxy-Authorization: Basic ' . base64_encode($proxySettings['user'] . ':' . $proxySettings['pass'])];
		$ret['ftp']['header'] = ['Proxy-Authorization: Basic ' . base64_encode($proxySettings['user'] . ':' . $proxySettings['pass'])];

		return $ret;
	}
}