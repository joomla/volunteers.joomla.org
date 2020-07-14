<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Site\Model\MultipleDatabases;
use Akeeba\Engine\Platform;

/**
 * Remove an extra database definition
 */
class RemoveIncludedDB extends AbstractTask
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
		$filter = \JFilterInput::getInstance();

		// Get the passed configuration values
		$defConfig = array(
			'profile'       => 0,
			'name'          => '',
		);

		$defConfig = array_merge($defConfig, $parameters);

		$profile       = $filter->clean($defConfig['profile'], 'int');
		$name          = $filter->clean($defConfig['name'], 'string');

		// We need a valid profile ID
		if ($profile <= 0)
		{
			$profile = 1;
		}

		// We need a uuid
		if (empty($name))
		{
			throw new \RuntimeException('The database name is required', 500);
		}

		// Set the active profile
		$this->container->platform->setSessionVar('profile', $profile);

		// Load the configuration
		Platform::getInstance()->load_configuration($profile);

		/** @var MultipleDatabases $model */
		$model = $this->container->factory->model('MultipleDatabases')->tmpInstance();

		return $model->remove($name);
	}
}
