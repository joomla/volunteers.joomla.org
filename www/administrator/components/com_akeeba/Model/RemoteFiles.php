<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\Model;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Admin\Model\Mixin\GetErrorsFromExceptions;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Postproc\Exception\RangeDownloadNotSupported;
use Exception;
use FOF30\Model\Model;
use Joomla\CMS\Language\Text as JText;
use RuntimeException;

class RemoteFiles extends Model
{
	use GetErrorsFromExceptions;

	/**
	 * The fragment size for chunked downloads. Default: 1MB
	 */
	const DOWNLOAD_FRAGMENT_SIZE = 1048576;

	/**
	 * Returns information about the capabilities of the post-processing engine used with a specific backup record.
	 *
	 * @param   int  $id
	 *
	 * @return  array
	 */
	public function getCapabilities($id)
	{
		$postProcEngineName = $this->getPostProcEngineNameForRecord($id, false);
		if ($postProcEngineName == 'none')
		{
			// There's no file stored remotely. Get the post-proc engine from the profile.
			$postProcEngineName = $this->getPostProcEngineNameForRecord($id, true);
		}
		$postProcEngine = Factory::getPostprocEngine($postProcEngineName);


		$capabilities = [
			'engine'            => $postProcEngineName,
			'delete'            => $postProcEngine->supportsDelete(),
			'downloadToFile'    => $postProcEngine->supportsDownloadToFile(),
			'downloadToBrowser' => $postProcEngine->supportsDownloadToBrowser(),
			'inlineDownload'    => $postProcEngine->doesInlineDownloadToBrowser(),
		];

		return $capabilities;
	}

	/**
	 * Returns the definitions of the Manage Remotely Stored Files action for a given backup record.
	 *
	 * Returns an icon definition list for the applicable actions on this backup record
	 *
	 * @param   int  $id  The backup record ID to return action definitions for
	 *
	 * @return  array The action definitions
	 */
	public function getActions($id)
	{
		$actions = [
			'downloadToFile'    => false,
			'delete'            => false,
			'downloadToBrowser' => 0,
		];

		$postProcEngineName = $this->getPostProcEngineNameForRecord($id);
		$postProcEngine     = Factory::getPostprocEngine($postProcEngineName);
		$stat               = Platform::getInstance()->get_statistics($id);

		// Does the engine support local d/l and we need to d/l the file locally?
		if ($postProcEngine->supportsDownloadToFile() && !$stat['filesexist'])
		{
			$actions['downloadToFile'] = true;
		}

		// Does the engine support remote deletes?
		if ($postProcEngine->supportsDelete())
		{
			$actions['delete'] = true;
		}

		// Does the engine support downloads to browser?
		if ($postProcEngine->supportsDownloadToBrowser())
		{
			$actions['downloadToBrowser'] = max(1, $stat['multipart']);
		}

		return $actions;
	}

