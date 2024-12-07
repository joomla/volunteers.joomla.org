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
use DateTime;
use Exception;

abstract class AbstractQuotaManagement extends AbstractFinalizer
{
	/**
	 * Abstraction for the engine configuration keys used in the concrete quota management class.
	 *
	 * @since 9.3.1
	 * @var   string[]
	 */
	protected $configKeys = [
		'maxAgeEnable' => 'akeeba.quota.maxage.enable',
		'maxAgeDays'   => 'akeeba.quota.maxage.maxdays',
		'maxAgeKeep'   => 'akeeba.quota.maxage.keepday',
		'countEnable'  => 'akeeba.quota.enable_count_quota',
		'countValue'   => 'akeeba.quota.count_quota',
		'sizeEnable'   => 'akeeba.quota.enable_size_quota',
		'sizeValue'    => 'akeeba.quota.size_quota',
	];

	/**
	 * The ID of the latest backup (the one we are running in right now)
	 *
	 * @since 9.3.1
	 * @var   int
	 */
	protected $latestBackupId;

	/**
	 * Human-readable quote type e.g. 'local', 'remote', etc. for the concrete quota management class.
	 *
	 * @since 9.3.1
	 * @var   string
	 */
	protected $quotaType = 'local';

	/**
	 * @inheritDoc
	 */
	public function __invoke()
	{
		$this->setStep(
			sprintf(
				'Applying %s quotas',
				$this->quotaType
			)
		);
		$this->setSubstep('');

		// If no quota settings are enabled, quit
		$configuration  = Factory::getConfiguration();
		$timer          = Factory::getTimer();
		$useDayQuotas   = $configuration->get($this->configKeys['maxAgeEnable']);
		$useCountQuotas = $configuration->get($this->configKeys['countEnable']);
		$useSizeQuotas  = $configuration->get($this->configKeys['sizeEnable']);

		if (!($useDayQuotas || $useCountQuotas || $useSizeQuotas))
		{
			Factory::getLog()->debug(
				sprintf(
					'No %s quotas were defined; old backup files will be kept intact',
					$this->quotaType
				)
			);

			return true;
		}

		// Get the latest backup ID
		$statistics           = Factory::getStatistics();
		$this->latestBackupId = $statistics->getId();

		// Try to load the calculated quotas from the volatile keys
		$keyIsCalculated    = sprintf('volatile.quotas.%s.calculated', $this->quotaType);
		$keyRemoveBackupIDs = sprintf('volatile.quotas.%s.removeBackupIDs', $this->quotaType);
		$keyRemoveLogPaths  = sprintf('volatile.quotas.%s.removeLogPaths', $this->quotaType);
		$keyFilesToRemove   = sprintf('volatile.quotas.%s.filesToRemove', $this->quotaType);

		$isCalculated    = $configuration->get($keyIsCalculated, false);
		$removeBackupIDs = $configuration->get($keyRemoveBackupIDs, []);
		$removeLogPaths  = $configuration->get($keyRemoveLogPaths, []);
		$filesToRemove   = $configuration->get($keyFilesToRemove, []);

		// Calculate the quotas if nothing was calculated just yet.
		if (!$isCalculated)
		{
			// Calculate the quotas. If nothing is found, return immediately.
			if ($this->calculateQuotas($allRecords, $removeBackupIDs, $removeLogPaths, $filesToRemove) === false)
			{
				return true;
			}

			$this->saveCalculatedQuotas($removeBackupIDs, $removeLogPaths, $filesToRemove);

			// Do I have enough time to process removals?
			if ($timer->getTimeLeft() <= 0)
			{
				return false;
			}
		}

		// Process a chunk of removals
		if (!$this->processRemovals($removeBackupIDs, $filesToRemove, $removeLogPaths))
		{
			$this->saveCalculatedQuotas($removeBackupIDs, $removeLogPaths, $filesToRemove);

			return false;
		}

		$removeBackupIDs = null;
		$removeLogPaths  = null;
		$filesToRemove   = null;

		$this->saveCalculatedQuotas($removeBackupIDs, $removeLogPaths, $filesToRemove);

		return true;
	}

	/**
	 * Get all the backup records to apply quotas on.
	 *
	 * @return  array
	 *
	 * @since   9.3.1
	 */
	abstract protected function getAllRecords(): array;

