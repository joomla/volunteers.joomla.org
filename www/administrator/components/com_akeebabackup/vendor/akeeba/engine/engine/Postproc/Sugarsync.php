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
use Akeeba\Engine\Postproc\Connector\Sugarsync as ConnectorSugarsync;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;
use Akeeba\Engine\Postproc\Exception\RangeDownloadNotSupported;
use Exception;

/**
 * SugarSync post-processing class for Akeeba Backup
 *
 * @obsolete  SugarSync discontinued its consumer file-sync service and its public REST API
 *            (https://api.sugarsync.com) is no longer generally available. This engine can therefore no longer be
 *            exercised against a live service, and no integration test exists for it (see GitHub issue #146). It is
 *            retained only so that pre-existing backup profiles do not break on load; do not target it for new backups
 *            and do not expect it to function. Use any of the other, actively-supported remote storage engines instead.
 */
class Sugarsync extends Base
{
	public function __construct()
	{
		$this->supportsDelete            = true;
		$this->supportsDownloadToFile    = true;
		$this->supportsDownloadToBrowser = false;
	}

	public function processPart($localFilepath, $remoteBaseName = null)
	{
		/** @var ConnectorSugarsync $connector */
		$connector = $this->getConnector();
		$settings  = $this->getSettings();

		// Calculate relative remote filename
		$filename  = empty($remoteBaseName) ? basename($localFilepath) : $remoteBaseName;
		$directory = $settings['directory'];

		if (empty($directory) || ($directory == '/'))
		{
			$directory = '';
		}

		// Store the absolute remote path in the class property
		$this->remotePath = $directory . '/' . $filename;

		$connector->uploadFile($directory, $filename, $localFilepath);

		return true;
	}

	public function delete($path)
	{
		/** @var ConnectorSugarsync $connector */
		$connector = $this->getConnector();

		$connector->deleteFile($path);
	}

	public function downloadToFile($remotePath, $localFile, $fromOffset = null, $length = null)
	{
		if (!is_null($fromOffset) || !is_null($length))
		{
			throw new RangeDownloadNotSupported();
		}

		/** @var ConnectorSugarsync $connector */
		$connector = $this->getConnector();

		$connector->downloadFile($remotePath, null, $localFile);
	}

	/**
	 * Returns the engine configuration
	 *
	 * @return  array
	 *
	 * @throws  Exception  If something is wrong
	 */
	protected function getSettings()
	{
		// Retrieve engine configuration data
		$config = Factory::getConfiguration();

		$access           = trim($config->get('engine.postproc.sugarsync.access', ''));
		$private          = trim($config->get('engine.postproc.sugarsync.private', ''));
		$email            = trim($config->get('engine.postproc.sugarsync.email', ''));
		$password         = trim($config->get('engine.postproc.sugarsync.password', ''));
		$defaultDirectory = $config->get('engine.postproc.sugarsync.directory', '');
		$directory        = $config->get('volatile.postproc.directory', $defaultDirectory);

		// Sanity checks
		if (empty($access))
		{
			throw new BadConfiguration('You have not set up your SugarSync Access Key ID');
		}

		if (empty($private))
		{
			throw new BadConfiguration('You have not set up your SugarSync Private Access Key');
		}

		if (empty($email))
		{
			throw new BadConfiguration('You have not set up your SugarSync email address');
		}

		if (empty($password))
		{
			throw new BadConfiguration('You have not set up your SugarSync password');
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
			'access'    => $access,
			'private'   => $private,
			'email'     => $email,
			'password'  => $password,
			'directory' => $directory,
		];
	}

	protected function makeConnector()
	{
		$settings = $this->getSettings();

		return new ConnectorSugarsync($settings);
	}


}
