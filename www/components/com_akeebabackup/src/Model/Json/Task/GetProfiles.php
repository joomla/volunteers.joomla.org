<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Model\ProfilesModel;
use Exception;
use RuntimeException;
use stdClass;

/**
 * Get a list of known backup profiles
 */
class GetProfiles extends AbstractTask
{
	/**
	 * Execute the JSON API task
	 *
	 * @param   array  $parameters  The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  RuntimeException|Exception  In case of an error
	 */
	public function execute(array $parameters = [])
	{
		/** @var ProfilesModel $model */
		$model = $this->factory->createModel('Profiles', 'Administrator', ['ignore_request' => true]);

		$model->setState('list.start', 0);
		$model->setState('list.limit', 0);

		$profiles = $model->getItems();
		$ret      = [];

		if (count($profiles))
		{
			foreach ($profiles as $profile)
			{
				$temp       = new stdClass();
				$temp->id   = $profile->id;
				$temp->name = $profile->description;
				$ret[]      = $temp;
			}
		}

		return $ret;
	}
}
