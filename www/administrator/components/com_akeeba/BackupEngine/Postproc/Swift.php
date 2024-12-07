<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Postproc\Connector\Swift as SwiftConnector;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;
use RuntimeException;

/**
 * A post processing engine used to upload files to OpenStack Swift object storage
 */
class Swift extends Base
{
	public function __construct()
	{
		$this->supportsDelete            = true;
		$this->supportsDownloadToFile    = true;
		$this->supportsDownloadToBrowser = false;
	}

	public function processPart($localFilepath, $remoteBaseName = null)
	{
		/** @var SwiftConnector $connector */
		$connector = $this->getConnector();
		$settings  = $this->getSettings();

		// Calculate relative remote filename
		$filename  = empty($remoteBaseName) ? basename($localFilepath) : $remoteBaseName;
		$directory = $settings['directory'];

		if (!empty($directory) && ($directory != '/'))
		{
			$filename = $directory . '/' . $filename;
		}

		// Store the absolute remote path in the class property
		$this->remotePath = $filename;

		// Upload the file
		Factory::getLog()->debug(sprintf("Uploading %s", basename($localFilepath)));
		$input = [
			'file' => $localFilepath,
		];
		$connector->putObject($input, $filename, 'application/octet-stream');

		return true;
	}

	public function delete($path)
	{
		/** @var SwiftConnector $connector */
		$connector = $this->getConnector();

		// Delete the file
		$connector->deleteObject($path);
	}

	public function downloadToFile($remotePath, $localFile, $fromOffset = null, $length = null)
	{
		/** @var SwiftConnector $connector */
		$connector = $this->getConnector();

		// Do we need to set a range header?
		$headers = [];

		if (!is_null($fromOffset) && is_null($length))
		{
			$headers['Range'] = 'bytes=' . $fromOffset;
		}
		elseif (!is_null($fromOffset) && !is_null($length))
		{
			$headers['Range'] = 'bytes=' . $fromOffset . '-' . ($fromOffset + $length - 1);
		}
		elseif (!is_null($length))
		{
			$headers['Range'] = 'bytes=0-' . ($fromOffset + $length);
		}

		if (!empty($headers))
		{
			Factory::getLog()->debug(sprintf("Sending Range header «%s»", $headers['Range']));
		}

		$fp = @fopen($localFile, 'w');

		if ($fp === false)
		{
			throw new RuntimeException(sprintf("Can't open %s for writing", $localFile));
		}

		try
		{
			$connector->downloadObject($remotePath, $fp, $headers);
		}
		finally
		{
			$this->conditionalFileClose($fp);
		}
	}

	/**
	 * Returns the post-processing engine settings in array format. If something is amiss it returns boolean false.
	 *
	 * @return array
	 */
	protected function getSettings()
	{
		// Retrieve engine configuration data
		$config = Factory::getConfiguration();

		$version      = trim($config->get('engine.postproc.swift.keystone_version', 'v2'));
		$authurl      = trim($config->get('engine.postproc.swift.authurl', ''));
		$tenantid     = trim($config->get('engine.postproc.swift.tenantid', ''));
		$domain       = trim($config->get('engine.postproc.swift.domain', 'default'));
		$username     = trim($config->get('engine.postproc.swift.username', ''));
		$password     = trim($config->get('engine.postproc.swift.password', ''));
		$containerurl = $config->get('engine.postproc.swift.containerurl', 0);
		$directory    = $config->get('volatile.postproc.directory', null);

		if (empty($directory))
		{
			$directory = $config->get('engine.postproc.swift.directory', 0);
		}

		// Sanity checks
		if (empty($authurl))
		{
			throw new BadConfiguration('You have not set up your Authentication URL');
		}

		if (empty($tenantid))
		{
			throw new BadConfiguration('You have not set up your Tenant ID');
		}

		if (empty($username))
		{
			throw new BadConfiguration('You have not set up your OpenStack Username');
		}

		if (empty($password))
		{
			throw new BadConfiguration('You have not set up your OpenStack Password');
		}

		if (empty($containerurl))
		{
			throw new BadConfiguration('You have not set up your Container URL');
		}

		if (!function_exists('curl_init'))
		{
			throw new BadConfiguration('cURL is not enabled, please enable it in order to post-process your archives');
		}

		// Fix the directory name, if required
		$directory = empty($directory) ? '' : $directory;
		$directory = trim($directory);
		$directory = ltrim(Factory::getFilesystemTools()->TranslateWinPath($directory), '/');
		$directory = Factory::getFilesystemTools()->replace_archive_name_variables($directory);
		$config->set('volatile.postproc.directory', $directory);

		return [
			'version'      => $version,
			'authurl'      => $authurl,
			'tenantid'     => $tenantid,
			'domain'       => $domain,
			'username'     => $username,
			'password'     => $password,
			'containerurl' => $containerurl,
			'directory'    => $directory,
		];
	}

	protected function makeConnector()
	{
		$settings = $this->getSettings();

		// Create the API connector object
		$connector = new SwiftConnector($settings['version'], $settings['authurl'], $settings['tenantid'], $settings['username'], $settings['password'], $settings['domain']);
		$connector->setStorageEndpoint($settings['containerurl']);

		// Authenticate
		Factory::getLog()->debug('Authenticating to OpenStack Swift');
		$connector->getToken();

		return $connector;
	}
}
