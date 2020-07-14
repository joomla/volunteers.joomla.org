<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Site\Model\IncludeFolders;
use Akeeba\Engine\Platform;

/**
 * Remove an extra directory definition
 */
class RemoveIncludedDirectory extends AbstractTask
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
			'uuid'          => '',
		);

		$defConfig = array_merge($defConfig, $parameters);

		$profile       = $filter->clean($defConfig['profile'], 'int');
		$uuid          = $filter->clean($defConfig['uuid'], 'string');

		// We need a valid profile ID
		if ($profile <= 0)
		{
			$profile = 1;
		}

		// We need a uuid
		if (empty($uuid))
		{
			throw new \RuntimeException('UUID is required', 500);
		}

		// Set the active profile
		$this->container->platform->setSessionVar('profile', $profile);

		// Load the configuration
		Platform::getInstance()->load_configuration($profile);

		/** @var IncludeFolders $model */
		$model = $this->container->factory->model('IncludeFolders')->tmpInstance();

		return $model->remove($uuid);
	}
}
