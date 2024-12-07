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

/**
 * Keeps a maximum number of "obsolete" records
 *
 * @since       9.3.1
 * @package     Akeeba\Engine\Core\Domain\Finalizer
 */
final class ObsoleteRecordsQuotas extends AbstractFinalizer
{

	/**
	 * @inheritDoc
	 */
	public function __invoke()
	{
		$this->setStep('Applying quota limit on obsolete backup records');
		$this->setSubstep('');
		$registry = Factory::getConfiguration();
		$limit    = $registry->get('akeeba.quota.obsolete_quota', 0);
		$limit    = (int) $limit;

		if ($limit <= 0)
		{
			return true;
		}

		$platform   = Platform::getInstance();
		$statsTable = $platform->tableNameStats;
		$db         = Factory::getDatabase($platform->get_platform_database_options());
		$query      =
			$db->getQuery(true)
			   ->select([
				   $db->qn('id'),
				   $db->qn('tag'),
				   $db->qn('backupid'),
				   $db->qn('absolute_path'),
			   ])
			   ->from($db->qn($statsTable))
			   ->where($db->qn('profile_id') . ' = ' . $db->q($platform->get_active_profile()))
			   ->where($db->qn('status') . ' = ' . $db->q('complete'))
			   ->where($db->qn('filesexist') . '=' . $db->q('0'))
			   ->where(
				   '(' .
				   $db->qn('remote_filename') . '=' . $db->q('') . ' OR ' .
				   $db->qn('remote_filename') . ' IS NULL'
				   . ')'
			   )
			   ->order($db->qn('id') . ' DESC');

		$db->setQuery($query, $limit, 100000);
		$records = $db->loadAssocList();

		if (empty($records))
		{
			return true;
		}

		$array = [];

		// Delete backup-specific log files if they exist and add the IDs of the records to delete in the $array
		foreach ($records as $stat)
		{
			$array[] = $stat['id'];

			// We can't delete logs if there is no backup ID in the record
			if (!isset($stat['backupid']) || empty($stat['backupid']))
			{
				continue;
			}

			$logFileName = 'akeeba.' . $stat['tag'] . '.' . $stat['backupid'] . '.log.php';
			$logPath     = dirname($stat['absolute_path']) . '/' . $logFileName;

			if (@file_exists($logPath))
			{
				@unlink($logPath);
			}

			/**
			 * Transitional period: the log file akeeba.tag.log.php may not exist but the akeeba.tag.log does. This
			 * addresses this transition.
			 */
			$logPath = dirname($stat['absolute_path']) . '/' . substr($logFileName, 0, -4);

			if (@file_exists($logPath))
			{
				@unlink($logPath);
			}
		}

		$ids = [];

		foreach ($array as $id)
		{
			$ids[] = $db->q($id);
		}

		$ids = implode(',', $ids);

		$query = $db->getQuery(true)
		            ->delete($db->qn($statsTable))
		            ->where($db->qn('id') . " IN ($ids)");
		$db->setQuery($query);
		$db->query();

		return true;
	}
}