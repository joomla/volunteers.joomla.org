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

/**
 * Get a list of known backup profiles
 */
class GetProfiles extends AbstractTask
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
		/** @var Profiles $model */
		$model = $this->container->factory->model('Profiles')->tmpInstance();

		$profiles = $model->get(true);
		$ret      = array();

		if (count($profiles))
		{
			foreach ($profiles as $profile)
			{
				$temp       = new \stdClass();
				$temp->id   = $profile->id;
				$temp->name = $profile->description;
				$ret[]      = $temp;
			}
		}

		return $ret;
	}
}
