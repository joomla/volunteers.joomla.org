<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\Model;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Exception;
use FOF30\Model\Model;
use LogicException;

/**
 * Model for the archive re-upload to remote storage feature
 */
class Upload extends Model
{
	/**
	 * Upload an archive part to remote storage
	 *
	 * @param   int  $id    Backup record ID
	 * @param   int  $part  Part to upload. 0 is the first part (e.g. .jpa), 1 the second (e.g. .j01) etc.
	 * @param   int  $frag  Fragment (chunk) of the part to upload. The first fragment is 0.
	 *
	 * @return  bool  True if the upload of this file is done, false if more work is necessary
	 *
	 * @throws Exception
	 */
	public function upload($id, $part, $frag)
	{
		// Initialize
		$stat         = Platform::getInstance()->get_statistics($id);
		$returnStatus = false;

		// The number of parts must be AT LEAST 1. If it's 0 it's from a backup record of a VERY old release.
		$stat['multipart'] = max($stat['multipart'], 1);

		// Load the Factory
		$savedFactory = $this->container->platform->getSessionVar('upload_factory', null, 'akeeba');
		$logger       = Factory::getLog();

		if ($savedFactory)
		{
			Factory::unserialize($savedFactory);
			Platform::getInstance()->load_configuration($stat['profile_id']);
			$logger->open('backend');
			$logger->info(sprintf(
				'Continuing transfer of backup record #%d (part %d, fragment %d)',
				$id, $part, $frag
			));
		}
		else
		{
			Platform::getInstance()->load_configuration($stat['profile_id']);
			$logger->reset('backend');
			$logger->info(sprintf(
				"Starting transfer of the files of backup record #%d to remote storage using post-processing engine %s.",
				$id,
				Factory::getConfiguration()->get('akeeba.advanced.postproc_engine')));
		}

		// Load the post-processing engine
		$config      = Factory::getConfiguration();
		$engine_name = $config->get('akeeba.advanced.postproc_engine');
		$engine      = Factory::getPostprocEngine($engine_name);

		// Get the timer and reset it
		$timer = Factory::getTimer();
		$timer->resetTime();

		while ($timer->getTimeLeft() > 0.01)
		{
			// Start counting the time for this part
			$startTime = $timer->getRunningTime();

			// Calculate the filenames
			$local_filename = $stat['absolute_path'];
			$basename       = basename($local_filename);
			$extension      = strtolower(str_replace(".", "", strrchr($basename, ".")));
			$new_extension  = $extension;

			if ($part > 0)
			{
				$new_extension = substr($extension, 0, 1) . sprintf('%02u', $part);
			}

			$local_filename = substr($local_filename, 0, -strlen($extension)) . $new_extension;

			// Start uploading
			try
			{
				$result = $engine->processPart($local_filename);
			}
			catch (Exception $e)
			{
				$this->container->platform->setSessionVar('upload_factory', null, 'akeeba');

				throw $e;
			}

			if (!is_bool($result))
			{
				throw new LogicException(sprintf("Unexpected result from %s: %s", get_class($engine), print_r($result, true)));
			}

			// Stop the running timer
			$endTime     = $timer->getRunningTime();
			$elapsedTime = $endTime - $startTime;

			// What is the next part and frag to upload?
			$frag++;

			if ($result === true)
			{
				$part++;
				$frag = 0;
			}

			// Calculate the remote filename
			$remote_filename = $config->get('akeeba.advanced.postproc_engine', '') . '://';
			$remote_filename .= $engine->getRemotePath();

			/**
			 * Am I already finished?
			 *
			 * Parts are uploaded in the order 0, 1, 2, ... Part 0 is the .jpa/.jps/.zip file. Part 1 is .j01/.z01 and
			 * so forth.
			 *
			 * $stat['multipart'] contains the total number of parts. Let's say it's 5. This means that we need to
			 * upload parts 0, 1, 2, 3 and 4. When $part is 5 (or greater, even though that'd be a bug) we must stop
			 * at one.
			 *
			 * In the edge case where $stat['multipart'] is 1 the same thing applies. The only part we must transfer is
			 * 0. If $part is 1 we're done.
			 *
			 * Therefore the condition for checking whether we're all done is that $part is greater than zero (we have
			 * already uploaded the first and possibly only part) AND $part is greater than OR EQUAL to the total number
			 * of parts in the backup archive set.
			 */
			if (($part >= 0) && ($part >= $stat['multipart']))
			{
				Factory::getLog()->info(sprintf(
					'Finished transfer of backup record #%d',
					$id
				));

				// Update stats with remote filename
				$data = [
					'remote_filename' => $remote_filename,
				];

				Platform::getInstance()->set_or_update_statistics($id, $data);

				// Indicate that we're all done
				$returnStatus = true;

				break;
			}

			// Do I have enough time for another fragment's upload?
			if ($timer->getTimeLeft() < (2.0 * $elapsedTime))
			{
				break;
			}

			$logger->info(sprintf(
				'Continuing transfer of backup record #%d (part %d, fragment %d)',
				$id, $part, $frag
			));
		}

		// Serialize the factory
		$this->container->platform->setSessionVar('upload_factory', $returnStatus ? null : Factory::serialize(), 'akeeba');

		// Should I tell the user that we have more work to do?
		if (!$returnStatus)
		{
			Factory::getLog()->info(sprintf(
				'The transfer of backup record #%d will continue on the next step',
				$id
			));
		}

		// Update the Model state
		$this->setState('id', $id);
		$this->setState('part', $part);
		$this->setState('frag', $frag);
		$this->setState('stat', $stat);
		$this->setState('remotename', $remote_filename);

		$timer->enforce_min_exec_time();

		return $returnStatus;
	}
}