	/**
	 * Downloads a remote file back to the site's server
	 *
	 * @param   int  $id    The backup record ID to fetch back to server
	 * @param   int  $part  Which part file of the backup record should I fetch back?
	 * @param   int  $frag  Which fragment of the backup record should I fetch back?
	 *
	 * @return  bool  true when we're done downloading, false if we have more work to do
	 * @throws  Exception  On error
	 */
	function downloadToServer($id, $part, $frag)
	{
		// Gather the necessary information to perform the download
		$backupRecord        = Platform::getInstance()->get_statistics($id);
		$remoteFilenameParts = explode('://', $backupRecord['remote_filename']);
		$engine              = Factory::getPostprocEngine($remoteFilenameParts[0]);
		$remoteFilepath      = $remoteFilenameParts[1];
		// Note that single part archives have $backupRecord['multipart'] == 0. We need that to be 1.
		$totalNumberOfParts = max($backupRecord['multipart'], 1);

		// Timer initialization
		$config = Factory::getConfiguration();
		$timer  = Factory::getTimer();
		$start  = $timer->getRunningTime();

		// If we are starting a new download we need to reset the statistics in the session
		if ($part == -1)
		{
			// Total size of the files to download
			$this->container->platform->setSessionVar('dl_totalsize', $backupRecord['total_size'], 'akeeba');
			// Cummulative bytes downloaded so far
			$this->container->platform->setSessionVar('dl_donesize', 0, 'akeeba');
			// Convert part -1 to 0, indicating it's the very first part
			$part = 0;
			// Indicate this is going to be the very first fragment of the file to download
			$frag = -1;
		}

		while (true)
		{
			/**
			 * If we are trying to download a part that's higher than the number of parts in the archive we're all done.
			 *
			 * Remember: $part is the 0-based index (first part is zero). $totalNumberOfParts is the 1-based count of
			 * items in the collection.
			 */
			if ($part >= $totalNumberOfParts)
			{
				// Fall through to the return code which also updates the backup record
				break;
			}

			// Get the remote and local filenames
			$basename       = basename($remoteFilepath);
			$extension      = strtolower(str_replace(".", "", strrchr($basename, ".")));
			$partExtension  = ($part == 0) ? $extension : substr($extension, 0, 1) . sprintf('%02u', $part);
			$remoteFilepath = substr($remoteFilenameParts[1], 0, -strlen($extension)) . $partExtension;
			$localFilepath  = $config->get('akeeba.basic.output_directory') . '/' . basename($remoteFilepath);

			/**
			 * If $frag == -1 I am starting to download a new backup archive part. Therefore I need to initialize the
			 * local file.
			 */
			if ($frag == -1)
			{
				Platform::getInstance()->unlink($localFilepath);

				$fp = @fopen($localFilepath, 'wb');

				if ($fp === false)
				{
					throw new RuntimeException(JText::sprintf('COM_AKEEBA_REMOTEFILES_ERR_CANTOPENFILE', $localFilepath), 500);
				}

				@fclose($fp);

				// Set the frag to 0 to let the download proceed correctly.
				$frag = 0;
			}

			// Calculate the offset to start downloading from and try to download the next fragment
			$from           = $frag * self::DOWNLOAD_FRAGMENT_SIZE + 1;
			$tempFilepath   = $localFilepath . '.tmp';
			$allowMultipart = true;

			try
			{
				// Try to do a multipart download. If it's not supported, do a single part download.
				try
				{
					$engine->downloadToFile($remoteFilepath, $tempFilepath, $from, self::DOWNLOAD_FRAGMENT_SIZE);
				}
				catch (RangeDownloadNotSupported $e)
				{
					$allowMultipart = false;

					$engine->downloadToFile($remoteFilepath, $tempFilepath);
				}
			}
			catch (Exception $e)
			{
				// Failed download
				if (
					(($part < $backupRecord['multipart']) || (($backupRecord['multipart'] == 0) && ($part == 0))) &&
					($frag == 0)
				)
				{
					// Failure to download the part's beginning = failure to download. Period.
					throw new RuntimeException(JText::_('COM_AKEEBA_REMOTEFILES_ERR_CANTDOWNLOAD'), 500, $e);
				}

				// We tried reading past the end of a part file. Go to the next part.
				$part++;
				$frag = -1;
				continue;
			}

			// Add the currently downloaded fragment's size to the running total size of downloaded files
			$downloadedFragmentSize = (int) @filesize($tempFilepath);
			$currentTotal           = $this->container->platform->getSessionVar('dl_donesize', 0, 'akeeba');
			$this->container->platform->setSessionVar('dl_donesize', $currentTotal + $downloadedFragmentSize, 'akeeba');

			if (!$allowMultipart)
			{
				// Single part download: move the temporary file to the local file
				$this->moveTempFile($tempFilepath, $localFilepath);

				// Go to the start of the next part
				$part++;
				$frag = -1;

				break;
			}


			// Multipart download: try to combine the just downloaded fragment (in a temp file) with the local file
			$this->combineTemporaryAndLocalFile($tempFilepath, $localFilepath);

			// Indicate we need to download the next fragment
			$frag++;

			// Do I have enough time to try another fragment?
			$end          = $timer->getRunningTime();
			$requiredTime = max(1.1 * ($end - $start), !isset($requiredTime) ? 1.0 : $requiredTime);

			if ($timer->getTimeLeft() < $requiredTime)
			{
				break;
			}

			$start = $end;
		}

		// We set these variables in the model state to allow the View to access them
		$this->setState('id', $id);
		$this->setState('part', $part);
		$this->setState('frag', $frag);

		/**
		 * If we are trying to download a part that's higher than the number of parts in the archive we're all done.
		 *
		 * Remember: $part is the 0-based index (first part is zero). $totalNumberOfParts is the 1-based count of
		 * items in the collection.
		 */
		if ($part >= $totalNumberOfParts)
		{
			// Update the backup record, indicating the files now exist locally
			$backupRecord['filesexist'] = 1;

			Platform::getInstance()->set_or_update_statistics($id, $backupRecord);

			// Tell the called that we're all done with the downloads.
			return true;
		}

		// Tell the caller more steps are required to download the files
		return false;
	}

