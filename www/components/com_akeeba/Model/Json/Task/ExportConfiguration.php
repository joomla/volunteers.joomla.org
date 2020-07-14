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
 * Export the profile's configuration
 */
class ExportConfiguration extends AbstractTask
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

		$profile_id = (int)$defConfig['profile'];

		if ($profile_id <= 0)
		{
			$profile_id = 1;
		}

		/** @var Profiles $profile */
		$profile = $this->container->factory->model('Profiles')->tmpInstance();

		$data = $profile->findOrFail($profile_id)->getData();

		if (substr($data['configuration'], 0, 12) == '###AES128###')
		{
			// Load the server key file if necessary
			if (!defined('AKEEBA_SERVERKEY'))
			{
				$filename = JPATH_COMPONENT_ADMINISTRATOR . '/BackupEngine/serverkey.php';

				include_once $filename;
			}

			$key = Factory::getSecureSettings()->getKey();

			$data['configuration'] = Factory::getSecureSettings()->decryptSettings($data['configuration'], $key);
		}

		return array(
			'description'   => $data['description'],
			'configuration' => $data['configuration'],
			'filters'       => $data['filters'],
		);
	}
}
