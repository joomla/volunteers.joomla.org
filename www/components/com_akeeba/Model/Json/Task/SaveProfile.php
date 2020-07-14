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
 * Saves a backup profile
 */
class SaveProfile extends AbstractTask
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
			'profile'     => 0,
			'description' => null,
			'quickicon'   => null,
			'source'      => 0,
		);

		$defConfig = array_merge($defConfig, $parameters);

		$profile     = (int)$defConfig['profile'];
		$description = $defConfig['description'];
		$quickicon   = $defConfig['quickicon'];
		$source      = (int)$defConfig['source'];

		if ($profile <= 0)
		{
			$profile = null;
		}

		// At least one of these parameters is required
		if (empty($profile) && empty($source) && empty($description))
		{
			throw new \RuntimeException('Invalid profile ID', 404);
		}

		// Get a profile model
		/** @var Profiles $profileModel */
		$profileModel = $this->container->factory->model('Profiles')->tmpInstance();

		// Load the profile
		$sourceId = empty($profile) ? $source : $profile;

		if (!empty($sourceId))
		{
			$profileModel->findOrFail($sourceId);
		}
		else
		{
			$profileModel->reset(true);
		}

		$profileModel->setFieldValue('id', $profile);

		if ($description)
		{
			$profileModel->setFieldValue('description', $description);
		}

		if (!is_null($quickicon))
		{
			$profileModel->setFieldValue('quickicon', (int)$quickicon);
		}

		$profileModel->save();

		return true;
	}
}
