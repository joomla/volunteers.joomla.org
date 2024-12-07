<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
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

		// Set $filename to kickstart's source file
		$filename = Platform::getInstance()->get_installer_images_path() . '/kickstart.txt';

		// Post-process the file
		$this->setSubstep('kickstart.php');

		if (!@file_exists($filename) || !is_file($filename))
		{
			Factory::getLog()->warning(
				sprintf(
					'Failed to upload kickstart.php. Missing file %s',
					$filename
				)
			);

			// Indicate we're done.
			return true;
		}

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