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
use DateTime;
use Exception;

final class LocalQuotas extends AbstractQuotaManagement
{

	/**
	 * Get all the backup records to apply quotas on.
	 *
	 * @return  array
	 *
	 * @since   9.3.1
	 */
	protected function getAllRecords(): array
	{
		// Get valid-looking backup ID's
		$validIDs = Platform::getInstance()->get_valid_backup_records(true) ?: [];

		// Create a list of valid files
		$allFiles   = [];
		$statistics = Factory::getStatistics();

		foreach ($validIDs as $id)
		{
			$stat = Platform::getInstance()->get_statistics($id);

			// Exclude frozen record from quota management
			if (isset($stat['frozen']) && $stat['frozen'])
			{
				Factory::getLog()->debug(
					sprintf(
						'Excluding frozen backup id %d from %s quota management',
						$id,
						$this->quotaType
					)
				);
				continue;
			}

			try
			{
				$backupstart = new DateTime($stat['backupstart']);
				$backupTS    = $backupstart->format('U');
				$backupDay   = $backupstart->format('d');
			}
			catch (Exception $e)
			{
				$backupTS  = 0;
				$backupDay = 0;
			}

			// Get the log file name
			$tag      = $stat['tag'];
			$backupId = $stat['backupid'] ?? '';
			$logName  = '';

			if (!empty($backupId))
			{
				$logName = 'akeeba.' . $tag . '.' . $backupId . '.log.php';
			}

			// Multipart processing
			$filenames = $statistics->get_all_filenames($stat, true);

			// Only process existing files
			if (is_null($filenames))
			{
				continue;
			}

			$filesize = 0;

			foreach ($filenames as $filename)
			{
				$filesize += @filesize($filename);
			}

			$allFiles[] = [
				'id'          => $id,
				'filenames'   => $filenames,
				'size'        => $filesize,
				'backupstart' => $backupTS,
				'day'         => $backupDay,
				'logname'     => $logName,
			];
		}

		return $allFiles;
	}

	/**
	 * Performs the actual removal.
	 *
	 * @param   array  $removeBackupIDs  The backup IDs which will have their files removed
	 * @param   array  $filesToRemove    The flat list of files to remove
	 * @param   array  $removeLogPaths   The flat list of log paths to remove
	 *
	 * @return  bool  True if we are done, false to come back in the next step of the engine
	 * @throws  Exception
	 * @since   9.3.1
	 */
	protected function processRemovals(array &$removeBackupIDs, array &$filesToRemove, array &$removeLogPaths): bool
	{
		$timer = Factory::getTimer();

		// Update the statistics record with the removed remote files
		if (!empty($removeBackupIDs))
		{
			Factory::getLog()->debug(
				sprintf(
					'Applying %s quotas: updating backup records',
					$this->quotaType
				)
			);
		}

		while (!empty($removeBackupIDs) && $timer->getTimeLeft() > 0)
		{
			$id   = array_shift($removeBackupIDs);
			$data = ['filesexist' => '0'];

			Platform::getInstance()->set_or_update_statistics($id, $data);
		}

		// Check if I have enough time
		if ($timer->getTimeLeft() <= 0)
		{
			return false;
		}

		// Apply quotas upon backup records
		if (!empty($filesToRemove) > 0)
		{
			Factory::getLog()->debug(
				sprintf(
					'Applying %s quotas: removing backup archives',
					$this->quotaType
				)
			);
		}

		while (!empty($filesToRemove) && $timer->getTimeLeft() > 0)
		{
			$file = array_shift($filesToRemove);

			if (@Platform::getInstance()->unlink($file))
			{
				continue;
			}

			Factory::getLog()->warning(
				sprintf(
					'Failed to remove old backup file %s',
					$file
				)
			);
		}

		// Check if I have enough time
		if ($timer->getTimeLeft() <= 0)
		{
			return false;
		}

		// Apply quotas to log files
		if (!empty($removeLogPaths))
		{
			Factory::getLog()->debug(
				sprintf(
					'Applying %s quotas: removing obsolete log files',
					$this->quotaType
				)
			);
			Factory::getLog()->debug('Removing obsolete log files');
		}

		while (!empty($removeLogPaths) && $timer->getTimeLeft() > 0)
		{
			$logPath = array_shift($removeLogPaths);

			if (@Platform::getInstance()->unlink($logPath))
			{
				continue;
			}

			Factory::getLog()->debug(
				sprintf(
					'Failed to remove old log file %s',
					$file
				)
			);
		}

		// Check if I have enough time
		if ($timer->getTimeLeft() <= 0)
		{
			return false;
		}

		return true;
	}
}