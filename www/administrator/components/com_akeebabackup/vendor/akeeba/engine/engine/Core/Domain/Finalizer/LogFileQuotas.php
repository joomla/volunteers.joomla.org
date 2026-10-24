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

namespace Akeeba\Engine\Core\Domain\Finalizer;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use DateTime;
use Exception;

/**
 * Applies quota management to backup log files, deleting old log files per the configured policy.
 *
 * @since   10.3.3
 * @package Akeeba\Engine\Core\Domain\Finalizer
 */
final class LogFileQuotas extends AbstractFinalizer
{
	private const KEY_CALCULATED       = 'volatile.quotas.logfiles.calculated';
	private const KEY_REMOVE_LOG_PATHS = 'volatile.quotas.logfiles.removeLogPaths';

	/**
	 * @inheritDoc
	 */
	public function __invoke(): bool
	{
		$this->setStep('Applying log file quotas');
		$this->setSubstep('');

		$configuration = Factory::getConfiguration();
		$timer         = Factory::getTimer();

		$quotaMode = (int) $configuration->get('akeeba.quota.enable_logfiles', 0);

		if ($quotaMode === 0)
		{
			Factory::getLog()->debug('Log file quotas are disabled; log files will be kept intact.');

			return true;
		}

		$isCalculated   = (bool) $configuration->get(self::KEY_CALCULATED, false);
		$removeLogPaths = $configuration->get(self::KEY_REMOVE_LOG_PATHS, []) ?: [];

		if (!$isCalculated)
		{
			$candidates     = $this->collectCandidates();
			$removeLogPaths = $this->calculateRemovePaths($candidates, $configuration);

			$configuration->set(self::KEY_CALCULATED, true);
			$configuration->set(self::KEY_REMOVE_LOG_PATHS, $removeLogPaths);

			if ($timer->getTimeLeft() <= 0)
			{
				return false;
			}
		}

		while (!empty($removeLogPaths) && $timer->getTimeLeft() > 0)
		{
			$logPath = array_shift($removeLogPaths);

			if (!@Platform::getInstance()->unlink($logPath))
			{
				Factory::getLog()->warning(sprintf('Failed to remove log file %s', $logPath));
			}
			else
			{
				Factory::getLog()->debug(sprintf('Removed log file %s', $logPath));
			}
		}

		$configuration->set(self::KEY_REMOVE_LOG_PATHS, $removeLogPaths);

		if (!empty($removeLogPaths))
		{
			return false;
		}

		$configuration->remove(self::KEY_CALCULATED);
		$configuration->remove(self::KEY_REMOVE_LOG_PATHS);

		return true;
	}

