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

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;

/**
 * DreamObjects is a sub-case of the Amazon S3 engine with a custom endpoint
 *
 * @package Akeeba\Engine\Postproc
 */
class Dreamobjects extends Amazons3
{
	/**
	 * Used in log messages.
	 *
	 * @var  string
	 */
	protected $engineLogName = 'DreamObjects';

	/**
	 * The prefix to use for volatile key storage
	 *
	 * @var  string
	 */
	protected $volatileKeyPrefix = 'volatile.postproc.dreamobjects.';

	public function downloadToBrowser($remotePath)
	{
		$url = parent::downloadToBrowser($remotePath);

		// We need to inject the bucket name into the download path since DreamObjects doesn't use virtual-hosting-style access
		$engineConfig = $this->getEngineConfiguration();
		$bucket       = $engineConfig['bucket'];
		$bucket       = str_replace('/', '', $bucket);

		$url = str_replace('https://objects-us-east-1.dream.io/', 'https://objects-us-east-1.dream.io/' . $bucket . '/', $url);

		return $url;
	}

	/**
	 * Get the configuration information for this post-processing engine
	 *
	 * @return  array
	 */
	protected function getEngineConfiguration(): array
	{
		$config   = Factory::getConfiguration();
		$endpoint = "objects-us-east-1.dream.io";

		Factory::getLog()->info("DreamObjects: using S3 compatible endpoint $endpoint");

		$ret = [
			'accessKey'                 => $config->get('engine.postproc.dreamobjects.accesskey', ''),
			'secretKey'                 => $config->get('engine.postproc.dreamobjects.secretkey', ''),
			'token'                     => '',
			'useSSL'                    => $config->get('engine.postproc.dreamobjects.usessl', 1),
			'dualStack'                 => 0,
			'customEndpoint'            => $endpoint,
			'signatureMethod'           => 'v2',
			'useLegacyPathAccess'       => false,
			'region'                    => '',
			'disableMultipart'          => 1,
			'bucket'                    => $config->get('engine.postproc.dreamobjects.bucket', null),
			'directory'                 => $config->get('engine.postproc.dreamobjects.directory', null),
			'rrs'                       => 0,
			'lowercase'                 => $config->get('engine.postproc.dreamobjects.lowercase', 1),
			'alternateDateHeaderFormat' => true,
			'useHTTPDateHeader'         => true,
			'preSignedBucketInURL'      => false,
		];

		if ($ret['lowercase'] && !empty($ret['bucket']))
		{
			$ret['bucket'] = strtolower($ret['bucket']);
		}

		return $ret;
	}
}
