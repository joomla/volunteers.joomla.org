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

/**
 * @package     Akeeba\Engine\Core\Domain\Finalizer
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Akeeba\Engine\Core\Domain\Finalizer;

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Exception;
use Akeeba\Engine\Psr\Log\LogLevel;

/**
 * Uploads Kickstart using the post-processing engine
 *
 * @since       9.3.1
 * @package     Akeeba\Engine\Core\Domain\Finalizer
 */
final class UploadKickstart extends AbstractFinalizer
{

	/**
	 * @inheritDoc
	 */
	public function __invoke()
	{
		$this->setStep('Post-processing Kickstart');
		$this->setSubstep('');

		$configuration = Factory::getConfiguration();

		// Do not run if we are not told to upload Kickstart
		$uploadKickstart = $configuration->get('akeeba.advanced.uploadkickstart', 0);

		if (!$uploadKickstart)
		{
			return true;
		}

		$engineName = $configuration->get('akeeba.advanced.postproc_engine');
		Factory::getLog()->debug("Loading post-processing engine object ($engineName)");
		$postProcEngine = Factory::getPostprocEngine($engineName);

		// Prefer XOR-encoded kickstart.dat (obfuscated to defeat host scanners); fall back to plain kickstart.txt.
		$xorKey        = 'ThisIsKickstartCoreWhichIsNotMaliciousYouCanDownloadItFromAkeebaDotComIfYouWant';
		$installerPath = Platform::getInstance()->get_installer_images_path();
		$encodedPath   = $installerPath . '/kickstart.dat';
		$plainPath     = $installerPath . '/kickstart.txt';
		$isTempFile    = false;

		if (@file_exists($encodedPath) && is_file($encodedPath))
		{
			$encoded    = file_get_contents($encodedPath);
			$fullKey    = str_repeat($xorKey, (int) ceil(strlen($encoded) / strlen($xorKey)));
			$filename   = tempnam(sys_get_temp_dir(), 'ksdat_');
			$isTempFile = true;
			file_put_contents($filename, $encoded ^ substr($fullKey, 0, strlen($encoded)));
		}
		elseif (@file_exists($plainPath) && is_file($plainPath))
		{
			$filename = $plainPath;
		}
		else
		{
			Factory::getLog()->warning(
				sprintf('Failed to upload kickstart.php. Missing file %s', $encodedPath)
			);

			// Indicate we're done.
			return true;
		}

		// Post-process the file
		$this->setSubstep('kickstart.php');

		$exception          = null;
		$finishedProcessing = false;

		try
		{
			$finishedProcessing = $postProcEngine->processPart($filename, 'kickstart.php');
		}
		catch (Exception $e)
		{
			$exception = $e;
		}
		finally
		{
			if ($isTempFile)
			{
				@unlink($filename);
			}
		}

		if (!is_null($exception))
		{
			Factory::getLog()->warning('Failed to upload kickstart.php');
			Factory::getLog()->warning('Error received from the post-processing engine:');
			$this->logErrorsFromException($exception, LogLevel::WARNING);
		}
		elseif ($finishedProcessing === true)
		{
			// The post-processing of this file ended successfully
			Factory::getLog()->info('Finished uploading kickstart.php');
			$configuration->set('volatile.postproc.filename', null);
		}

		// Indicate we're done
		return true;
	}
}