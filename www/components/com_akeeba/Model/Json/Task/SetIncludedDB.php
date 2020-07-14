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
 * Set up or edit an extra database definition
 */
class SetIncludedDB extends AbstractTask
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
			'profile'    => 0,
			'name'       => '',
			'connection' => array(),
			'test'       => true,
		);

		$defConfig = array_merge($defConfig, $parameters);

		$profile    = $filter->clean($defConfig['profile'], 'int');
		$name       = $filter->clean($defConfig['name'], 'string');
		$connection = $filter->clean($defConfig['connection'], 'array');
		$test       = $filter->clean($defConfig['test'], 'bool');

		// We need a valid profile ID
		if ($profile <= 0)
		{
			$profile = 1;
		}

		if (
			empty($connection) || !isset($connection['host']) || !isset($connection['driver'])
			|| !isset($connection['database']) || !isset($connection['user'])
			|| !isset($connection['password'])
		)
		{
			throw new \RuntimeException('Connection information missing or incomplete', 500);
		}

		// Set the active profile
		$this->container->platform->setSessionVar('profile', $profile);

		// Load the configuration
		Platform::getInstance()->load_configuration($profile);

		/** @var MultipleDatabases $model */
		$model = $this->container->factory->model('MultipleDatabases')->tmpInstance();

		if ($test)
		{
			$result = $model->test($connection);

			if (!$result['status'])
			{
				throw new \RuntimeException('Connection test failed: ' . $result['message'], 500);
			}
		}

		return $model->setFilter($name, $connection);
	}
}
