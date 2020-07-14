<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Site\Model\Statistics;
use Akeeba\Engine\Platform;

/**
 * Delete a backup record
 */
class Delete extends AbstractTask
{
	/**
	 * Execute the JSON API task
	 *
	 * @param   array $parameters The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  \RuntimeException  In case of an error
	 */
	public function execute(array $parameters = array())
	{
		// Get the passed configuration values
		$defConfig = array(
			'backup_id' => 0,
		);

		$defConfig = array_merge($defConfig, $parameters);

		$backup_id = (int)$defConfig['backup_id'];

		/** @var Statistics $model */
		$model = $this->container->factory->model('Statistics')->tmpInstance();
		$model->setState('id', $backup_id);

		try
		{
			$model->delete();
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException($e->getMessage(), 500);
		}

		return true;
	}
}