	/**
	 * Delete the files stored in the remote storage service
	 *
	 * @param   int  $id    The backup record we're deleting remote stored files for
	 * @param   int  $part  The backup part whose remotely stored file we're deleting
	 *
	 * @return  array  Information about the progress
	 * @throws  Exception  On error
	 */
	public function deleteRemoteFiles($id, $part)
	{
		$ret = [
			'finished' => false,
			'id'       => $id,
			'part'     => $part,
		];

		// Gather the necessary information to perform the delete
		$stat                = Platform::getInstance()->get_statistics($id);
		$remoteFilenameParts = explode('://', $stat['remote_filename']);
		$engine              = Factory::getPostprocEngine($remoteFilenameParts[0]);
		$remote_filename     = $remoteFilenameParts[1];

		// Start timing ourselves
		$timer = Factory::getTimer(); // The core timer object
		$start = $timer->getRunningTime(); // Mark the start of this download
		$break = false; // Don't break the step

		while ($timer->getTimeLeft() && !$break && ($part < $stat['multipart']))
		{
			// Get the remote filename
			$basename  = basename($remote_filename);
			$extension = strtolower(str_replace(".", "", strrchr($basename, ".")));

			$new_extension = $extension;

			if ($part > 0)
			{
				$new_extension = substr($extension, 0, 1) . sprintf('%02u', $part);
			}

			$remote_filename = substr($remote_filename, 0, -strlen($extension)) . $new_extension;

			// Do we have to initialize the process?
			if ($part == -1)
			{
				// Init
				$part = 0;
			}

			// Try to delete the part
			$required_time = 1.0;

			try
			{
				$engine->delete($remote_filename);
			}
			catch (Exception $e)
			{
				throw new RuntimeException(JText::_('COM_AKEEBA_REMOTEFILES_ERR_CANTDELETE'), 500, $e);
			}

			// Successful delete
			$end = $timer->getRunningTime();
			$part++;

			// Do we predict that we have enough time?
			$required_time = max(1.1 * ($end - $start), $required_time);

			if ($timer->getTimeLeft() < $required_time)
			{
				$break = true;
			}

			$start = $end;
		}

		if ($part >= $stat['multipart'])
		{
			// Just finished!
			$stat['remote_filename'] = '';

			Platform::getInstance()->set_or_update_statistics($id, $stat);
			$ret['finished'] = true;

			return $ret;
		}

		// More work to do...
		$ret['id']   = $id;
		$ret['part'] = $part;

		return $ret;
	}

