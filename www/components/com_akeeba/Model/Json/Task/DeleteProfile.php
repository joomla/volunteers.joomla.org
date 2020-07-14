<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Site\Model\Profiles;
use Akeeba\Engine\Platform;

/**
 * Delete a backup profile
 */
class DeleteProfile extends AbstractTask
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
			'profile' => 0,
		);

		$defConfig = array_merge($defConfig, $parameters);

		$profile = (int)$defConfig['profile'];

		// You need to specify the profile
		if (empty($profile))
		{
			throw new \RuntimeException('Invalid profile ID', 404);
		}

		if ($profile == 1)
		{
			throw new \RuntimeException('You cannot delete the default backup profile', 404);
		}

		// Get a profile model
		/** @var Profiles $profileModel */
		$profileModel = $this->container->factory->model('Profiles')->tmpInstance();
		$profileModel->findOrFail($profile);
		$profileModel->delete();

		return true;
	}
}
