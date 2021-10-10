<?php
/*
 * Akeeba Engine
 * The PHP-only site backup engine
 *
 * @copyright Copyright (c)2006-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or, at your option, any later version
 * @package   akeebaengine
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