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

/**
 * Updates the backup statistics record
 *
 * @since       9.3.1
 * @package     Akeeba\Engine\Core\Domain\Finalizer
 */
final class UpdateStatistics extends AbstractFinalizer
{
	/**
	 * @inheritDoc
	 */
	public function __invoke()
	{
		$this->setStep('Updating backup record information');
		$this->setSubstep('');

		Factory::getLog()->debug('Updating statistics');

		// We finished normally. Fetch the stats record
		$statistics = Factory::getStatistics();
		$registry   = Factory::getConfiguration();
		$data       = [
			'backupend' => Platform::getInstance()->get_timestamp_database(),
			'status'    => 'complete',
			'multipart' => $registry->get('volatile.statistics.multipart', 0),
		];

		try
		{
			$result = $statistics->setStatistics($data);
		}
		catch (Exception $e)
		{
			$result = false;
		}

		if ($result === false)
		{
			// Most likely a "MySQL has gone away" issue...
			$configuration = Factory::getConfiguration();
			$configuration->set('volatile.breakflag', true);

			return false;
		}

		/**
		 * We could have handled it in $data above. However, if the schema has not been updated this function will
		 * continue failing infinitely, causing the backup to never end.
		 */
		$statistics->updateInStep(false);

		$stat = (object) $statistics->getRecord();
		Platform::getInstance()->remove_duplicate_backup_records($stat->archivename);

		return true;
	}
}