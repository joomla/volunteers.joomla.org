<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Postproc\Connector\Cloudfiles as ConnectorCloudfiles;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;
use RuntimeException;

/**
 * A post processing engine used to upload files to RackSpace CloudFiles
 */
class Cloudfiles extends Base
{
	/**
	 * Public constructor. Initialises the advertised properties of this processing engine
	 */
	public function __construct()
	{
		$this->supportsDelete            = true;
		$this->supportsDownloadToFile    = true;
		$this->supportsDownloadToBrowser = false;
	}

	public function processPart($localFilepath, $remoteBaseName = null)
	{
		$settings  = $this->getEngineConfiguration();
		$directory = $settings['directory'];

		// Calculate relative remote filename
		$filename = empty($remoteBaseName) ? basename($localFilepath) : $remoteBaseName;

		if (!empty($directory) && ($directory != '/'))
		{
			$filename = $directory . '/' . $filename;
		}

		// Store the absolute remote path in the class property
		$this->remotePath = $filename;

		/** @var ConnectorCloudfiles $connector */
		$connector = $this->getConnector();

		// Cache the tokens in the volatile engine parameters to speed up further uploads
		Factory::getConfiguration()->set('volatile.postproc.cloudfiles.options', $connector->getCurrentOptions());

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
		/** @var ConnectorCloudfiles $connector */
		$connector = $this->getConnector();
		$connector->deleteObject($path);
	}

	public function downloadToFile($remotePath, $localFile, $fromOffset = null, $length = null)
	{
		/** @var ConnectorCloudfiles $connector */
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

		$fp = @fopen($localFile, 'w');

		if ($fp === false)
		{
			throw new RuntimeException(sprintf("Can't open %s for writing", $localFile));
		}

		$connector->downloadObject($remotePath, $fp, $headers);

		$this->conditionalFileClose($fp);
	}

	/**
	 * Returns the post-processing engine settings in array format. If something is amiss it returns boolean false.
	 *
	 * @return  array
	 */
	protected function getEngineConfiguration()
	{
		// Retrieve engine configuration data
		$config = Factory::getConfiguration();

		$username  = trim($config->get('engine.postproc.cloudfiles.username', ''));
		$apikey    = trim($config->get('engine.postproc.cloudfiles.apikey', ''));
		$container = $config->get('engine.postproc.cloudfiles.container', 0);
		$directory = $config->get('volatile.postproc.directory', null);

		if (empty($directory))
		{
			$directory = $config->get('engine.postproc.cloudfiles.directory', 0);
		}

		// Sanity checks
		if (empty($username))
		{
			throw new BadConfiguration('You have not set up your CloudFiles user name');
		}

		if (empty($apikey))
		{
			throw new BadConfiguration('You have not set up your CoudFiles API Key');
		}

		if (empty($container))
		{
			throw new BadConfiguration('You have not set up your CloudFiles container');
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
			'username'  => $username,
			'apikey'    => $apikey,
			'container' => $container,
			'directory' => $directory,
		];
	}

	protected function makeConnector()
	{
		$settings = $this->getEngineConfiguration();

		// Do I have authorisation options already stored in the volatile settings?
		$options = Factory::getConfiguration()->get('volatile.postproc.cloudfiles.options', [], false);
		$options = array_merge([
			'container' => $settings['container'],
		], $options);

		$connector = new ConnectorCloudfiles($settings['username'], $settings['apikey'], $options);

		Factory::getLog()->debug('Authenticating to CloudFiles');

		$connector->authenticate();

		return $connector;

	}
}
