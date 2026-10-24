<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Model\StatisticModel;
use RuntimeException;

/**
 * Delete a backup record
 */
class Delete extends AbstractTask
{
	/**
	 * The backend view.task key this API method is equivalent to.
	 *
	 * @var    string
	 * @since  10.4.0
	 */
	protected $aclKey = 'manage.delete';

	/**
	 * Execute the JSON API task
	 *
	 * @param   array  $parameters  The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  RuntimeException  In case of an error
	 */
	public function execute(array $parameters = [])
	{
		// Get the passed configuration values
		$defConfig = [
			'backup_id' => 0,
		];

		$defConfig = array_merge($defConfig, $parameters);

		$backup_id = (int) $defConfig['backup_id'];

		/** @var StatisticModel $model */
		$model = $this->factory->createModel('Statistic', 'Administrator', ['ignore_request' => true]);
		$model->setState('id', $backup_id);
		$model->setState('workaround.override_canDelete', true);

		$ids = [$backup_id];

		if (!$model->delete($ids))
		{
			/** @deprecated 10.1.0 Only for Joomla 4 b/c. Remove in 11. */
			/** @noinspection PhpDeprecationInspection */
			throw new RuntimeException($model->getError(), 500);
		}

		return true;
	}
}