	/**
	 * Processes a list of records for removal. The removal DOES NOT take place here.
	 *
	 * @param   array  $allRecords       The records to process
	 * @param   array  $removeBackupIDs  Running tally of backup IDs to remove files from
	 * @param   array  $removeLogPaths   Running tally of log entries to remove
	 * @param   array  $ret              Running tally of arrays of files to remove
	 * @param   array  $leftover         Leftover records to be processed by the next quota rule
	 *
	 * @since   9.3.1
	 */
	protected function markAllRecordsForRemoval(array &$allRecords, array &$removeBackupIDs, array &$removeLogPaths, array &$ret, array &$leftover)
	{
		foreach ($allRecords as $def)
		{
			if ($def['id'] != $this->latestBackupId)
			{
				continue;
			}

			$temp       = array_pop($leftover);
			$leftover[] = $def;
			array_unshift($allRecords, $temp);

			break;
		}

		foreach ($allRecords as $def)
		{
			$ret[]             = $def['filenames'];
			$removeBackupIDs[] = $def['id'];

			if (empty($def['logname']))
			{
				continue;
			}

			$filePath = reset($def['filenames']);

			if (empty($filePath))
			{
				continue;
			}

			$logPath = dirname($filePath) . '/' . $def['logname'];

			if (@file_exists($logPath))
			{
				$removeLogPaths[] = $logPath;

				continue;
			}

			$altLogPath = substr($logPath, 0, -4);

			if (@file_exists($altLogPath))
			{
				/**
				 * Bad host: the log file akeeba.tag.log.php may not exist but the akeeba.tag.log file
				 * does. This code addresses this problem.
				 */
				$removeLogPaths[] = $altLogPath;
			}
		}
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
	abstract protected function processRemovals(array &$removeBackupIDs, array &$filesToRemove, array &$removeLogPaths): bool;

	/**
	 * Applies the Count Quotas.
	 *
	 * @param   array  $allRecords       All records left to process
	 * @param   array  $removeBackupIDs  Running tally of backup IDs to remove files from
	 * @param   array  $removeLogPaths   Running tally of log entries to remove
	 * @param   array  $ret              Running tally of arrays of files to remove
	 *
	 * @return  void
	 * @since   9.3.1
	 */
	private function applyCountQuotas(array &$allRecords, array &$removeBackupIDs, array &$removeLogPaths, array &$ret)
	{
		$configuration  = Factory::getConfiguration();
		$useCountQuotas = $configuration->get($this->configKeys['countEnable']);
		$countQuota     = $configuration->get($this->configKeys['countValue']);

		// Do we need to apply count quotas?
		if (!$useCountQuotas || !is_numeric($countQuota) || $countQuota <= 0)
		{
			return;
		}

		// We should only run a count quota if there are more files than the set limit
		if (count($allRecords) <= $countQuota)
		{
			return;
		}

		Factory::getLog()->debug(
			sprintf(
				'Processing %s count quotas',
				$this->quotaType
			)
		);

		/**
		 * Backups are sorted by reverse ID order, e.g. 200, 199, 198, 197, 196, 195, 194, 193, 192, 191, 190, 189, 188.
		 * I need to keep the first $countQuota records in $leftover and process the remaining records.
		 */
		$leftover   = array_slice($allRecords, 0, $countQuota);
		$allRecords = array_slice($allRecords, $countQuota);

		$this->markAllRecordsForRemoval($allRecords, $removeBackupIDs, $removeLogPaths, $ret, $leftover);

		$allRecords = $leftover;
	}

	/**
	 * Applies the Day-Based Quotas.
	 *
	 * @param   array  $allRecords       All records left to process
	 * @param   array  $removeBackupIDs  Running tally of backup IDs to remove files from
	 * @param   array  $removeLogPaths   Running tally of log entries to remove
	 * @param   array  $ret              Running tally of arrays of files to remove
	 *
	 * @return  void
	 * @since   9.3.1
	 */
	private function applyDayQuotas(array &$allRecords, array &$removeBackupIDs, array &$removeLogPaths, array &$ret): void
	{
		$configuration = Factory::getConfiguration();
		$daysQuota     = $configuration->get($this->configKeys['maxAgeDays']);
		$preserveDay   = $configuration->get($this->configKeys['maxAgeKeep']);
		$leftover      = [];

		$killDatetime = new DateTime();
		$killDatetime->modify('-' . $daysQuota . ($daysQuota == 1 ? ' day' : ' days'));
		$killTS = $killDatetime->format('U');

		/**
		 * Move the following kind of records FROM allRecords TO leftover:
		 * - Current backup record
		 * - Backups on a preserve day
		 * - Backups newer than the earliest removal date
		 */
		$allRecords = array_filter(
			$allRecords,
			function (array $def) use ($killDatetime, $killTS, $preserveDay, &$leftover): bool {
				if ($def['id'] == $this->latestBackupId)
				{
					$leftover[] = $def;

					return false;
				}

				// Is this on a preserve day?
				if ($preserveDay > 0 && $def['day'] == $preserveDay)
				{
					$leftover[] = $def;

					return false;
				}

				// Otherwise, check the timestamp
				if ($def['backupstart'] >= $killTS)
				{
					$leftover[] = $def;

					return false;
				}

				return true;
			}
		);

		$this->markAllRecordsForRemoval($allRecords, $removeBackupIDs, $removeLogPaths, $ret, $leftover);

		$allRecords = $leftover;
	}

	/**
	 * Applies the Maximum Size Quotas.
	 *
	 * @param   array  $allRecords       All records left to process
	 * @param   array  $removeBackupIDs  Running tally of backup IDs to remove files from
	 * @param   array  $removeLogPaths   Running tally of log entries to remove
	 * @param   array  $ret              Running tally of arrays of files to remove
	 *
	 * @return  void
	 * @since   9.3.1
	 */
	private function applySizeQuotas(array &$allRecords, array &$removeBackupIDs, array &$removeLogPaths, array &$ret)
	{
		$configuration = Factory::getConfiguration();
		$useSizeQuotas = $configuration->get($this->configKeys['sizeEnable']);
		$sizeQuota     = $configuration->get($this->configKeys['sizeValue']);

		// Do we need to apply size quotas?
		if (!$useSizeQuotas || !is_numeric($sizeQuota) || $sizeQuota <= 0 || count($allRecords) <= 0)
		{
			return;
		}

		Factory::getLog()->debug(
			sprintf(
				'Processing %s size quotas',
				$this->quotaType
			)
		);

		// First I will find how many elements of the array I need to get to the $sizeQuota size.
		$runningSize     = 0;
		$numberOfRecords = 0;

		foreach ($allRecords as $def)
		{
			$numberOfRecords++;

			if ($def['id'] == $this->latestBackupId)
			{
				continue;
			}

			$runningSize += $def['size'];

			if ($runningSize >= $sizeQuota)
			{
				break;
			}
		}

		$leftover   = array_slice($allRecords, 0, $numberOfRecords);
		$allRecords = array_slice($allRecords, $numberOfRecords);

		$this->markAllRecordsForRemoval($allRecords, $removeBackupIDs, $removeLogPaths, $ret, $leftover);

		$allRecords = $leftover;
	}

	private function calculateQuotas(&$allRecords, &$removeBackupIDs, &$removeLogPaths, &$filesToRemove): bool
	{
		$configuration = Factory::getConfiguration();
		$useDayQuotas  = $configuration->get($this->configKeys['maxAgeEnable']);

		$allRecords = $this->getAllRecords();

		// If there are no files, exit early
		if (count($allRecords) == 0)
		{
			Factory::getLog()->debug(
				sprintf(
					'There were no old backup records to apply %s quotas on',
					$this->quotaType
				)
			);

			return false;
		}

		// Init arrays
		$removeBackupIDs = [];
		$removeLogPaths  = [];
		$ret             = [];

		// Do we need to apply maximum backup age quotas?
		if ($useDayQuotas)
		{
			$this->applyDayQuotas($allRecords, $removeBackupIDs, $removeLogPaths, $ret);
		}
		else
		{
			$this->applyCountQuotas($allRecords, $removeBackupIDs, $removeLogPaths, $ret);
			$this->applySizeQuotas($allRecords, $removeBackupIDs, $removeLogPaths, $ret);
		}

		// Convert the $ret 2-dimensional array to single dimensional
		$filesToRemove = [];

		foreach ($ret as $temp)
		{
			$filesToRemove = array_merge($filesToRemove ?? [], $temp);
		}

		return true;
	}

	/**
	 * Save the calculated quotas into the volatile storage
	 *
	 * @param   array|null  $removeBackupIDs  Running tally of backup IDs to remove files from
	 * @param   array|null  $removeLogPaths   Running tally of log entries to remove
	 * @param   array|null  $filesToRemove    The flat list of files to remove
	 *
	 * @return  void
	 * @since   9.3.1
	 */
	private function saveCalculatedQuotas(?array &$removeBackupIDs, ?array &$removeLogPaths, ?array &$filesToRemove): void
	{
		$configuration      = Factory::getConfiguration();
		$keyIsCalculated    = sprintf('volatile.quotas.%s.calculated', $this->quotaType);
		$keyRemoveBackupIDs = sprintf('volatile.quotas.%s.removeBackupIDs', $this->quotaType);
		$keyRemoveLogPaths  = sprintf('volatile.quotas.%s.removeLogPaths', $this->quotaType);
		$keyFilesToRemove   = sprintf('volatile.quotas.%s.filesToRemove', $this->quotaType);

		$isCalculated = $removeBackupIDs !== null || $removeLogPaths !== null || $filesToRemove !== null;

		if ($isCalculated)
		{
			$configuration->set($keyIsCalculated, true);
			$configuration->set($keyRemoveBackupIDs, $removeBackupIDs);
			$configuration->set($keyRemoveLogPaths, $removeLogPaths);
			$configuration->set($keyFilesToRemove, $filesToRemove);

			return;
		}

		$configuration->remove($keyIsCalculated);
		$configuration->remove($keyRemoveBackupIDs);
		$configuration->remove($keyRemoveLogPaths);
		$configuration->remove($keyFilesToRemove);
	}
}