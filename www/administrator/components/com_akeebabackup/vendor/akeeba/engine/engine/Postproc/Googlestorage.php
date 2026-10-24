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
 * Google Storage is a sub-case of the Amazon S3 engine with a custom endpoint
 *
 * @package Akeeba\Engine\Postproc
 */
class Googlestorage extends Amazons3
{
	public function __construct()
	{
		parent::__construct();

		$this->engineLogName             = 'Google Storage';
		$this->volatileKeyPrefix         = 'volatile.postproc.googlestorage.';
		$this->supportsDownloadToBrowser = false;

		Factory::getLog()->warning("The old Google Storage integration you are currently using, the one that makes use of the legacy S3 API, is deprecated and will be removed in a future version. Please switch to the new Upload to Google Storage (JSON API) integration.");

	}

	/**
	 * Get the configuration information for this post-processing engine
	 *
	 * @return  array
	 */
	protected function getEngineConfiguration(): array
	{
		$config   = Factory::getConfiguration();
		$endpoint = 'storage.googleapis.com';

		Factory::getLog()->info("GoogleStorage: using S3 compatible endpoint $endpoint");

		$ret = [
			'accessKey'                 => $config->get('engine.postproc.googlestorage.accesskey', ''),
			'secretKey'                 => $config->get('engine.postproc.googlestorage.secretkey', ''),
			'token'                     => '',
			'useSSL'                    => $config->get('engine.postproc.googlestorage.usessl', 1),
			'dualStack'                 => 0,
			'customEndpoint'            => $endpoint,
			'signatureMethod'           => 'v2',
			'useLegacyPathAccess'       => false,
			'region'                    => '',
			'disableMultipart'          => 1,
			'bucket'                    => $config->get('engine.postproc.googlestorage.bucket', null),
			'directory'                 => $config->get('engine.postproc.googlestorage.directory', null),
			'rrs'                       => 0,
			'lowercase'                 => $config->get('engine.postproc.googlestorage.lowercase', 1),
			'alternateDateHeaderFormat' => false,
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
