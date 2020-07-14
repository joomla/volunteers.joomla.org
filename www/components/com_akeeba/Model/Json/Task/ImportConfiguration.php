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
use Akeeba\Engine\Factory;

/**
 * Import the profile's configuration
 */
class ImportConfiguration extends AbstractTask
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
			'data' => null,
		);

		$defConfig = array_merge($defConfig, $parameters);

		$profile_id = (int)$defConfig['profile'];
		$data = $defConfig['data'];

		if ($profile_id <= 0)
		{
			$profile_id = 0;
		}

		/** @var Profiles $profile */
		$profile = $this->container->factory->model('Profiles')->tmpInstance();

		if ($profile_id)
		{
			$profile->find($profile_id);
		}

		$profile->import($data);

		return true;
	}
}
