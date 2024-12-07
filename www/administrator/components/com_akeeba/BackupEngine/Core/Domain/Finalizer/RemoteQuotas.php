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

		parent::__construct($finalizationPart);
	}

	/** @inheritDoc */
	public function __invoke()
	{
		// Make sure we are enabled
		$configuration = Factory::getConfiguration();
		$enableRemote  = $configuration->get('akeeba.quota.remote', 0);

		if (!$enableRemote)
		{
			Factory::getLog()->debug('Count quotas are not enabled; no remote quotas will be processed.');

			return true;
		}

		return parent::__invoke();
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
					'id'          => $stat['id'],
					'filenames'   => $remoteFilenames,
					'size'        => $stat['total_size'],
					'backupstart' => $backupTS,
					'day'         => $backupDay,
					'logname'     => $logName,
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