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
			(method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
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

			$baseName = dirname($stat['absolute_path']) . '/akeeba.' . $stat['tag'] . '.' . $stat['backupid'];

			/**
			 * Transitional period: the log file akeeba.tag.log.php may not exist but the akeeba.tag.php or the
			 * akeeba.tag.log does. This addresses this transition.
			 */
			foreach (['.log.php', '.php', '.log'] as $suffix)
			{
				$logPath = $baseName . $suffix;

				if (@file_exists($logPath))
				{
					@unlink($logPath);
				}
			}
		}

		$ids = [];

		foreach ($array as $id)
		{
			$ids[] = $db->q($id);
		}

		$ids = implode(',', $ids);

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
		            ->delete($db->qn($statsTable))
		            ->where($db->qn('id') . " IN ($ids)");
		$db->setQuery($query);
		$db->query();

		return true;
	}
}