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
 * List the backup records
 */
class ListBackups extends AbstractTask
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
			'from'  => 0,
			'limit' => 50
		);

		$defConfig = array_merge($defConfig, $parameters);

		$from  = (int)$defConfig['from'];
		$limit = (int)$defConfig['limit'];

		/** @var Statistics $model */
		$model = $this->container->factory->model('Statistics')->tmpInstance();
		$model->setState('limitstart', $from);
		$model->setState('limit', $limit);

		return $model->getStatisticsListWithMeta(false);
	}
}