	/**
	 * Appends the contents of the temporary file to the local file
	 *
	 * @param   string  $tempFilepath   Temporary file to read from
	 * @param   string  $localFilepath  Local file to append to
	 * @param   int     $chunkLength    Perform the appent up to this many bytes at a time
	 *
	 * @return  void
	 *
	 * @throws  RuntimeException  If something has gone wrong
	 */
	private function combineTemporaryAndLocalFile($tempFilepath, $localFilepath, $chunkLength = 262144)
	{
		try
		{
			$localFilePointer = @fopen($localFilepath, 'ab');

			if ($localFilePointer === false)
			{
				throw new RuntimeException(JText::sprintf('COM_AKEEBA_REMOTEFILES_ERR_CANTOPENFILE', $localFilepath), 500);
			}

			$tempFilePointer = fopen($tempFilepath, 'rb');

			// Um, weird, I can't open the temp file.
			if ($tempFilePointer === false)
			{
				throw new RuntimeException(sprintf('Can not read data from temporary file %s', $tempFilepath));
			}

			while (!feof($tempFilePointer))
			{
				$data = fread($tempFilePointer, $chunkLength);

				if ($data === false)
				{
					throw new RuntimeException(sprintf('Can not read data from temporary file %s', $tempFilepath));
				}

				$dataLength = $this->akstrlen($data);
				$written    = fwrite($localFilePointer, $data);

				if ($written != $dataLength)
				{
					throw new RuntimeException(JText::sprintf('COM_AKEEBA_REMOTEFILES_ERR_CANTOPENFILE', $localFilepath), 500);
				}
			}
		}
		finally
		{
			if (isset($tempFilePointer) && is_resource($tempFilePointer))
			{
				fclose($tempFilePointer);
			}

			if (isset($localFilePointer) && is_resource($localFilePointer))
			{
				fclose($localFilePointer);
			}

			Platform::getInstance()->unlink($tempFilepath);
		}
	}

	private function akstrlen($string)
	{
		return function_exists('mb_strlen') ? mb_strlen($string, '8bit') : strlen($string);
	}

	/**
	 * Move a temporary file into a local file path
	 *
	 * @param   string  $tempFilepath   The temporary file to move from
	 * @param   string  $localFilepath  The local file to move into
	 *
	 * @return  void
	 * @throws  RuntimeException  If the move fails
	 */
	private function moveTempFile($tempFilepath, $localFilepath)
	{
		try
		{
			// Try to unlink the existing local file (it should exist, we already tried creating it as a zero byte file)
			Platform::getInstance()->unlink($localFilepath);

			// Move the temporary file to the local file
			if (!Platform::getInstance()->move($tempFilepath, $localFilepath))
			{
				throw new RuntimeException(JText::sprintf('COM_AKEEBA_REMOTEFILES_ERR_CANTOPENFILE', $localFilepath));
			}

		}
		finally
		{
			// Delete the temporary file
			Platform::getInstance()->unlink($tempFilepath);
		}
	}

	/**
	 * Returns the post-processing engine name for the given backup record ID.
	 *
	 * @param   int   $id                      The backup record ID
	 * @param   bool  $profileOverridesRecord  Return the engine name from the backup profile, not the backup record
	 *
	 * @return string
	 */
	private function getPostProcEngineNameForRecord($id, $profileOverridesRecord = false)
	{
		// Load the stats record
		$stat = Platform::getInstance()->get_statistics($id);

		if (empty($stat))
		{
			return 'none';
		}

		if ($profileOverridesRecord)
		{
			/** @var Profiles $profilesModel */
			$profilesModel = $this->container->factory->model('Profiles')->tmpInstance();
			$postProcPerProfile = $profilesModel->getPostProcessingEnginePerProfile();
			$profileId = $stat['profile_id'];

			if (!array_key_exists($profileId, $postProcPerProfile))
			{
				return 'none';
			}

			return $postProcPerProfile[$profileId];
		}

		// Get the post-proc engine from the remote location
		$remote_filename = $stat['remote_filename'];

		if (empty($remote_filename))
		{
			return 'none';
		}

		$remoteFilenameParts = explode('://', $remote_filename, 2);

		return $remoteFilenameParts[0];
	}
}
