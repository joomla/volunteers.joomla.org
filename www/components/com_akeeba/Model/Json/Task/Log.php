<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Admin\Model\Log as LogModel;

/**
 * Get the log contents
 */
class Log extends AbstractTask
{
	/**
	 * Execute the JSON API task
	 *
	 * @param   array  $parameters  The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  \RuntimeException  In case of an error
	 */
	public function execute(array $parameters = [])
	{
		// Get the passed configuration values
		$defConfig = [
			'tag' => 'remote',
		];

		$defConfig = array_merge($defConfig, $parameters);
		$tag       = (int) $defConfig['tag'];

		/** @var LogModel $model */
		$model = $this->container->factory->model('Log')->tmpInstance();
		$model->setState('tag', $tag);

		@ob_start();
		$model->echoRawLog(false);

		return @ob_get_clean();
	}
}
