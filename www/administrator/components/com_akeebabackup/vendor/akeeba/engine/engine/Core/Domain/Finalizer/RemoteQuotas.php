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

use Akeeba\Engine\Core\Domain\Finalization;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use DateTime;
use Exception;

class RemoteQuotas extends AbstractQuotaManagement
{
	/** @inheritDoc */
	public function __construct(Finalization $finalizationPart)
	{
		$this->quotaType = 'remote';

		$this->configKeys = [
			'maxAgeEnable' => 'akeeba.quota.remotely.maxage.enable',
			'maxAgeDays'   => 'akeeba.quota.remotely.maxage.maxdays',
			'maxAgeKeep'   => 'akeeba.quota.remotely.maxage.keepday',
			'countEnable'  => 'akeeba.quota.remotely.enable_count_quota',
			'countValue'   => 'akeeba.quota.remotely.count_quota',
			'sizeEnable'   => 'akeeba.quota.remotely.enable_size_quota',
			'sizeValue'    => 'akeeba.quota.remotely.size_quota',
		];

		parent::__construct($finalizationPart);
	}

	/** @inheritDoc */
	protected function getAllRecords(): array
	{
		$configuration = Factory::getConfiguration();
		$useLatest = $configuration->get('akeeba.quota.remote_latest', '1') == 1;

		// Get all records with a remote filename and filter out the current record and frozen records
		$allRecords = array_filter(
			Platform::getInstance()->get_valid_remote_records() ?: [],
			function (array $stat) use ($useLatest): bool {
				// Exclude frozen records from quota management
				if (isset($stat['frozen']) && $stat['frozen'])
				{
					Factory::getLog()->debug(
						sprintf(
							'Excluding frozen backup id %d from %s quota management',
							$stat['id'],
							$this->quotaType
						)
					);

					return false;
				}

				// Exclude the current record from the remote quota management
				return $useLatest ? true : ($stat['id'] != $this->latestBackupId);
			}
		);

		// Convert stat records to entries used in quota management
		return array_map(
			function (array $stat): array {
				$remoteFilenames = $this->getRemoteFiles($stat['remote_filename'], $stat['multipart']);

				try
				{
					$backupStart = new DateTime($stat['backupstart']);
					$backupTS    = $backupStart->format('U');
					$backupDay   = $backupStart->format('d');
				}
				catch (Exception $e)
				{
					$backupTS  = 0;
					$backupDay = 0;
				}

				// Get the log file name
				$tag      = $stat['tag'] ?? 'backend';
				$backupId = $stat['backupid'] ?? '';
				$logName  = '';

				if (!empty($backupId))
				{
					$logName = 'akeeba.' . $tag . '.' . $backupId . '.log.php';
				}

				return [
					'id'            => $stat['id'],
					'filenames'     => $remoteFilenames,
					'size'          => $stat['total_size'],
					'backupstart'   => $backupTS,
					'day'           => $backupDay,
					'logname'       => $logName,
					'absolute_path' => $stat['absolute_path'],
				];
			},
			$allRecords
		);
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
			$data = ['remote_filename' => ''];

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
			$filename = array_shift($filesToRemove);
			[$engineName, $path] = explode('://', $filename);
			$engine = Factory::getPostprocEngine($engineName);

			if (!$engine->supportsDelete())
			{
				continue;
			}

			Factory::getLog()->debug(
				sprintf(
					'Removing remotely stored file %s',
					$filename
				)
			);

			try
			{
				$engine->delete($path);
			}
			catch (Exception $e)
			{
				Factory::getLog()->debug(
					sprintf(
						'Could not remove remotely stored file. Error: %s',
						$e->getMessage()
					)
				);
			}
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
					$logPath
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

	/**
	 * Get the full paths to all remote backup parts
	 *
	 * @param   string  $filename   The full filename of the last part stored in the database
	 * @param   int     $multipart  How many parts does this archive consist of?
	 *
	 * @return  array  A list of the full paths of all remotely stored backup archive parts
	 * @since   9.3.1
	 */
	private function getRemoteFiles(string $filename, int $multipart): array
	{
		$result = [];

		$extension       = substr($filename, -3);
		$base            = substr($filename, 0, -4);
		$extensionPrefix = substr($extension, 0, 1);
		$result[]        = $filename;

		if ($multipart <= 1)
		{
			return $result;
		}

		for ($i = 1; $i < $multipart; $i++)
		{
			$newExt   = $extensionPrefix . sprintf('%02u', $i);
			$result[] = $base . '.' . $newExt;
		}

		return $result;
	}

}