	/**
	 * Collects log file candidates from the database for the current profile.
	 *
	 * @return  array  Each entry has keys: id, logpath, size, backupstart
	 * @since   10.3.3
	 */
	private function collectCandidates(): array
	{
		$platform   = Platform::getInstance();
		$db         = Factory::getDatabase($platform->get_platform_database_options());
		$statsTable = $platform->tableNameStats;
		$profileId  = $platform->get_active_profile();

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->qn('id'),
				$db->qn('tag'),
				$db->qn('backupid'),
				$db->qn('absolute_path'),
				$db->qn('backupstart'),
				$db->qn('frozen'),
			])
			->from($db->qn($statsTable))
			->where($db->qn('profile_id') . ' = ' . $db->q($profileId))
			->order($db->qn('id') . ' DESC');

		$db->setQuery($query);
		$records = $db->loadAssocList();

		$candidates = [];

		foreach ($records as $stat)
		{
			if (!empty($stat['frozen']))
			{
				Factory::getLog()->debug(
					sprintf('Excluding frozen backup id %d from log file quota management', $stat['id'])
				);
				continue;
			}

			if (empty($stat['absolute_path']) || empty($stat['backupid']))
			{
				continue;
			}

			$logPath = $this->resolveLogPath($stat);

			if ($logPath === null)
			{
				continue;
			}

			$backupTS = 0;

			if (!empty($stat['backupstart']))
			{
				try
				{
					$dt       = new DateTime($stat['backupstart']);
					$backupTS = (int) $dt->format('U');
				}
				catch (Exception $e)
				{
					$backupTS = 0;
				}
			}

			$candidates[] = [
				'id'          => (int) $stat['id'],
				'logpath'     => $logPath,
				'size'        => (int) (@filesize($logPath) ?: 0),
				'backupstart' => $backupTS,
			];
		}

		return $candidates;
	}

	/**
	 * Resolves the log file path for a backup record, handling the transitional .log.php → .php → .log fallback.
	 *
	 * @param   array  $stat  The backup record fields
	 *
	 * @return  string|null  The path to the log file, or null if no log file exists on disk
	 * @since   10.3.3
	 */
	private function resolveLogPath(array $stat): ?string
	{
		$logDir   = dirname($stat['absolute_path']);
		$baseName = $logDir . '/akeeba.' . $stat['tag'] . '.' . $stat['backupid'];

		// Transitional period: the .log.php file may not exist but the .php or the .log file does
		foreach (['.log.php', '.php', '.log'] as $suffix)
		{
			if (@file_exists($baseName . $suffix))
			{
				return $baseName . $suffix;
			}
		}

		return null;
	}

	/**
	 * Determines which log files to remove based on the configured quota type.
	 *
	 * @param   array   $candidates     Log file candidates, sorted newest-first
	 * @param   object  $configuration  The engine configuration object
	 *
	 * @return  array  List of log file paths to remove
	 * @since   10.3.3
	 */
	private function calculateRemovePaths(array $candidates, $configuration): array
	{
		if (empty($candidates))
		{
			Factory::getLog()->debug('No log file candidates found for quota management.');

			return [];
		}

		$latestId  = Factory::getStatistics()->getId();
		$quotaType = $configuration->get('akeeba.quota.logfiles.type', 'count');

		switch ($quotaType)
		{
			case 'size':
				return $this->applyLogSizeQuota($candidates, $latestId, $configuration);

			case 'days':
				return $this->applyLogDaysQuota($candidates, $latestId, $configuration);

			case 'count':
			default:
				return $this->applyLogCountQuota($candidates, $latestId, $configuration);
		}
	}

	/**
	 * Applies a count-based quota: keep the N most recent log files.
	 *
	 * @param   array   $candidates     Log file candidates, sorted newest-first
	 * @param   int     $latestId       The current backup ID (never deleted)
	 * @param   object  $configuration  The engine configuration object
	 *
	 * @return  array
	 * @since   10.3.3
	 */
	private function applyLogCountQuota(array $candidates, int $latestId, $configuration): array
	{
		$count = max(1, (int) $configuration->get('akeeba.quota.logfiles.count', 3));

		Factory::getLog()->debug(sprintf('Applying log file count quota: keep %d log files.', $count));

		if (count($candidates) <= $count)
		{
			return [];
		}

		$toRemove    = array_slice($candidates, $count);
		$removePaths = [];

		foreach ($toRemove as $entry)
		{
			if ($entry['id'] === $latestId)
			{
				continue;
			}

			$removePaths[] = $entry['logpath'];
		}

		return $removePaths;
	}

	/**
	 * Applies a size-based quota: keep log files totalling up to X bytes.
	 *
	 * @param   array   $candidates     Log file candidates, sorted newest-first
	 * @param   int     $latestId       The current backup ID (never deleted)
	 * @param   object  $configuration  The engine configuration object
	 *
	 * @return  array
	 * @since   10.3.3
	 */
	private function applyLogSizeQuota(array $candidates, int $latestId, $configuration): array
	{
		$sizeLimit = (int) $configuration->get('akeeba.quota.logfiles.size', 0);

		if ($sizeLimit <= 0)
		{
			Factory::getLog()->debug('Log file size quota: limit is 0 or not set; no files removed.');

			return [];
		}

		Factory::getLog()->debug(
			sprintf('Applying log file size quota: keep up to %d bytes of log files.', $sizeLimit)
		);

		$runningSize = 0;
		$keepCount   = 0;

		foreach ($candidates as $entry)
		{
			$keepCount++;
			$runningSize += $entry['size'];

			if ($entry['id'] !== $latestId && $runningSize >= $sizeLimit)
			{
				break;
			}
		}

		$toRemove    = array_slice($candidates, $keepCount);
		$removePaths = [];

		foreach ($toRemove as $entry)
		{
			if ($entry['id'] === $latestId)
			{
				continue;
			}

			$removePaths[] = $entry['logpath'];
		}

		return $removePaths;
	}

	/**
	 * Applies a days-based quota: keep log files from the last X days.
	 *
	 * @param   array   $candidates     Log file candidates, sorted newest-first
	 * @param   int     $latestId       The current backup ID (never deleted)
	 * @param   object  $configuration  The engine configuration object
	 *
	 * @return  array
	 * @since   10.3.3
	 */
	private function applyLogDaysQuota(array $candidates, int $latestId, $configuration): array
	{
		$days = max(1, (int) $configuration->get('akeeba.quota.logfiles.days', 30));

		Factory::getLog()->debug(
			sprintf('Applying log file days quota: keep log files from the last %d days.', $days)
		);

		$killDatetime = new DateTime();
		$killDatetime->modify('-' . $days . ($days === 1 ? ' day' : ' days'));
		$killTS = (int) $killDatetime->format('U');

		$removePaths = [];

		foreach ($candidates as $entry)
		{
			if ($entry['id'] === $latestId)
			{
				continue;
			}

			if ($entry['backupstart'] < $killTS)
			{
				$removePaths[] = $entry['logpath'];
			}
		}

		return $removePaths;
	}
}
